<?php
/**
 * General functions file for the plugin.
 *
 * @package Wp-CJK-Fulltext-Index
 * @subpackage Functions
 */

// don't load directly
if ( !defined('CJKFXI_VERSION') ) die('-1');

/**
 * Gets a setting from from the plugin settings in the database.
 *
 * @since 0.1
 */
function cjkfxi_get_setting( $option = '' ) {
	global $cjkfxi;

	if ( !$option )
		return false;

	if ( !isset( $cjkfxi->settings ) )
		$cjkfxi->settings = get_option( 'cjkfxi_settings' );

	if ( !is_array( $cjkfxi->settings ) || empty( $cjkfxi->settings[$option] ) )
		return false;

	return $cjkfxi->settings[$option];
}

function cjkfxi_set_setting( $option = '', $value = null ) {
	global $cjkfxi;

	if ( !$option )
		return false;

	if ( !isset( $cjkfxi->settings ) )
		$cjkfxi->settings = get_option( 'cjkfxi_settings' );

	if ( !is_array( $cjkfxi->settings ) )
		return false;
	
	$cjkfxi->settings[$option] = $value;
	update_option( 'cjkfxi_settings', $cjkfxi->settings );

	return $cjkfxi->settings[$option];
}

function cjkfxi_set_post($post_id, $fulltext){
	global $wpdb, $cjkfxi;
	
	$cjkfxi_search = new CJKFXI_Search();
	$fulltext = $cjkfxi_search->normalizeText($fulltext);
	
	return $wpdb->query( $wpdb->prepare("INSERT INTO `{$wpdb->prefix}cjkfxi` (`post_id`, `content`) 
		VALUES ( %d, %s ) ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);", $post_id, $fulltext ) );
}

function cjkfxi_set_posts_clauses_request(&$clauses, $term){
	global $wpdb;

	if(!empty($term)){
		$cjkfxi_search = new CJKFXI_Search();
		$query = $cjkfxi_search->getQuery($term, "{$wpdb->prefix}cjkfxi");
		if(!empty($query)){
			$clauses['join'] .= " LEFT JOIN `{$wpdb->prefix}cjkfxi` ON ( `{$wpdb->prefix}cjkfxi`.`post_id` = `{$wpdb->posts}`.`ID` ) ";
			$clauses['where'] .= ' AND ' . $query . ' ';
		}
	}
}

?>