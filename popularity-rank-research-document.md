# Research Document: Google Merchant Center `popularity_rank`

## Purpose
This document provides a factual research base for writing an article about the new `popularity_rank` field in Google Merchant Center product feeds. It is intended as source material for drafting an article for aukemarketing.com with clear distinctions between what Google explicitly states, what can be inferred safely, and what should be framed as interpretation rather than fact.[cite:18][cite:30]

## Executive Overview
Google has introduced `popularity_rank` as part of a set of new conversational attributes in the Merchant Center product data specification.[cite:30] Google states that conversational attributes are designed to help AI systems and conversational agents understand products better, and says they can help customers discover product information across AI-driven surfaces such as AI Mode in Search while also enhancing traditional search experiences.[cite:30] `popularity_rank` itself is an optional attribute that represents how popular a product is relative to the merchant’s own total inventory, on a scale from 0.0 to 100.0, where higher values indicate better-performing products.[cite:18]

## What `popularity_rank` Is
Google defines `popularity_rank` as an attribute that indicates the popularity of a product, ranked as a percentage of total inventory.[cite:18] The higher the value, the better the product is performing compared with other products sold by the same merchant.[cite:18] Google says merchants can use this attribute to communicate how well their products are selling, such as recent best sellers, and to help consumers make more informed buying decisions.[cite:18]

Important nuance: Google explicitly notes that this attribute is primarily intended for conversational experiences such as AI Mode in Google Search.[cite:18] That means the field should not automatically be described as a core ranking factor for classic Shopping ads unless the article clearly labels that as speculation or interpretation rather than official guidance.[cite:18][cite:30]

## Where It Fits
`popularity_rank` belongs to Google’s new set of conversational attributes in Merchant Center.[cite:30] The six conversational attributes listed by Google are:

- `question_and_answer`
- `document_link`
- `related_product`
- `item_group_title`
- `variant_option`
- `popularity_rank` [cite:30]

Google says these attributes are completely optional and are intended to complement the main Merchant Center product data specification, not replace it.[cite:30] Google also states that adding them does not affect the approval status of existing products.[cite:30]

## Official Format and Validation Rules
Google’s official documentation for `popularity_rank` provides the following requirements:[cite:18]

- Type: Number between 0.0 and 100.0.[cite:18]
- Repeated field: No.[cite:18]
- Maximum precision: One decimal place.[cite:18]
- Percent sign: Must not be included.[cite:18]
- Accuracy requirement: The value must reflect how well the product is selling and be correctly ranked against other products in the merchant’s own inventory.[cite:18]

Google also provides format examples for different feed types:[cite:18]

- CSV/TSV example: `95.5`[cite:18]
- XML example: `<g:popularity_rank>95.5</g:popularity_rank>`[cite:18]

## What Google Recommends for Submission
Google says conversational attributes can be submitted in three ways:[cite:30]

- In the primary data source.[cite:30]
- In a supplemental data source, which Google explicitly recommends.[cite:30]
- Through the Merchant API.[cite:30]

For article writing, this is useful because it supports a practical implementation angle: merchants do not necessarily need to rebuild their primary feed to test the attribute.[cite:30] A supplemental feed can be positioned as the lowest-risk first implementation method because Google itself recommends it for conversational attributes.[cite:30]

## What Google Says About Updating It
Google’s best-practice guidance says merchants should update the value when there is a substantial change in product popularity based on recent sales.[cite:18] This sentence is important because it implies that the field should be based on recent performance, not a static lifetime popularity score.[cite:18] It also supports the argument that the field should ideally be recalculated on an ongoing basis, such as daily or weekly, rather than filled once and forgotten.[cite:18]

## Safe Interpretation of the Score
The safest interpretation is:

- The score is relative to the merchant’s own catalog, not the entire market.[cite:18]
- A higher number means the product is performing better than other products sold by that same merchant.[cite:18]
- The number is not an absolute revenue figure, sales count, or market-share number.[cite:18]
- The field is designed to communicate popularity in a machine-readable format for Google’s systems.[cite:18][cite:30]

The documentation says the product is ranked “as a percentage of total inventory,” but Google does not publish a strict mandatory formula for calculating that value.[cite:18] This is a critical editorial point: the article can explain implementation approaches, but it should not claim that Google requires one specific scoring method unless Google publishes one.[cite:18]

## What Google Does Not Explicitly Say
Google does **not** explicitly state in the cited documentation that:

- `popularity_rank` is a direct ranking factor for standard Shopping ads.[cite:18][cite:30]
- merchants must calculate the score using revenue instead of units sold, or vice versa.[cite:18]
- the score must be percentile-based rather than min-max normalized.[cite:18]
- the field guarantees improved performance.[cite:18][cite:30]

These absences matter because they shape responsible article framing. Any advice beyond the official specification should be presented as implementation guidance or best-practice interpretation, not as an official Google rule.[cite:18][cite:30]

## Recommended Editorial Framing
The article can safely state the following:

- Google has introduced a new optional Merchant Center attribute called `popularity_rank`.[cite:18][cite:30]
- It is part of the conversational attributes family aimed primarily at AI-driven shopping and conversational search experiences.[cite:18][cite:30]
- The field lets merchants express how well a product is selling relative to other items in their own catalog.[cite:18]
- It should be kept accurate and refreshed when recent sales materially change product popularity.[cite:18]
- It can be submitted via a supplemental feed or API without changing product approval status.[cite:30]

The article should be more careful with statements such as “this will become a major ranking factor” or “this will improve Shopping performance,” because those claims are not confirmed in the cited Google documentation.[cite:18][cite:30]

## Suggested Practical Explanation for Readers
A useful article section could explain that `popularity_rank` is best understood as a merchant-side popularity signal. Google wants a value between 0 and 100 that tells its systems which products are relative top sellers and which are not, based on the merchant’s own assortment.[cite:18] This makes the field especially relevant for merchants with broad catalogs, shifting trends, seasonal products, or stores that already maintain high-quality structured feeds.[cite:18][cite:30]

A practical explanation can also say that the field is a candidate for automation because merchants usually already have the needed data in ecommerce systems such as WooCommerce, Shopify, ERP tools, or feed platforms, even though Google itself does not prescribe one exact calculation method in the cited source.[cite:18]

## Implementation Options to Discuss
Because Google does not force one formula, the article can present several reasonable methods and label them as merchant-side approaches rather than official Google formulas.[cite:18]

### Option 1: Units Sold in the Last 30 to 90 Days
Use the number of units sold per product in a recent time window and convert the product’s position in the catalog into a 0 to 100 score. This approach is easy to explain and aligns closely with the idea of “how well the product is selling.”[cite:18]

### Option 2: Revenue-Based Score
Use recent product revenue instead of units sold. This can work well for higher-ticket stores where a single sale may represent meaningful demand, but it can also overvalue expensive products.[cite:18]

### Option 3: Weighted Score
Combine recent units sold and recent revenue into one composite score, then normalize it to a 0 to 100 range. This is often the best compromise for implementation, but it should be framed as a practical recommendation, not a Google requirement.[cite:18]

### Option 4: Percentile Ranking
Rank all products within the merchant’s catalog and assign a percentile-like value between 0 and 100. This matches the relative nature of the attribute and is often easier to keep consistent across large inventories.[cite:18]

## Example Language for Explaining the Math
Possible article wording:

- “Google does not publish a mandatory formula for calculating `popularity_rank`, but it does require the value to accurately reflect how well a product sells relative to the rest of your catalog.”[cite:18]
- “A practical approach is to score products based on recent sales performance, such as units sold or revenue over the last 30, 60, or 90 days, and then normalize that score to a value between 0 and 100.”[cite:18]
- “Because Google recommends updating the field when popularity changes substantially, a recurring recalculation process is more defensible than a one-time manual value.”[cite:18]

## Why Supplemental Feeds Matter
Google explicitly says conversational attributes can be added through a supplemental data source and recommends that route.[cite:30] For article structure, this is valuable because it lowers the barrier to adoption: merchants can enrich an existing feed rather than rebuilding the full product feed architecture.[cite:30] This also creates a natural angle for feed-management tooling and automation workflows.[cite:30]

## Popular Products in Merchant Center Analytics
Google also provides a “Popular products” area in Merchant Center Analytics that shows popular products and brands on Google, along with current availability for the merchant’s products.[cite:23][cite:85] This is not the same thing as `popularity_rank`, but it is relevant context because it shows Google is investing more broadly in popularity and demand visibility inside Merchant Center.[cite:23][cite:85]

This distinction matters for the article:

- `popularity_rank` is a feed attribute supplied by the merchant.[cite:18]
- Popular products in Analytics is an insight/reporting interface inside Merchant Center.[cite:23][cite:85]

The article can use this contrast to show a wider trend without claiming that Google derives one directly from the other unless official documentation confirms it.[cite:18][cite:23][cite:85]

## Risks and Caveats
A high-quality article should include caution points:

- Do not invent random values; Google requires an accurate ranking.[cite:18]
- Do not treat the field as a guaranteed performance lever; Google does not promise that in the cited documentation.[cite:18][cite:30]
- Do not duplicate information unnecessarily across conversational attributes if it already exists in `description`, `product_highlight`, or `product_detail`; Google says duplication is not needed.[cite:30]
- Do not position the field as mandatory; Google marks it as optional.[cite:18][cite:30]

## Strong Article Angles
These angles are supported by the research:

### Angle 1: “Google Is Expanding the Product Feed for AI Shopping”
This angle is well supported because Google explicitly frames conversational attributes as a way to help AI systems and conversational agents understand products better across AI-driven surfaces.[cite:30]

### Angle 2: “`popularity_rank` Turns Internal Sales Data into Feed Data”
This angle is supportable because Google requires a relative popularity score based on the merchant’s own inventory and recent sales changes.[cite:18]

### Angle 3: “Supplemental Feeds Are the Fastest Way to Test the New Attribute”
This is supported because Google recommends supplemental data sources for conversational attributes.[cite:30]

### Angle 4: “This Is an Optional Attribute with Strategic Potential, Not a Proven Silver Bullet”
This is a balanced and evidence-based framing because the field is optional and primarily intended for conversational experiences, while the documentation does not promise specific ranking lifts.[cite:18][cite:30]

## Claims That Need Careful Wording
These claims should be softened or qualified:

- “`popularity_rank` improves ad rank” should be avoided unless separately evidenced.[cite:18][cite:30]
- “Google now uses merchant popularity data as a major ranking signal everywhere” should be avoided.[cite:18][cite:30]
- “The score should always be based on revenue” should be avoided because Google does not specify that.[cite:18]
- “The field replaces other feed quality work” should be avoided because Google says conversational attributes complement the primary specification.[cite:30]

## Article Outline Suggestion

## 1. Introduction
Explain that Google Merchant Center has introduced new conversational attributes and that `popularity_rank` is one of the most actionable for merchants who already have ecommerce sales data.[cite:18][cite:30]

## 2. What Is `popularity_rank`?
Define the field using Google’s wording: a 0 to 100 value that reflects how popular a product is relative to the merchant’s own inventory.[cite:18]

## 3. Why Google Added It
Explain the AI/conversational context and Google’s framing around AI Mode and conversational discovery.[cite:18][cite:30]

## 4. How to Fill It Responsibly
Discuss recent sales windows, ranking logic, and the need for accurate merchant-side scoring without overclaiming that one formula is official.[cite:18]

## 5. How to Implement It
Explain primary feed vs supplemental feed vs Merchant API, with supplemental feed as the practical recommendation.[cite:30]

## 6. What It Probably Means for Feed Strategy
Discuss likely strategic importance while clearly separating evidence from interpretation.[cite:18][cite:30]

## 7. Conclusion
Position the field as a new optional feed enrichment layer for AI-era product data, best treated as structured sales intelligence rather than a hack.[cite:18][cite:30]

## Source Notes for the Writer
Use Google’s own documentation as the backbone of the article because it provides the strongest factual basis.[cite:18][cite:30] External commentary can add examples or industry interpretation, but any statement about Google’s official intent, formatting rules, or implementation guidance should map back to Google’s help documentation.[cite:18][cite:30]

## Key Facts Checklist
- `popularity_rank` is optional.[cite:18]
- It is mainly intended for conversational experiences such as AI Mode in Google Search.[cite:18]
- It is part of the conversational attributes set.[cite:30]
- Value must be between 0.0 and 100.0.[cite:18]
- Use at most one decimal.[cite:18]
- Do not include `%`.[cite:18]
- Value must accurately reflect performance versus the merchant’s own inventory.[cite:18]
- Update when popularity changes substantially based on recent sales.[cite:18]
- Can be submitted via primary feed, supplemental feed, or Merchant API.[cite:30]
- Google recommends supplemental data sources for conversational attributes.[cite:30]
- Adding conversational attributes does not affect approval status of existing products.[cite:30]

## Ready-to-Use Summary Paragraph
Google Merchant Center now supports `popularity_rank`, a new optional conversational attribute that lets merchants express how popular a product is relative to their own catalog on a 0.0 to 100.0 scale.[cite:18][cite:30] Google says the field is primarily intended for conversational experiences such as AI Mode in Search, requires an accurate ranking against the merchant’s own inventory, and should be updated when recent sales materially change a product’s popularity.[cite:18] Merchants can add the field via a primary feed, a supplemental feed, or the Merchant API, with Google explicitly recommending supplemental data sources for conversational attributes.[cite:30]
