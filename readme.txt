=== JetPrayer - Islamic Prayer Times ===
Contributors: mehdituran
Tags: prayer times, islamic, namaz, adhan, ezan
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 1.0.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A performance-optimized, secure, and highly customizable WordPress plugin to display Islamic prayer times using local database caching.

== Description ==

JetPrayer is a performance-first, modern, and highly customizable WordPress plugin designed to display Islamic prayer times. Unlike standard prayer time plugins that fetch timings via slow, external API calls on every page load, JetPrayer syncs the entire year's timetable with the AlAdhan API and caches it directly in your local WordPress database. This guarantees a blazing-fast frontend loading speed of 0ms external latency, saving server resources and eliminating API quota limits.

JetPrayer includes a beautiful, premium visual style engine with support for five interactive layouts (Cards, Responsive Grid, Dynamic Slider, Ticker, and Trigger Modal) and a complete customization panel.

### 3rd Party Service Integration
This plugin relies on the external, 3rd party service **AlAdhan API** to fetch and synchronize Islamic prayer times for your target locations.
*   **Service URL**: https://aladhan.com / https://api.aladhan.com
*   **Terms of Service**: https://aladhan.com/credits-and-terms
*   **Data Sent**: The plugin makes remote HTTP requests sending only the location credentials (city name/country name or latitude/longitude coordinates), calculation method ID, Asr school, and year. No personal user data, IP addresses, or visitor tracking data is transmitted to the external service.

### Features
*   **0ms Latency Caching**: The plugin works entirely offline on the frontend, retrieving values from a dedicated database table.
*   **5 Gorgeous Layouts**: Select between Card, Grid, Slider, Ticker, and Trigger Modal layouts.
*   **Full Customization (Displays Tab)**: Dynamically toggle prayer rows and customize layout dimensions, text alignments, padding, margins, ratios, and font-families separately for each layout.
*   **Auto-Detect & Country-Grouped Switcher**: Dynamically group synced cities by country in frontend dropdowns. Automatically resolves calculations for identical cities under different timing methods (e.g. Istanbul Diyanet vs. Istanbul MWL) and uses visitor IP geolocation to auto-select the closest synced city based on distance.
*   **Timetable CRUD Editor**: Manually view, edit, or customize individual prayer timings day-by-day directly in the database manager.
*   **AlAdhan API Sync**: Sync location-based calculation methods (including Diyanet Turkey, Shia Jafari, Makkah, ISNA, and more).
*   **Bulk Add & Sync**: Import and synchronize multiple cities and countries in bulk using a simple JSON file format, complete with real-time progress logging and cancellation controls.
*   **Gutenberg Block & Elementor Widget Integration**: Drag-and-drop the JetPrayer block (Gutenberg) or the native "JetPrayer - Prayer Times" widget (Elementor) into your editor, or use standard shortcodes.

== Installation ==

1. Upload the entire `jetprayer` folder to the `/wp-content/plugins/` directory, or upload the `jetprayer.zip` file via the WordPress plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the **JetPrayer** menu in your WordPress dashboard.
4. Configure your location settings (City/Country or Latitude/Longitude) and Calculation Method.
5. Save settings and click the **Sync Entire Year Now** button to populate the database cache.
6. Insert the shortcode, Gutenberg block, or Elementor widget into any page or widget.

== Shortcodes ==

JetPrayer provides a highly flexible shortcode with styling and layout parameters:

*   `[jetprayer layout="card"]` - Displays the prayer times inside a premium, centralized card.
*   `[jetprayer layout="grid"]` - Renders the prayer times in a responsive grid layout.
*   `[jetprayer layout="slider"]` - Puts prayer times in a touch-friendly dynamic slider/carousel.
*   `[jetprayer layout="ticker"]` - Creates an infinite scrolling ticker marquee, perfect for headers or announcements.
*   `[jetprayer layout="modal"]` - Renders today's card layout with a button that triggers a modal popup displaying the full monthly timetable.

### Attributes
*   `layout`: The layout format. Options: `card` (default), `grid`, `slider`, `ticker`, `modal`.
*   `city`: Specify the city name (e.g. `city="Istanbul"`). You can also specify a comma-separated list of multiple cities (e.g. `city="Istanbul,Ankara,Izmir"`) to limit the frontend city switcher dropdown option list. If omitted, the alphabetically first synced city for the resolved country will be loaded.
*   `country`: Specify the country name (e.g. `country="Turkey"`). If omitted, the globally synced default country or the first synced record in the database will be resolved.
*   `method`: Override the default calculation method (from Settings & Sync) for this shortcode/widget only. Use the AlAdhan Method ID, e.g. `method="13"` for Diyanet Turkey or `method="4"` for Umm Al-Qura, Makkah. Use `method="all"` or `method="any"` to automatically query and resolve whichever calculation method is synced for the target location in the database. Leave empty/omit to use the default.
*   `date`: Select the target date. Options: `today` (default), `tomorrow`, or a specific custom date in `YYYY-MM-DD` format (e.g. `date="2026-06-25"`).

### Gutenberg Block & Elementor Widget
The same `layout`, `method`, and `date` options are available as visual dropdown controls in both the Gutenberg block ("JetPrayer Times") and the Elementor widget ("JetPrayer - Prayer Times", under the "JetPrayer" category) — no shortcode typing required.

== Frequently Asked Questions ==

= How does the caching mechanism work? =
When you configure settings and click "Sync", JetPrayer downloads all 365 days of prayer timings for your selected method and location from the AlAdhan API. It stores this data in a custom MySQL table (`wp_jetprayer_times`). The front end queries this local table, resulting in instantaneous page load times without any external HTTP calls.

= Why is Jafari calculation method ID saved as 0 in the database? =
The plugin uses the official calculation method IDs defined by the AlAdhan API. In the API's standard, Shia Ithna-Ashari (Jafari) is mapped to ID `0` (e.g. Diyanet Turkey is `13`, Umm Al-Qura is `4`). Saving as `0` is correct and intended. If you select another method, it will be saved with its respective ID.

= Why do I get a 429 Too Many Requests error when syncing? =
The plugin features a 60-second rate limiter transient for the sync action. This is to protect the AlAdhan API and your website server from rate limit bans. Please wait 1 minute before running another synchronization.

= Are custom manual edits preserved during a sync? =
Yes! If you manually edit prayer times in the CRUD Database Editor, the row is marked as `is_custom = 1`. During a normal API sync, custom rows are locked and will not be overwritten by AlAdhan API data, preserving your manual adjustments.

= What is the default location and how do I change it? =
The default location is the city/country or coordinates configured and last synced in the "Settings & Sync" dashboard. This is saved as the default options values in the database, and the shortcode `[jetprayer]` will display this location by default. If you sync multiple locations, you can change the default location anytime by entering its details in the settings form and clicking "Sync".

= How does the Bulk Add & Sync feature work? =
In the Settings & Sync panel, click the "Bulk Add & Sync" button. You can upload a JSON file containing a list of countries and cities. The plugin supports three formats:
1. **Simple City List**: A list of cities (e.g. `["Istanbul", "Ankara"]`) which uses the form's default method and year.
2. **Custom Per-City**: Specifying a custom calculation method ID and year immediately following each city.
3. **Grouped with Single Method/Year**: A list of multiple cities followed by a single method and year at the end, which automatically applies those settings to all preceding cities in the group (e.g. `["Madrid", "Barcelona", "3", "2026"]`).
The plugin will download and cache all timings sequentially with real-time progress log output.

== Screenshots ==

1. Settings & Sync configuration page – Allows setting the location (City/Country or Coordinates), choosing calculation methods, and triggering database synchronization.
2. Timetable CRUD Editor page – Displays cached database rows month-by-month with options to edit timings, delete entries, and search records.
3. Layout Display Settings page – Exposes layout customization options including visibility toggles and advanced CSS controls (max width, text alignment, margins, paddings, ratios, and font families).
4. Card layout – Exposes prayer times inside a premium, glassmorphic card design.
5. Grid layout – Displays prayer times side-by-side in a responsive grid.
6. Slider layout – Slides through individual prayer times in a carousel.
7. Ticker layout – Infinite marquee scrolling text displaying today's timings.
8. Modal layout – Displays today's summary with a button to toggle the full monthly timetable modal.
9. Backup page – Allows downloading a complete or partial JSON backup of the plugin settings, customization values, and timings.

== Changelog ==

= 1.0.2 =
*   Fixed Display settings bug where font-weight values (like 600, 700) were clamped to 100 on sanitization.
*   Reduced container padding and margins on mobile to 5px for better mobile screen space utilization.
*   Added Upgrade to Pro tab in plugin settings page outlining premium features and direct customer support.
*   Fixed CSS empty spacing/gaps in headers when Hijri and Gregorian dates are toggled off.

= 1.0.1 =
*   Added Backup tab to allow downloading Partial (settings, custom timings, and locations metadata) or Full (all timings cache) JSON backups.
*   Implemented memory-safe Chunked Streaming architecture for Full Backups to prevent server timeouts and RAM limits.
*   Added mobile responsive CSS overrides to keep layout width and centering stable on mobile devices, regardless of custom max-width settings.
*   Added horizontal scroll support to monthly timetable modal on mobile screens.

= 1.0.0 =
*   Initial release. Features Card, Grid, Slider, Ticker, and Modal layouts.
*   Includes Settings & Sync panel with AlAdhan API sync and 60-second rate limit protection.
*   Displays tab allowing layout toggles, visibility controls, and advanced CSS customization.
*   Country-grouped frontend switcher with automatic IP distance-based closest location auto-detection.
*   Timetable CRUD Editor Database Manager with custom row locks on sync.
*   Gutenberg Block, native Elementor widget, and responsive shortcode implementations.
*   REST API timings endpoints optimized with recursive UTF-8 sanitization and exception-safe try/catch handlers.
