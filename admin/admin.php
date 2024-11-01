<?php
/**
 * Handles the admin setup and functions for the plugin.
 *
 * @package Wp-CJK-Fulltext-Index
 * @subpackage Admin
 */

// don't load directly
if ( !defined('CJKFXI_VERSION') ) die('-1');

/**
 * Wp-CJK-Fulltext-Index plugin nonce function.  This is to help with securely making sure forms have been processed 
 * from the correct place.
 *
 * @since 0.1
 * @param $action string Additional action to add to the nonce.
 */
function cjkfxi_get_nonce( $action = '' ) {
	if ( $action )
		return "cjkfxi-plugin-component-action_{$action}";
	else
		return "cjkfxi-plugin";
}

?>