<?php
/**
 * WooCommerce Popularity Rank Calculator
 *
 * Calculates a normalized 0.0–100.0 popularity_rank score per product from
 * recent order data and stores it in product meta under `_popularity_rank_score`
 * for later use in feed exports (e.g. Google Merchant Center).
 *
 * Phase 1: script only. No admin UI, no settings page, no feed logic.
 *
 * Usage (snippet loader, WP-CLI, or a custom plugin):
 *
 *     $result = wc_calculate_popularity_ranks();
 *     // or with overrides:
 *     $result = wc_calculate_popularity_ranks( array(
 *         'lookback_days'  => 90,
 *         'order_statuses' => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
 *         'revenue_weight' => 0.70,
 *         'qty_weight'     => 0.30,
 *     ) );
 *
 * @package WC_Popularity_Rank
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

if ( ! class_exists( 'WC_Popularity_Rank_Calculator' ) ) :

	/**
	 * Calculates and stores product popularity rank scores.
	 */
	class WC_Popularity_Rank_Calculator {

		/**
		 * Meta key used to store the final score.
		 *
		 * @var string
		 */
		const META_KEY = '_popularity_rank_score';

		/**
		 * Resolved configuration.
		 *
		 * @var array
		 */
		protected $config;

		/**
		 * @param array $args Optional configuration overrides.
		 */
		public function __construct( array $args = array() ) {
			$defaults = array(
				// Rolling window of order history to consider, in days.
				'lookback_days'   => 90,
				// Order statuses that count as a real sale (prefixed 'wc-').
				'order_statuses'  => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
				// Weighting of the raw score. Should sum to 1.0.
				'revenue_weight'  => 0.70,
				'qty_weight'      => 0.30,
				// When true, products with no qualifying sales are scored 0.0.
				// When false (default), unsold products are skipped entirely.
				'include_unsold'  => false,
				// When true, log-transform revenue and quantity before normalizing.
				// Softens the long-tail crush on catalogs where a few products
				// dominate sales, giving the percentile buckets more gradation.
				// Off by default so scoring behaviour is unchanged unless enabled.
				'log_transform'   => false,
				// How many orders to load per batch (memory safety).
				'orders_per_page' => 200,
			);

			$this->config = wp_parse_args( $args, $defaults );

			// Guard against a zero weight-sum causing division issues.
			$weight_sum = $this->config['revenue_weight'] + $this->config['qty_weight'];
			if ( $weight_sum <= 0 ) {
				$this->config['revenue_weight'] = 0.70;
				$this->config['qty_weight']     = 0.30;
			}
		}

		/**
		 * Run the full calculation: collect → rank → save.
		 *
		 * Safe to run repeatedly; each run overwrites the single stored value.
		 *
		 * @return array {
		 *     Summary of the run.
		 *
		 *     @type int   $products_scored Number of products that received a score.
		 *     @type int   $orders_scanned  Number of orders read.
		 *     @type array $scores          Map of product_id => score (for logging/testing).
		 * }
		 */
		public function calculate() {
			$sales = $this->collect_sales_data( $orders_scanned );
			$scores = $this->rank_and_normalize( $sales );
			$saved  = $this->save_scores( $scores );

			return array(
				'products_scored' => $saved,
				'orders_scanned'  => (int) $orders_scanned,
				'scores'          => $scores,
			);
		}

		/**
		 * Collect per-product recent sales metrics from qualifying orders.
		 *
		 * Uses wc_get_orders() (HPOS-safe) with pagination. Variation sales are
		 * rolled up into their parent product id.
		 *
		 * @param int $orders_scanned Passed by reference; set to the order count read.
		 * @return array Map of product_id => array( 'revenue' => float, 'qty' => float ).
		 */
		protected function collect_sales_data( &$orders_scanned = 0 ) {
			$sales          = array();
			$orders_scanned = 0;
			$after          = gmdate( 'Y-m-d H:i:s', time() - ( absint( $this->config['lookback_days'] ) * DAY_IN_SECONDS ) );
			$page           = 1;

			do {
				$orders = wc_get_orders(
					array(
						'status'       => $this->config['order_statuses'],
						'date_created' => '>=' . $after,
						'limit'        => absint( $this->config['orders_per_page'] ),
						'paged'        => $page,
						'orderby'      => 'ID',
						'order'        => 'ASC',
						'return'       => 'objects',
					)
				);

				if ( empty( $orders ) ) {
					break;
				}

				foreach ( $orders as $order ) {
					if ( ! $order instanceof WC_Order ) {
						continue;
					}

					$orders_scanned++;

					foreach ( $order->get_items() as $item ) {
						if ( ! $item instanceof WC_Order_Item_Product ) {
							continue;
						}

						// get_product_id() returns the parent for variations,
						// giving us basic parent/child aggregation for free.
						$product_id = $item->get_product_id();
						if ( ! $product_id ) {
							continue;
						}

						if ( ! isset( $sales[ $product_id ] ) ) {
							$sales[ $product_id ] = array(
								'revenue' => 0.0,
								'qty'     => 0.0,
							);
						}

						// Net line revenue (after discounts, excl. tax).
						$sales[ $product_id ]['revenue'] += (float) $item->get_total();
						$sales[ $product_id ]['qty']     += (float) $item->get_quantity();
					}
				}

				$page++;

				// Stop when the last page returned fewer than a full batch.
			} while ( count( $orders ) === absint( $this->config['orders_per_page'] ) );

			return $sales;
		}

		/**
		 * Turn raw sales metrics into a final 0.0–100.0 percentile score per product.
		 *
		 * Steps:
		 *   0. Optionally log-transform revenue and quantity (long-tail softening).
		 *   1. Min–max normalize revenue and quantity across the catalog to 0–100.
		 *   2. Weighted raw score (default 70% revenue, 30% quantity).
		 *   3. Percentile-rank the raw score across all included products.
		 *
		 * @param array $sales Map of product_id => array( 'revenue', 'qty' ).
		 * @return array Map of product_id => float score (1 decimal).
		 */
		protected function rank_and_normalize( array $sales ) {
			if ( empty( $sales ) ) {
				return array();
			}

			// 0: optional log-transform. log1p (log(1 + x)) keeps zeros safe and
			// preserves ordering, while compressing large values so a few
			// blockbuster products don't flatten the rest of the catalog.
			if ( $this->config['log_transform'] ) {
				foreach ( $sales as $product_id => $metrics ) {
					$sales[ $product_id ]['revenue'] = log( 1 + max( 0.0, $metrics['revenue'] ) );
					$sales[ $product_id ]['qty']     = log( 1 + max( 0.0, $metrics['qty'] ) );
				}
			}

			$revenues = wp_list_pluck( $sales, 'revenue' );
			$quantities = wp_list_pluck( $sales, 'qty' );

			$max_revenue = max( $revenues );
			$max_qty     = max( $quantities );

			$rev_weight = $this->config['revenue_weight'];
			$qty_weight = $this->config['qty_weight'];
			$weight_sum = $rev_weight + $qty_weight;

			// 1 + 2: normalized, weighted raw score per product.
			$raw = array();
			foreach ( $sales as $product_id => $metrics ) {
				$rev_norm = ( $max_revenue > 0 ) ? ( $metrics['revenue'] / $max_revenue ) * 100 : 0.0;
				$qty_norm = ( $max_qty > 0 ) ? ( $metrics['qty'] / $max_qty ) * 100 : 0.0;

				$raw[ $product_id ] = ( ( $rev_norm * $rev_weight ) + ( $qty_norm * $qty_weight ) ) / $weight_sum;
			}

			// 3: percentile rank. percentile = (# products with raw <= this) / N * 100.
			$total  = count( $raw );
			$values = array_values( $raw );
			$scores = array();

			foreach ( $raw as $product_id => $score ) {
				$at_or_below = 0;
				foreach ( $values as $other ) {
					if ( $other <= $score ) {
						$at_or_below++;
					}
				}

				$percentile = ( $at_or_below / $total ) * 100;
				$scores[ $product_id ] = round( $percentile, 1 );
			}

			return $scores;
		}

		/**
		 * Persist scores to product meta using WooCommerce product methods.
		 *
		 * Uses wc_get_product() + update_meta_data() + save() so WooCommerce hooks
		 * and object caches stay consistent (no direct update_post_meta()).
		 *
		 * @param array $scores Map of product_id => float score.
		 * @return int Number of products successfully saved.
		 */
		protected function save_scores( array $scores ) {
			$saved = 0;

			// Optionally floor every un-scored published product to 0.0.
			if ( $this->config['include_unsold'] ) {
				$scores = $this->add_unsold_products( $scores );
			}

			foreach ( $scores as $product_id => $score ) {
				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					continue;
				}

				$product->update_meta_data( self::META_KEY, number_format( (float) $score, 1, '.', '' ) );
				$product->save();
				$saved++;
			}

			return $saved;
		}

		/**
		 * Add every published product missing from $scores with a 0.0 score.
		 *
		 * Only called when 'include_unsold' is true.
		 *
		 * @param array $scores Existing scores keyed by product_id.
		 * @return array Scores plus 0.0 entries for unsold products.
		 */
		protected function add_unsold_products( array $scores ) {
			$all_ids = wc_get_products(
				array(
					'status' => 'publish',
					'limit'  => -1,
					'return' => 'ids',
				)
			);

			foreach ( $all_ids as $product_id ) {
				if ( ! isset( $scores[ $product_id ] ) ) {
					$scores[ $product_id ] = 0.0;
				}
			}

			return $scores;
		}
	}

endif;

if ( ! function_exists( 'wc_calculate_popularity_ranks' ) ) :

	/**
	 * Convenience wrapper: calculate and store popularity ranks in one call.
	 *
	 * Intended for manual execution via a code hook, WP-CLI, or a snippet.
	 * Structured so WP-Cron scheduling can wrap this later, e.g.:
	 *
	 *     add_action( 'wc_popularity_rank_cron', 'wc_calculate_popularity_ranks' );
	 *
	 * @param array $args Optional configuration overrides.
	 * @return array|WP_Error Run summary, or WP_Error if WooCommerce is inactive.
	 */
	function wc_calculate_popularity_ranks( array $args = array() ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return new WP_Error(
				'woocommerce_inactive',
				'WooCommerce must be active to calculate popularity ranks.'
			);
		}

		$calculator = new WC_Popularity_Rank_Calculator( $args );

		return $calculator->calculate();
	}

endif;
