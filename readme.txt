=== Custom Permalinks ===

Donate link: http://michael.tyson.id.au/wordpress/plugins/custom-permalinks
Tags: permalink, url, link, address, custom, redirect
Requires at least: 2.6
Tested up to: 2.8.4
Stable tag: 0.5.2

Set custom permalinks on a per-post, per-tag or per-category basis.

== Description ==

Lay out your site the way *you* want it. Set the URL of any post, page, tag or category to anything you want.
Old permalinks will redirect properly to the new address.  Custom Permalinks gives you ultimate control
over your site structure.


== Installation ==

1. Unzip the package, and upload `custom-permalinks` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit any post, page, tag or category to set a custom permalink.

== Changelog ==

0.5.2: Bugfix for matching posts when there are multiple posts that match parts of the query

0.5.1: Compatibility fix for WP 2.7's tag/category pages

0.5: Support for Wordpress sites in subdirectories (i.e., not located at the webroot)

0.4.1: WP 2.7 compatability fixes; fix for bug encountered when publishing a draft, or reverting to draft status, and fix for placeholder permalink value for pages

0.4:  Support for pages, and a fix for draft posts/pages

0.3.1: Discovered a typo that broke categories

0.3: Largely rewritten to provide more robust handling of trailing slashes, proper support for trailing URL components (eg. paging)

0.2.2: Fixed bug with not matching permalinks when / appended to the URL, and workaround for infinite redirect when another plugin is enforcing trailing /

0.2.1: Better handling of trailing slashes

0.2: Added 'Custom Permalinks' section under 'Manage' to show existing custom permalinks, and allow reverting to the defaults

0.1.1: Fixed bug with categories
