# Changelog

## 3.2.1
* Removed tick_timer function as timeouts are handled via curl options.
* Switched to CURLOPT_TIMEOUT_MS option for better granularity.

## 3.2.0

* Pass-through of User Agent to gather statistics.

## 3.1.0

* Seller Ratings support.

## 3.0.0
 * Security updates:
   * No longer infer page or base URL.
   * Sanitize pagination links.
 * Adopt semver versioning.

## 2.4.0.0

* Fix sp_mt metadata value and incorrect variable names.

## 2.3.0.1

* Support gzip htm(html) download.
* bvstate support, with fragment support.
* Support for multi-byte urls.
* Fix getAggregateRating and getReviews when botnet is detected.

## 2.2.0.2

* Spotlights support.
* PHP SDK refactoring and consistent variable naming.
* Stories support.
* Charset support.
* Botnet support with configuration for timeouts for botnet requests and non-botnet requests.
