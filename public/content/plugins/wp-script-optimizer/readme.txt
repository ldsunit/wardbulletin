=== WP Scripts & Styles Optimizer ===
Contributors: Riddler84
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XYR3H8B74NE4Y
Tags: scripts, styles, javascript, css, into header, into footer, optimization, customization, wp_enqueue_scripts, wp_enqueue_styles, assets, rendering, speed, output, improvement, files, faster, rendering, blocker, performance, utility, tool
Requires at least: 4.4.0
Tested up to: 4.8.2
Stable tag: 0.4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Improve your site-rendering speed by customizing all of your JavaScript- and CSS-files. Deactivate, set conditions or change positioning of files

== Description ==

Optimization of included JavaScript- and CSS-files can be a very important thing, if you want a faster site. Many WordPress plugins comes with multiple third-party and/or own JavaScript-files that are then often be included on every page of your site. In worst cases you have more than one of the same file included on your site. That slows down your site!

But also "Render blocking" can easily slow down a website. Every external JavaScript- or CSS-file, that is included in the head of your site, blocks the site from rendering, until all files are completely processed. It's a common practice to move all these Scripts and Styles (except critical CSS) to the footer of your site, so the rendering is no longer blocked and your website loads faster.

This is where WP Script Optimizer helps you! It makes it possible to control the output of every registered JavaScript or CSS-file. You can easily create rules for it or deactivate files completely. You can also move files to the footer of your site or vice versa.

Check out the following lists for all currently avaiable and planned features. If you like this plugin, please upvote and comment. That would be really helpful.

**What you can currently do with WP Script Optimizer**

* Get an overview of all frontend JavaScript and CSS files, that are included on your site (divided in categories)
* Control scripts and styles for as many single pages as you want or simply globally
* Change the positioning (Header/Footer) of specific JavaScripts / CSS or all at once
* Deactivate specific Javascripts or CSS-files completely, if not needed
* Easily create logical rules to control under which conditions a file is included or not (by use of wordpress's conditional tags)

**What you can do in future with WP Script Optimizer (planned, but not currently implemented)**

* Minify and/or encrypt JavaScript- and CSS-files
* Include your own JavaScript/CSS files or JavaScript/CSS inline code, without do it manually through functions.php
* Add extra code to any registered JavaScript or CSS-file
* Concatenate two or more (or all) files of one type (JS or CSS) to speed up loading times
* Better overview of dependents and its dependencys
* ... feel free to make suggestions :-)

**IMPORTANT - Please read:**

This plugin can not decide if a file is needed or not needed in a specific situation. Your settings have the potential to break features and functions of your site. If that is the case, you can easily change or delete your settings via the admin panel, so everything will be like it was before.

tl;dr: you have to know what you're doing ;-)

---

**If You had any problems with this plugin, please contact me, so i could fix it. Please don't write a negative review without gave me the chance to correct any issues. Thanks.**

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Move onto the 'WP Script Optimizer' page in admin panel and click on 'Update lists' in the upper right corner.
1. Thats it! Now you can start customize your scripts and styles.

== Frequently Asked Questions ==

Coming soon...

== Screenshots ==

1. Overview of global scripts and styles.
2. Overview of single page scripts and styles.
3. Avaiable Options for each file.
4. Set Conditions for any file.
5. Get scripts & styles for a single page.
6. Admin notices keeps you informed of what happened.

== Changelog ==

= 0.4.5 =
* CHANGE: Remove cURL and use WP-HTTP API instead to improve compatibility.

= 0.4.4 =
* FIX: Last update produces a bug that could potentially prevent scripts from being enqueued. This is now fixed.

= 0.4.3 =
* FIX: Localized script data will now be used dynamically to make sure data like nonces, which are being created at runtime, work properly.

= 0.4.2 =
* FIX: Add useragent to CURL requests to prevent HTTP error 403.

= 0.4.1 =
* FIX: Handles without a src-value will be registered and enqueued correctly now.
* CHANGE: The lists don't show handles without a src-value anymore. These handles don't represent real files and are only there to force other scripts or styles to be enqueued. (e.g. for backwards compatibility).

= 0.4.0 =
* Required Wordpress version raises to 4.4.0.
* Changed the upper tabs to "Global" and "Single pages".
* Scripts and styles can now be controlled on one page. Header and footer can be switched via tabs.
* Added the ability to control also single pages. They can be added and controlled via the "Single Pages" tab.
	* New pages can be added by pasting the url into the input field and click on "Get Scripts & Styles".
	* Saved pages can be found in a list on the left. After clicking a list item, the scripts & styles associated with this page will be loaded.
    * The settings for single pages can be easily synchronized with the global settings, if needed.
    * All saved pages can be updated or deleted as once or separately.
* Improved admin notices for better information about things that happen, e.g. adding/deleting entrys or theme/plugin changes.

= 0.3.0 =
* Changed the name of the plugin to "WP Scripts & Styles Optimizer".
* Major improvements in the way how files are handled. Instead of simply deregister any deactivated or conditional file, it now deregisters all files and enqueue them back if not deactivated completely or conditions didn't match. This means more control over the whole process and is a step forward with view on future features.
* CSS-files can now moved into the footer! That was only possible due to the change above.
* The Wordpress JavaScript "wp-embed" can now be changed by the plugin.
* The "jquery" handle has now an explanation text, that it's only a placeholder for "jquery-core" and "jquery-migrate".
* Added plugin version to own javascript and css files to force a reload after update (caching).

= 0.2.2 =
* Improve internal handling of default options
* Fix a problem that prints a PHP Error in some cases.

= 0.2.1 =
* Complete new UI that no longer use Wordpress's WP_List_Table class. Instead of one table list, it is now divided into lists for scripts and styles, which are itself separated in header and footer.
* Including Font-Awesome Icon-Library for styling purposes.
* Added a new option to reset every script or style to its original state. (Per item or as bulk-action)
* Added a help page to explain the plugin's functions.
* Several code improvements

= 0.1.2 =
* Fixed a bug, which triggers a PHP notice for some users

= 0.1.1 =
* Fixed some bugs

= 0.1.0 =
* Initial Release
