===========================================================================
 WooCommerce Popularity Rank Calculator
===========================================================================

A single, dependency-free PHP script that calculates a 0.0-100.0
"popularity rank" score for your WooCommerce products from recent order
data, and stores it in product meta under `_popularity_rank_score`.

The score is designed to be exported later as a product feed attribute
(for example Google Merchant Center's popularity_rank), which expects a
value that reflects a product's popularity relative to your own catalog.

This is Phase 1: the calculation script only. It does NOT add an admin
screen, a settings page, a product edit field, or any feed-export logic.


---------------------------------------------------------------------------
 WHAT IT DOES
---------------------------------------------------------------------------

1. Reads your WooCommerce orders from the last 90 days (configurable).
   Only orders in these statuses count as a real sale:
       - Processing   (wc-processing)
       - Completed    (wc-completed)
       - On hold      (wc-on-hold)

2. For every product, it adds up two numbers from those orders:
       - Recent revenue (net line total, after discounts, excl. tax)
       - Recent quantity sold
   Sales from product variations are rolled up into their parent product.

3. It normalizes both numbers across your whole catalog to a 0-100 scale,
   then blends them into one raw score:
       - 70% weight on revenue
       - 30% weight on quantity

4. It converts that raw score into a percentile rank across all products
   that sold. Your best seller lands at 100.0, and everything else is
   ranked relative to it. The final value is rounded to one decimal.

5. It saves the result to each product using WooCommerce's own product
   methods (wc_get_product -> update_meta_data -> save), so WooCommerce
   hooks and caches stay consistent. It never writes post meta directly.

The script is HPOS-safe (works with WooCommerce's High-Performance Order
Storage as well as the legacy order tables) and reads orders in batches,
so it is safe to run on large stores.


---------------------------------------------------------------------------
 REQUIREMENTS
---------------------------------------------------------------------------

- WordPress with WooCommerce active.
- PHP 7.0 or newer.
- A way to run a bit of PHP: a code snippets plugin, a small custom
  plugin, WP-CLI, or a functions.php include.


---------------------------------------------------------------------------
 INSTALLATION
---------------------------------------------------------------------------

Pick ONE of these:

A) Code snippets plugin (easiest)
   Paste the entire contents of woocommerce-popularity-rank.php into a new
   PHP snippet and activate it. This makes the function available; it does
   not run the calculation on its own.

B) Custom plugin
   Drop woocommerce-popularity-rank.php into a plugin folder (or require it
   from your plugin's main file):
       require_once __DIR__ . '/woocommerce-popularity-rank.php';

C) functions.php
   Add to your child theme's functions.php:
       require_once get_stylesheet_directory() . '/woocommerce-popularity-rank.php';

Loading the file only DEFINES the function. Nothing is calculated until
you actually call it (see below).


---------------------------------------------------------------------------
 HOW TO RUN IT
---------------------------------------------------------------------------

Call the function once to score every recently-sold product:

    $result = wc_calculate_popularity_ranks();

It returns a small summary array:

    array(
        'products_scored' => 42,     // products that received a score
        'orders_scanned'  => 310,    // orders read
        'scores'          => array(  // product_id => score, for checking
            123 => 100.0,
            456 => 72.4,
            ...
        ),
    );

If WooCommerce is not active it returns a WP_Error instead.

It is safe to run repeatedly. Each run overwrites the single stored value
per product; it never creates duplicate data.

Run it with WP-CLI (no code changes needed):

    wp eval 'print_r( wc_calculate_popularity_ranks() );'


---------------------------------------------------------------------------
 OPTIONS
---------------------------------------------------------------------------

Pass an array to override any default:

    $result = wc_calculate_popularity_ranks( array(
        'lookback_days'  => 90,
        'order_statuses' => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
        'revenue_weight' => 0.70,
        'qty_weight'     => 0.30,
        'include_unsold' => false,
        'log_transform'  => false,
    ) );

  lookback_days   How many days of order history to consider. Default 90.

  order_statuses  Which order statuses count as a sale. Default is
                  processing, completed, and on-hold.

  revenue_weight  Share of the raw score driven by revenue. Default 0.70.
  qty_weight      Share driven by quantity sold. Default 0.30.
                  (These two should add up to 1.0.)

  include_unsold  false (default): products with no recent sales are
                  skipped and keep whatever score they already had.
                  true: every published product with no recent sales is
                  scored 0.0.

  log_transform   false (default): scoring behaves exactly as described
                  above.
                  true: revenue and quantity are log-softened before
                  normalizing. Use this if your catalog has a few
                  blockbuster products that push everything else near the
                  bottom, and you want smoother spread across 0-100.
                  It has little effect on catalogs with even sales.


---------------------------------------------------------------------------
 READING THE SCORE BACK
---------------------------------------------------------------------------

The value is stored in product meta under `_popularity_rank_score` as a
decimal string with one decimal place, e.g. "72.4".

In PHP:

    $product = wc_get_product( $product_id );
    $score   = $product->get_meta( '_popularity_rank_score' );

A feed plugin can be pointed at the `_popularity_rank_score` meta key to
map it to a custom feed attribute.


---------------------------------------------------------------------------
 RUNNING IT AUTOMATICALLY (LATER)
---------------------------------------------------------------------------

The script is built so scheduling can be bolted on without changes. For a
daily refresh, register a WP-Cron event that calls the function:

    // Schedule once (e.g. on plugin activation).
    if ( ! wp_next_scheduled( 'wc_popularity_rank_cron' ) ) {
        wp_schedule_event( time(), 'daily', 'wc_popularity_rank_cron' );
    }

    // Run the calculation when the event fires.
    add_action( 'wc_popularity_rank_cron', 'wc_calculate_popularity_ranks' );


---------------------------------------------------------------------------
 NOTES ON THE SCORE
---------------------------------------------------------------------------

The score is RELATIVE to your current catalog and the chosen time window.
It reflects how popular a product is compared to your other products right
now, which is exactly what a popularity feed attribute is meant to convey.
As sales patterns shift, scores shift with them. Re-run on a schedule to
keep the values current.


---------------------------------------------------------------------------
 FILES
---------------------------------------------------------------------------

  woocommerce-popularity-rank.php   The calculator (this readme accompanies it).
  readme.txt                        This file.
