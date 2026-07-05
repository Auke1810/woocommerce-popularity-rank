# PRD: WooCommerce Popularity Rank Script

## Goal
Create a lightweight WordPress/WooCommerce script that calculates a `popularity_rank` score for products and stores it as product meta for later use in feed exports, such as Google Merchant Center. Google documents `popularity_rank` as an optional attribute intended mainly for conversational shopping experiences and expects it to reflect a product's popularity relative to the merchant's own inventory.[cite:18]

## Scope
This phase is limited to a script only. No admin UI, no settings page, no custom product edit screen field, and no direct Merchant Center integration are required in this version.

## Problem
WooCommerce stores have sales data, but they do not natively provide a normalized 0 to 100 popularity score that can be exported as a product attribute. Google expects a popularity-based value that is accurate, relative to the merchant's own catalog, and updated when product popularity changes materially.[cite:18]

## Product Requirements
The script must:
- Run inside WordPress with WooCommerce active.
- Read recent WooCommerce order data for each product.
- Use a configurable lookback window, defaulting to 90 days.
- Include only paid or active sales states, with default order statuses: `wc-processing`, `wc-completed`, and `wc-on-hold`.
- Calculate per-product sales metrics from recent orders.
- Generate a normalized popularity score from 0.0 to 100.0.
- Store the final score in product meta under `_popularity_rank_score`.
- Save the value using WooCommerce product methods, not direct `update_post_meta()`, so WooCommerce hooks and caches remain compatible.[cite:75]
- Round the final score to one decimal place to match Google’s accepted format for `popularity_rank`.[cite:18]

## Scoring Logic
Use recent sales performance rather than lifetime `total_sales`, because Google expects the value to reflect current product popularity and be updated when popularity changes substantially.[cite:18][cite:49]

Recommended formula:
1. For each product, calculate `revenue_90d` and `qty_90d` from qualifying orders.
2. Normalize both values across the catalog to a 0 to 100 scale.
3. Compute a weighted raw score:
   - 70 percent revenue weight
   - 30 percent quantity weight
4. Convert the raw score into a percentile-style ranking across all included products.
5. Save the final score as a decimal number between 0.0 and 100.0.

## Functional Requirements
- Process simple products at minimum.
- Ignore products with no qualifying sales unless configured otherwise.
- Be safe to run repeatedly without creating duplicate data.
- Support manual execution via code hook or callable function.
- Be structured so WP-Cron scheduling can be added later.
- Store only one final value per product in `_popularity_rank_score`.

## Non-Goals
The first version should not include:
- Admin UI or settings screens
- Manual product-level editing
- Feed generation logic
- Logging dashboard
- Variable-product parent/child aggregation beyond a basic implementation
- Historical snapshots or audit trail

## Technical Notes
- WooCommerce products are stored as WordPress post objects, so the output should be saved as product meta.[cite:65][cite:70]
- WooCommerce supports product queries and product data access through its own APIs and object methods, which should be preferred for compatibility.[cite:52][cite:75]
- The script should be written so a feed plugin can later read `_popularity_rank_score` as a custom attribute.

## Acceptance Criteria
- A developer can drop the script into a custom plugin or snippet loader.
- Running the script calculates a score for recently sold products.
- Each processed product receives a `_popularity_rank_score` meta value.
- Saved values are decimal numbers from 0.0 to 100.0.
- The code uses WooCommerce product save methods.
- The script is readable, modular, and easy to extend later.

## Suggested Deliverable
A single PHP script or plugin-ready class containing:
- one main calculation function
- one helper for collecting order data
- one helper for normalization/ranking
- one helper for saving the computed score to product meta

## Prompt for Claude Code
Build a WordPress/WooCommerce PHP script that calculates a product popularity rank score and stores it in WooCommerce product meta as `_popularity_rank_score`. Use recent order data from the last 90 days by default, limited to `wc-processing`, `wc-completed`, and `wc-on-hold` orders. For each product, calculate recent revenue and quantity sold, normalize both metrics across the catalog, apply a weighted score of 70 percent revenue and 30 percent quantity, convert the result into a final 0.0 to 100.0 percentile-style score, round to one decimal place, and save it using WooCommerce product methods (`wc_get_product()`, `update_meta_data()`, `save()`). Do not build admin pages, settings screens, or feed export logic in this version. Keep the code modular and plugin-ready.
