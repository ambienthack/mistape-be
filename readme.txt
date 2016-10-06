=== Mistape ===
Contributors: decollete
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6UGPDDUY26MCC
Tags: mistake, mistype, spell, spelling error, report error
Requires at least: 3.9.0
Tested up to: 4.6.1
Stable tag: 1.3.1
License: GPLv2 or later

Mistape allows readers to effortlessly notify site staff about found spelling errors.

== Description ==
Strive to keep your content flawless? Mistape will help!

Let your readers notify you about spelling mistakes they may encounter. Make them feel attentive and helpful, and show your care fixing reported errors.

On Ctrl+Enter event, the plugin sends selected text along with paragraph and page URL it belongs to an email address selected in admin settings page. You can choose among administrators and editors, or specify another address.

The plugin is very lightweight. The "press Ctrl+Enter..." caption (or your text) can be configured to be automatically appended to selected post types or be inserted anywhere using a shortcode. Disabled features don't get loaded, so performance impact is minimized to the lowest notch.
Besides text, caption also can be set as image defined by URL.

Mistape is full of hooks enabling you to modify its behavior the way you like.

You can easily customize plugin in your colors and chose one from the icons near to the message in posts.

== Installation ==
1. Look up "Mistape" and and install it from plugins section of your site's admin area. Alternatively, download zip from WordPress.org Plugin Directory and extract its contents to wp-content/plugins directory.
2. Activate and follow the settings link in the notice you will see at the top. Tick desired checkboxes, save, and that's it!

All settings are stored in a single database entry and get wiped on plugin uninstall, so you can be sure your WP installation won't be clogged if you decide you don't want Mistape anymore (and we're sure you won't :) ).

== Screenshots ==
1. How it works.
2. Configuration.
3. Help.
4. Mail notification.

== Changelog ==
=1.3.1
* set of icons and a color scheme.
* support the IE & Edge.
* ability to purchase add-on with statistics and  notifications in Admin panel.
* Portuguese & French localization.

= 1.3.0
* Link to purchase a PRO version with enhanced statistics and own section in an admin panel.
* Option to choose your own color scheme for front-end.
* Set of icons for front-end notice in the end of post.
* Fixed an issue with post ID.
* Portuguese localisation added.

= 1.2.0
* new dialog box design (send action is now animated).
* introduce database table for saving reports. Used for checking for duplicates — you will not get multiple reports about the same error anymore.
* Introduce support for addons.
* (for developers) arguments for "mistape_process_report" action were changed.
* fixed textdomain plugin initialization.
* lots of improvements under the hood.

= 1.1.4 =
* fix "if post ID is determined, notify post author instead" feature functionality
* optimize AJAX calls handling

= 1.1.3 =
* remove unnecessary token check; fixes "A problem occurred while trying to deliver your report. That's all we know" issue with caching plugins enabled
* fix custom post types support
* multisite: add option for new blogs to inherit main site settings (shown only on multisite installations)
* add an option to disable Mistape caption at the bottom of post
* add hook to allow custom front end output logic (for example, disable Mistape for specific pages/categories/tags or other conditions)
* javascript: remove Classie library
* various internal code improvements

= 1.1.2 =
* set maximum z-index for dialog to evade see-through effect on some themes
* fix errors on multisite installation
* password-protected posts don't get "Ctrl+Enter" caption appended anymore
* some HTML layout fixes

= 1.1.1 =
* added an indent in front of Mistape caption to separate it from previous content. This fixed embedded objects not being processed by WordPress if they were in the end of the post content with Mistape caption enabled.

= 1.1.0 =
* added dialog modes: 1) notification, 2) confirmation, 3) confirmation with comment field (second option is the default)
* added option to override email recipient to post author if post ID is determined
* significantly improved determination of selection context
* improved email contents
* now user gets error message if submission fails
* improved specificity of css styles to avoid conflicts with themes
* disabled execution if visitor's browser is IE until selection extraction logic is implemented for it
* various fixes

= 1.0.8 =
* don't output scripts and styles when no caption displayed
* various fixes

= 1.0.7 =
* changed post type check logic to minimize caption's chance to appear in post excerpts

= 1.0.6 =
* skip mobile browsers and Internet Explorer < 11
* fixed enabled post types option behavior
* fixed dialog HTML markup

= 1.0.5 =
* fixed hide logo option saving

= 1.0.4 =
* updated Russian translation

= 1.0.3 =
* custom caption text setting
* ability to specify multiple email recipients
* added an option to display a Mistape logo in caption (enabled by default)
* shortcode fixes
* performance improvements

= 1.0.2 =
* fixed Russian translation.
* email template improvements.

= 1.0.1 =
* internal improvements.

= 1.0.0 =
* Initial release.

== Frequently Asked Questions ==
= I've successfully received a few emails and then Mistape stopped working. Why? =
Mistape implements spam-protection checks. A visitor cannot submit more than five reports in 5-minute time frame (per IP address). All subsequent reports are ignored until timeout.
So if Mistape seems to fail sending emails, and you want to test it once more, use a different internet connection, ask your friend to report something, or just wait a few minutes.

= Mistape doesn't seem to work on Internet Explorer and Microsoft Edge browser. Why ? =
Yes, since version 1.0.6 Mistape doesn't render itself if visitor's device is detected as a mobile device, and since 1.1.0 it ignores Internet Explorer and Edge completely (IE support might be added later).

= Can I customize text and style of the caption? =
Yes, hooks are available for that.

= Can I customize the appearance of confirmation dialog? =
Currently no, as this is a bit more complex feature, and plugin is light and robust.
Though, it may be implemented if there is demand. CSS styling is possible, of course.

= There is no support for my language. How to change the text users see? =
"Press Ctrl+Enter" caption can be customized in settings since version 1.0.3. The rest of strings can be translated using hooks.
We are open for contributions. If you send us a translation file (there are only about 80 strings to translate), we will give you credit in plugin description :).