=== Cookie Setting Yes ===
Contributors: denkon
Tags: cookie consent, 152-fz, GDPR, privacy, cookies
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Local cookie and personal data consent management with audit logs and tracker blocking.

== Description ==

Cookie Setting Yes collects an explicit visitor choice for cookie categories, prevents configured scripts from loading before consent, and stores evidence of the choice in the site's own database.

Features:

* Privacy modes for Russian 152-FZ, GDPR, CCPA/CPRA, and custom requirements.
* Equal actions to accept all cookies, reject optional cookies, or choose categories.
* Necessary, functional, analytics, marketing, and external content categories.
* Consent audit log with UTC date, terms version and hash, category choice, visitor hash, user ID, and configurable IP handling.
* Blocking for scripts registered with `wp_enqueue_script()` and recognized service code printed directly before `</body>`.
* Catalog of popular Russian and international services with ready-to-use blocking rules.
* Deferred WordPress oEmbed iframes until external content consent is granted.
* Google Consent Mode v2 and WP Consent API support.
* CSV log export.
* `[cookie_setting_yes_preferences]` and `[cookie_setting_yes_cookie_policy]` shortcodes.
* Separate personal data consent with its own version, terms hash, and audit log.
* `[cookie_setting_yes_data_consent]` shortcode, Contact Form 7 `[csy_consent]` tag, and WooCommerce classic checkout and Checkout Block support.
* Setup wizard, manual test scenarios, and a personal data policy template.
* Russian and English interface selected from the WordPress locale.

All data stays in WordPress. The plugin does not use an external SaaS service.

== Development ==

Public source repository: https://github.com/Denkon2/cookie-setting-yes

== Installation ==

1. Activate the plugin on the Plugins screen.
2. Open Settings > Cookie Setting Yes.
3. Enter links to your published privacy and cookie policies.
4. Set the consent version and increase it whenever purposes, services, or consent text change.
5. For registered WordPress scripts, add one `handle=category` rule per line, for example `yandex-metrika=analytics`.
6. Add `[cookie_setting_yes_cookie_policy]` and `[cookie_setting_yes_privacy_policy]` to dedicated policy pages and fill in the operator details.
7. Add `[cookie_setting_yes_data_consent]` to a regular form, or `[csy_consent]` to a Contact Form 7 template.
8. Complete the automatic checks and relevant manual scenarios in the Checks section.

== Frequently Asked Questions ==

= Does the plugin guarantee legal compliance? =

No. The plugin provides technical tools for consent choices, blocking, and evidence. Policies, processed data, international transfers, and the applicable legal basis must be reviewed for each operator and website.

= Why is a script not being blocked? =

Scripts registered through `wp_enqueue_script()` are blocked by handle. Recognized selected services, including Yandex Metrica, can also be blocked when their code is printed directly in the theme footer. For an unrecognized service, register it through the WordPress Scripts API or use the `cookie_setting_yes_raw_script_patterns` filter.

= What is removed when the plugin is deleted? =

Settings and logs are preserved by default. Enable the data removal option before uninstalling if you want them deleted.

== Changelog ==

= 0.5.0 =

* Added Russian and English localization for the admin and visitor interfaces.
* Updated metadata and documentation for WordPress.org publication.
* Addressed Plugin Check security, escaping, naming, and database audit findings.

= 0.4.9 =

* Added animated collapsible settings sections with browser-persisted state.

= 0.4.8 =

* Fixed the two-column settings grid container and workspace nesting.

= 0.4.7 =

* Improved wide-screen layout, right-side checks and logs, and the fixed save bar.

= 0.4.6 =

* Added the wide-screen two-column workspace and grouped log actions.

= 0.4.5 =

* Added Pro log cleanup controls and clearer personal data log guidance.

= 0.4.4 =

* Added blocking for recognized raw footer scripts and related noscript pixels.

= 0.4.3 =

* Added automatic settings validation and persistent manual check scenarios.

= 0.4.2 =

* Added Pro button text colors and grouped banner positioning.

= 0.4.1 =

* Added required consent support for the WooCommerce Checkout Block.

= 0.4.0 =

* Added the setup wizard, policy template, manual checks, and Pro link.

= 0.3.0 =

* Added separate personal data consent, logging, CSV export, Contact Form 7, and WooCommerce support.

= 0.2.0 =

* Added the service catalog, Google Consent Mode v2, and WP Consent API.

= 0.1.0 =

* Initial working release.
