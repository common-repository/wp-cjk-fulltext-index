<?php
/**
 * Version check and update functionality.
 *
 * @package Wp-CJK-Fulltext-Index
 * @subpackage Includes
 */

// don't load directly
if ( !defined('CJKFXI_VERSION') ) die('-1');

/* Hook our version check to 'init'. */
add_action( 'init', 'cjkfxi_version_check' );

/**
 * Checks the version number and runs install or update functions if needed.
 *
 * @since 0.1
 */
function cjkfxi_version_check() {

	/* Get the old database version. */
	$old_db_version = get_option( 'cjkfxi_db_version' );

	/* Get the theme settings. */
	$settings = get_option( 'cjkfxi_settings' );

	/* If there is no old database version, run the install. */
	if ( empty( $old_db_version ) && false === $settings )
		cjkfxi_install();

	/* Temporary check b/c version 0.1.0 didn't have an upgrade path. */
	elseif ( empty( $old_db_version ) && !empty( $settings ) )
		cjkfxi_update();

	/* If the old version is less than the new version, run the update. */
	elseif ( intval( $old_db_version ) < intval( CJKFXI_DB_VERSION ) )
		cjkfxi_update();
}

/**
 * Adds the plugin settings on install.
 *
 * @since 0.1
 */
function cjkfxi_install() {

	/* Add the database version setting. */
	add_option( 'cjkfxi_db_version', CJKFXI_DB_VERSION );

	/* Add the default plugin settings. */
	add_option( 'cjkfxi_settings', cjkfxi_get_default_settings() );
}

/**
 * Updates plugin settings if there are new settings to add.
 *
 * @since 0.1
 */
function cjkfxi_update() {

	/* Update the database version setting. */
	update_option( 'cjkfxi_db_version', CJKFXI_DB_VERSION );

	/* Get the settings from the database. */
	$settings = get_option( 'cjkfxi_settings' );

	/* Get the default plugin settings. */
	$default_settings = cjkfxi_get_default_settings();

	/* Loop through each of the default plugin settings. */
	foreach ( $default_settings as $setting_key => $setting_value ) {

		/* If the setting didn't previously exist, add the default value to the $settings array. */
		if ( !isset( $settings[$setting_key] ) )
			$settings[$setting_key] = $setting_value;
	}

	/* Update the plugin settings. */
	update_option( 'cjkfxi_settings', $settings );
}

/**
 * Returns an array of the default plugin settings.  These are only used on initial setup.
 *
 * @since 0.1
 */
function cjkfxi_get_default_settings() {

	/* Set up the default plugin settings. */
	$settings = array(
	);

	/* Return the default settings. */
	return $settings;
}

?>