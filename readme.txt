=== Plugin Name ===
Contributors: munger41
Tags: editor, chief, draft, multisite, author, stat
Requires at least: 3.5
Tested up to: 4.9
Stable tag: 4.1

Helps wordpress multisite "chief editor" to manage all drafts, comments, authors and "ready for publication" sends across the network. Also includes a calendar and full authors stats.

== Description ==

This plugin is aimed to *help the multisite wordpress editor-in-chief* in order to plan publication of posts. Both PRINT and WEB workflows.
More particularly:

* *Manage all posts* across all sites in the network : they are shown with a link to the article for quick reviewing or editing.
* *See all recent comments* accross the network of a multisite install, a link allow the user to answer directly.
* *Author stats* tab allow you to compare all authors efficiency accross the network. And give much more stats.
* *One button ready for publication* notification process in order for authors to receive their post and validate it before publication
* *Calendar*, allowing for global point of view, is available for chief editor of blog network.
* *Roles aware* allow editors to manage almost all, and contributors to only see prepared posts.
* *Custom statistics* for more precise control

= Translations =

* Serbo-Croatian : https://webhostinggeeks.com/

== Installation ==

1. Upload `chief-editor.zip` to the `/wp-content/plugins/` directory OR install with WP admin GUI at network level
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Settings available at network level : https://MY_SITE_URL/wp-admin/network/settings.php
4. Single Site settings available at : https://MY_SITE_URL/wp-admin/options-general.php?page=chief_editor_single_options
5. Dashboard available at each network site level : https://MY_SITE_URL/wp-admin/admin.php?page=chief-editor-dashboard

== Frequently Asked Questions ==

If you have questions, please [post a comment here](http://www.termel.fr/ "Support")

== Screenshots ==

1. Easily see all drafts, approved and scheduled posts across the network
2. See comments of all blogs in the same place, and get a direct link to answer
3. Complete stats per author, clic on column head to sort, then on button to trace corresponding graphs
4. Horizontal Bars author stats using ChartNew.js lib
5. Pie Chart

== Changelog ==

= 5.4.3 =
* bugfix on swal calls

= 5.4.2 =
* better system requirements checks

= 5.4.1 =
* special chief editor roles to manage posts on multisite

= 5.4.0 =
* special chief editor roles to manage posts

= 5.3.3 =
* hook refactor in order to get correct CPT menu translations

= 5.3.2 =
* better managers notifications

= 5.3.1 =
* colors defaults changed and search on coauthors

= 5.3.0 =
* print and web workflow splitted

= 5.2.7 =
* post sort fixed for 'post' type

= 5.2.6 =
* check if existing periodicals in setting

= 5.2.5 =
* time filters as setting

= 5.2.4 =
* cleaning translations

= 5.2.3 =
* much better datatable search field

= 5.2.2 =
* publish status removed by default from list

= 5.2.1 =
* bugs with media upload / swal2 integrated

= 5.2.0 =
* some bugs between network / single site settings pages fixed

= 5.1.3 =
* color scale and better status integration

= 5.1.2 =
* manage peridocial shots and allow pre desktop publishing posts to be affected to a specific shot

= 5.1.1 =
* add post builder in cc emails when sending BAT

= 5.1 = 
* better images export in XML

= 5.0 = 
* developing InDesign Connector

= 4.1.2 =
* bug fix : now includes post author in email sent

= 4.1.1 =
* prepared for Ben 

= 4.1 =
* old array use of object removed, now up to date and php7 compliant

= 4.0.5 =
* date format for custom stats set to dmY

= 4.0.4 =
* bug fixes on date and WP_Sites

= 4.0.3 =
* jqueryui upgrade to 1.12.1

= 4.0 =
* wp table list introduced

= 3.7.2 =
* Security fixes to repect wordpress plugins repo standards

= 3.7 =
* Serbo-Croatian translation by https://webhostinggeeks.com/ : thanks to them ! :)

= 3.6 =
* New settings and dashboard pages URL : please change your favorites
* Custom stats available, specify period in settings

= 3.5 =
* Double check for email recipients before sending email

= 3.4 =
* settings page moved to network settings : www.mysite.com/wp-admin/network/settings.php

= 3.3 =
* date order fixed
* scheduled text readability improved

= 3.2 =
* security improvments
* custom post types management
* chief editors management for in-press sends

= 3.0 =
* Most commented posts ever and most commented posts last month added

= 2.9.2 =
* Roles changed : Admin can see settings, Editors can see all but Settings, and special users with edit_others_posts, can review posts before published.

= 2.9.1 =
* Calendar added in order to easy scheduling of posts
* CSS image zoom
* preparing per blog chief editors

= 2.9 =
* bug fix for comments

= 2.8 =
* "For Press" automatic email sends

= 2.7 =
* compatible with default Edit Flow post statuses

= 2.4 =
* dynamic graphs added using ChartNew.js

= 2.3 =
* more authors stats added

= 2.2 =
* Authors stats added

= 2.1 =
* Single wordpress install (not multisite) ready

= 2.0 =
* Comments tab added in order to manage all recent comments accross the network
* Remove schedule functionnality because buggy

= 1.3 =
* Bug fix

= 1.2 =
* Colored lines according post status
* possibility to schedule/unschedule posts directly from chief-editor admin panel

= 1.1 =
* Translated to english
* Table layout improved

= 1.0 =
* First version