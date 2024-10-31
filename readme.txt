=== OneID Sign In ===
Contributors: dakattak
Donate link: https://www.oneid.com/
Tags: OneID, sign in widget, admin sign in, secure sign in, sign in tool, log in tool, security, log in, authentication, two-factor authentication, two-factor, single sign on, SSO, user management, convenience, productivity
Requires at least: 3.2
Tested up to: 3.6
Stable tag: 1.1.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OneID is an easy-to-integrate plug-in that lets your users log in with a click or tap, eliminating the need for usernames and passwords.

== Description ==

OneID Sign In is a digital identity service that lets users click or tap to log into your site. It easily maps to a user's WordPress account, and with OneID you'll never again have to deal with lost or forgotten password issues, saving you time and money. This works both for the people that contribute to and administer your site, yet you still retain all the usual controls.

* OneID Sign In is quick and easy to set up on your WordPress site.
* Integration is simple: install the OneID Sign In plugin, decide where you want the Sign In button, and modify your CSS accordingly.
* Your users sign up for a OneID on your site and can map to an existing or create a new WordPress account, in no time.

**User benefits**

* OneID Sign In happens right on the WordPress sign in page, but rather than having to remember a username and password, your user just clicks to sign in.
* OneID Sign In makes it easy and convenient for users on a mobile device, just needing to tap to log into your site.
* Your users can use their OneID across all sites that accept OneID for login or checkout.
* OneID doesn't use usernames and passwords; it's a click to get into your site.
* Your customers get added security with the OneID mobile app -- letting them opt into a second factor of authentication to further protect their accounts with your site.
* OneID is FREE for your customers, and OneID Sign In is free for you.

If you use a separate CRM system to manage your user authentication, OneID can easily integrate there, too. Beyond Sign In, OneID also provides solutions to make it as easy as one click or tap to purchase from you. Learn more about OneID QuickFill and Checkout solutions at [developer.oneid.com](https://developer.oneid.com "OneID Developer Documentation").

Learn more at [oneid.com](https://www.oneid.com "OneID Home Page").

== Installation ==

1. Upload the `OneID Sign In` plugin (located in the `oneid_login` directory) to the `wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Verify your new OneID options in Settings > OneID through the WordPress dashboard.

== Frequently Asked Questions ==

= Can I fill in my own OneID API ID and Key? =

Yes. We will generate a new API ID/Key pair for you at the plugin installation so you can immediately log in with
your OneID account. However, if you are already using a working pair, feel free to replace the ones we put up.

= Nothing happens when I click the Sign In button. =

If you are the host of this WordPress site, then this most likely means that you are missing the API ID or API Key value
in the WordPress database. If either one of these values is missing, then either find the ID/Key pair that you own and
fill in the appropriate input fields located in the Settings > OneID menu page, or deactivate and reactivate the plugin
to generate a new pair. If both of these values are filled, then try erasing one (your choice) and saving the options page.
Then deactivate and reactivate your OneID plugin for a new pair. If this still doesn't work, then contact
[OneID Support](https://support.oneid.com/home "OneID Support").

If you are not the host, then you should first check that your email address was verified. To do this, look for the green
check mark next to your email attribute at the [Control Panel](https://account.oneid.com/panel/dashboard/account "Control Panel").
If you discover that your email address has already been verified, then you should contact an administrator of the WordPress
site to see if they are allowing new users to register. If you have confirmed that the admin user is allowing new users to make
an account, then please contact [OneID Support](https://support.oneid.com/home "OneID Support").

== Changelog ==

= 1.0 =
* First working version

= 1.0.1 =
* Improved the README
* Removed mixed HTML/PHP in a function that was breaking installation for some users

= 1.0.2 =
* No longer bypass "Anyone can register" WP option when logging with with OneID
* Check email verification for each sign in attempt if the option is set, not just for new users

= 1.0.3 =
* Will no longer redirect to a 404 page in some cases at sign in

= 1.0.4 =
* Fixed a bug where the error message returned 0 instead of the necessary text

= 1.1 =
* Updated the plugin descriptions
* Connected to the new OneID API
* Added a feature where a user can go to their profile page and then link/unlink their OneID account to/from their WordPress account. This way, a user can link their OneID to an already-made WordPress account, even if the emails differ. Note that creating a new link will remove that OneID link from any other Wordpress account.

= 1.1.1 =
* Fixed an error where users who used a symlink to host this plugin would not get OneID credentials
