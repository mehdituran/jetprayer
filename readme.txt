=== JetPrayer - Islamic Prayer Times ===
Contributors: mehdituran
Tags: prayer times, islamic, namaz, adhan, ezan
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 1.0.0
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
*   **Full Customization (Displays Tab)**: Dynamically toggle prayer rows, change colors, adjust backgrounds, and customize border-radius separately for each layout.
*   **WordPress Color Picker Integration**: Utilizes native WordPress colors picker in the admin panel.
*   **Timetable CRUD Editor**: Manually view, edit, or customize individual prayer timings day-by-day directly in the database manager.
*   **AlAdhan API Sync**: Sync location-based calculation methods (including Diyanet Turkey, Shia Jafari, Makkah, ISNA, and more).
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

== Screenshots ==

1. Settings & Sync configuration page – Allows setting the location (City/Country or Coordinates), choosing calculation methods, and triggering database synchronization.
2. Timetable CRUD Editor page – Displays cached database rows month-by-month with options to edit timings, delete entries, and search records.
3. Layout Display Settings page – Exposes full layout customization options including visibility toggles, colors, corner radius, and advanced CSS controls.

== Changelog ==

= 1.0.0 =
*   Initial release. Features Card, Grid, Slider, Ticker, and Modal layouts.
*   Includes Settings & Sync panel with AlAdhan API sync and 60-second rate limit protection.
*   Displays tab allowing full color, layout toggles, and border-radius customization.
*   Timetable CRUD Editor Database Manager with custom row locks on sync.
*   Gutenberg Block, native Elementor widget, and responsive shortcode implementations.
*   REST API timings endpoints optimized with recursive UTF-8 sanitization and exception-safe try/catch handlers.
