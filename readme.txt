=== Wp CJK Fulltext Index ===
Contributors: j100002ben
Donate link: 
Tags: fulltext, full, text, index, cjk
Requires at least: 3.1.0
Tested up to: 3.1.3
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin port the full text index search functionality from mediawiki (ver 1.19.0) to wordpress. 

== Description ==

This plugin port the full text index search functionality from mediawiki (ver 1.19.0) to wordpress.

It improve the searching speed by using MATCH AGAINST than LIKE and also solve the full text index problem with cjk (Chinese, Japanese, and Korean) words.

This plugin will create a new database table with post ID and text content.

== Installation ==

Currently this plugin will not change the original wordpress search function. 
If you like to use it there are some custom function you need to add to your theme.

1.  hook the "save_post" action to choose which post you need to store full text to this plugin.
	
	`cjkfxi_set_post($post->ID, $fulltext);`
	
	`// $fulltext can be $_POST['post_title'] or $_POST['content'] ... etc or combine each together`

1.  hook the "posts_clauses_request" filter to add additional JOIN and WHERE with the search keywords to the exist SQL pattern.
	
	`$key = get_query_var('key');`
	
	`cjkfxi_set_posts_clauses_request($clauses, $key);`

1.  Remember to remove filter in the "posts_clauses_request" filter function because you just need to run it once.

1.  The original WP_Query use SQL_CALC_FOUND_ROWS to calculate the total, if you have lots of post, you can add "no_found_rows" to WP_Query's arguments and do the count yourself by adding second parameter to hook "posts_clauses_request".

The example of the "posts_clauses_request" filter: [https://gist.github.com/2761739](https://gist.github.com/2761739)

== Changelog ==

Not yet.

== Upgrade Notice ==

Not yet.
