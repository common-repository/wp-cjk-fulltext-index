<?php
/*
Plugin Name: Wp CJK Fulltext Index
Plugin URI: http://wordpress.org/extend/plugins/wp-cjk-fulltext-index/
Description: This plugin port the full text index search functionality from mediawiki (ver 1.19.0) to wordpress.
Author: j100002ben (Benjamin Peng)
Version: 0.1
Author URI: 
License: GPL2
*/

/*  Copyright 2012  j100002ben (Benjamin Peng)  (email : benjamin@poka.tw)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Wp_CJK_Fulltext_Index {

	/**
	 * PHP4 constructor method.  This will be removed once the plugin only supports WordPress 3.2, 
	 * which is the version that drops PHP4 support.
	 *
	 * @since 0.1
	 */
	function Wp_CJK_Fulltext_Index() {
		$this->__construct();
	}

	/**
	 * PHP5 constructor method.
	 *
	 * @since 0.1
	 */
	function __construct() {

		/* Set the constants needed by the plugin. */
		add_action( 'plugins_loaded', array( &$this, 'constants' ), 1 );

		/* Internationalize the text strings used. */
		add_action( 'plugins_loaded', array( &$this, 'i18n' ), 2 );

		/* Load the functions files. */
		add_action( 'plugins_loaded', array( &$this, 'includes' ), 3 );

		/* Load the init files. */
		add_action( 'plugins_loaded', array( &$this, 'init' ), 4 );
		
		/* Load the admin files. */
		add_action( 'plugins_loaded', array( &$this, 'admin' ), 5 );

		/* Register activation hook. */
		register_activation_hook( __FILE__, array( &$this, 'activation' ) );
	}

	/**
	 * Defines constants used by the plugin.
	 *
	 * @since 0.1
	 */
	function constants() {

		/* Set the version number of the plugin. */
		define('CJKFXI_VERSION','1.0');

		/* Set the database version number of the plugin. */
		define('CJKFXI_DB_VERSION', 1 );

		/* Set constant path to the plugin directory. */
		define('CJKFXI_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		/* Set constant path to the plugin URL. */
		define('CJKFXI_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

		/* Set the constant path to the includes directory. */
		define('CJKFXI_INCLUDES', CJKFXI_DIR . trailingslashit( 'includes' ) );

		/* Set the constant path to the admin directory. */
		define('CJKFXI_ADMIN', CJKFXI_DIR . trailingslashit( 'admin' ) );
	}

	/**
	 * Loads the initial files needed by the plugin.
	 *
	 * @since 0.1
	 */
	function includes() {

		/* Load the plugin functions file. */
		require_once( CJKFXI_INCLUDES . 'functions.php' );

		/* Load the update functionality. */
		require_once( CJKFXI_INCLUDES . 'update.php' );
		
		/* Load the search functionality. */
		require_once( CJKFXI_INCLUDES . 'SearchEngine.php' );
		require_once( CJKFXI_INCLUDES . 'Search.php' );
		
	}

	/**
	 * Loads the translation files.
	 *
	 * @since 0.1
	 */
	function i18n() {
	}

	/**
	 * Loads the init functions and files.
	 *
	 * @since 0.1
	 */
	function init() {

		/* Load the plugin init file. */
		require_once( CJKFXI_INCLUDES . 'init.php' );
		
	}
	
	/**
	 * Loads the admin functions and files.
	 *
	 * @since 0.1
	 */
	function admin() {

		/* Only load files if in the WordPress admin. */
		if ( is_admin() ) {

			/* Load the main admin file. */
			require_once( CJKFXI_ADMIN . 'admin.php' );
		}
	}

	/**
	 * Method that runs only when the plugin is activated.
	 *
	 * @since 0.1
	 */
	function activation() {
		
		global $wpdb;
		
		$wpdb->query(<<<SQL
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cjkfxi` (
  `post_id` bigint(20) unsigned NOT NULL,
  `content` longtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`post_id`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL
);
	}
}

$wp_cjk_fulltext_index = new Wp_CJK_Fulltext_Index();
?>