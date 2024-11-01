<?php
/**
 * Basic search engine
 *
 * @file
 * @ingroup Search
 */

/**
 * @defgroup Search Search
 */

/**
 * Contain a class for special pages
 * @ingroup Search
 */
class CJKFXI_SearchEngine {
	var $limit = 10;
	var $offset = 0;
	var $searchTerms = array();
	
	function CJKFXI_SearchEngine() {
		$this->__construct();
	}
	
	function __construct() {}
	
	/**
	 * @param $string string
	 * @param $pattern string
	 * @return string
	 */
	protected static function insertSpace( $string, $pattern ) {
		$string = preg_replace( $pattern, " $1 ", $string );
		$string = preg_replace( '/ +/', ' ', $string );
		return $string;
	}
	
	/**
	 * When overridden in derived class, performs database-specific conversions
	 * on text to be used for searching or updating search index.
	 * Default implementation does nothing (simply returns $string).
	 *
	 * @param $string string: String to process
	 * @return string
	 */
	public function normalizeText( $string ) {
		$reg = "/([\\xc0-\\xff][\\x80-\\xbf]*)/";
		$s = self::insertSpace( $string, $reg );
		return $s;
	}
	
	public static function legalSearchChars() {
		return "A-Za-z_'.0-9\\x80-\\xFF\\-";
	}
	
	/**
	 * Return a 'cleaned up' search string
	 *
	 * @param $text String
	 * @return String
	 */
	function filter( $text ) {
		$lc = $this->legalSearchChars();
		return trim( preg_replace( "/[^{$lc}]/", " ", $text ) );
	}
}

