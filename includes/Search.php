<?php
/**
 * MySQL search engine
 *
 * Copyright (C) 2004 Brion Vibber <brion@pobox.com>
 * http://www.mediawiki.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Search
 */

/**
 * Search engine hook for MySQL 4+
 * @ingroup Search
 */
class CJKFXI_Search extends CJKFXI_SearchEngine {
	var $strictMatching = true;
	static $mMinSearchLength;

	/**
	 * Creates an instance of this class
	 */
	function __construct( ) {
		parent::__construct( );
	}
	
	/**
	 * convert double-width roman characters to single-width.
	 * range: ff00-ff5f ~= 0020-007f
	 *
	 * @param $string string
	 *
	 * @return string
	 */
	protected static function convertDoubleWidth( $string ) {
		static $full = null;
		static $half = null;

		if ( $full === null ) {
			$fullWidth = "０１２３４５６７８９ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ";
			$halfWidth = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
			$full = str_split( $fullWidth, 3 );
			$half = str_split( $halfWidth );
		}

		$string = str_replace( $full, $half, $string );
		return $string;
	}
	
	/**
	 * Eventually this should be a word segmentation;
	 * for now just treat each character as a word.
	 * @todo FIXME: Only do this for Han characters...
	 *
	 * @param $string string
	 *
	 * @return string
	 */
	function segmentByWord( $string ) {
		$reg = "/([\\xc0-\\xff][\\x80-\\xbf]*)/";
		$s = self::insertSpace( $string, $reg );
		return $s;
	}
	
	/**
	 * @param $s
	 * @return string
	 */
	function normalizeForSearch( $s ) {

		// Double-width roman characters
		$s = self::convertDoubleWidth( $s );
		$s = trim( $s );
		$s = $this->segmentByWord( $s );

		return $s;
	}
	
	/**
	 * @param $s string
	 *
	 * @return string
	 */
	function strencode( $s ) {
		global $wpdb;
		$wpdb->escape_by_ref( $s );
		return $s;
	}

	/**
	 * Parse the user's query and transform it into an SQL fragment which will
	 * become part of a WHERE clause
	 *
	 * @param $filteredText string
	 *
	 * @return string
	 */
	function parseQuery( $filteredText, $tableName ) {
		$lc = CJKFXI_SearchEngine::legalSearchChars(); // Minus format chars
		$searchon = '';
		$this->searchTerms = array();

		# @todo FIXME: This doesn't handle parenthetical expressions.
		$m = array();
		if( preg_match_all( '/([-+<>~]?)(([' . $lc . ']+)(\*?)|"[^"]*")/',
			  $filteredText, $m, PREG_SET_ORDER ) ) {
			foreach( $m as $bits ) {
				@list( /* all */, $modifier, $term, $nonQuoted, $wildcard ) = $bits;

				if( $nonQuoted != '' ) {
					$term = $nonQuoted;
					$quote = '';
				} else {
					$term = str_replace( '"', '', $term );
					$quote = '"';
				}

				if( $searchon !== '' ) $searchon .= ' ';
				if( $this->strictMatching && ($modifier == '') ) {
					// If we leave this out, boolean op defaults to OR which is rarely helpful.
					$modifier = '+';
				}

				// Some languages such as Serbian store the input form in the search index,
				// so we may need to search for matches in multiple writing system variants.
				$convertedVariants = $term;
				if( is_array( $convertedVariants ) ) {
					$variants = array_unique( array_values( $convertedVariants ) );
				} else {
					$variants = array( $term );
				}

				// The low-level search index does some processing on input to work
				// around problems with minimum lengths and encoding in MySQL's
				// fulltext engine.
				// For Chinese this also inserts spaces between adjacent Han characters.
				$strippedVariants = array_map(
					array( $this, 'normalizeForSearch' ),
					$variants );

				// Some languages such as Chinese force all variants to a canonical
				// form when stripping to the low-level search index, so to be sure
				// let's check our variants list for unique items after stripping.
				$strippedVariants = array_unique( $strippedVariants );

				$searchon .= $modifier;
				if( count( $strippedVariants) > 1 )
					$searchon .= '(';
				foreach( $strippedVariants as $stripped ) {
					$stripped = $this->normalizeText( $stripped );
					if( $nonQuoted && ( strpos( $stripped, ' ' ) !== false || strpos( $stripped, '-' ) !== false ) ) {
						// Hack for Chinese: we need to toss in quotes for
						// multiple-character phrases since normalizeForSearch()
						// added spaces between them to make word breaks.
						$stripped = '"' . trim( $stripped ) . '"';
					}
					$searchon .= "$quote$stripped$quote$wildcard ";
				}
				if( count( $strippedVariants) > 1 )
					$searchon .= ')';

				// Match individual terms or quoted phrase in result highlighting...
				// Note that variants will be introduced in a later stage for highlighting!
				$regexp = $this->regexTerm( $term, $wildcard );
				$this->searchTerms[] = $regexp;
			}
		} else {
			
		}

		$searchon = $this->strencode( $searchon );
		return " MATCH(`$tableName`.`content`) AGAINST('$searchon' IN BOOLEAN MODE) ";
	}

	function regexTerm( $string, $wildcard ) {

		$regex = preg_quote( $string, '/' );
		if( false ) {
			if( $wildcard ) {
				// Don't cut off the final bit!
				$regex = "\b$regex";
			} else {
				$regex = "\b$regex\b";
			}
		} else {
			// For Chinese, words may legitimately abut other words in the text literal.
			// Don't add \b boundary checks... note this could cause false positives
			// for latin chars.
		}
		return $regex;
	}

	public static function legalSearchChars() {
		return "\"*" . parent::legalSearchChars();
	}
	
	/**
	 * Construct the SQL query to do the search.
	 * The guts shoulds be constructed in queryMain()
	 * @param $term String
	 * @return Array
	 * @since 1.18 (changed)
	 */
	function getQuery( $term, $tableName ) {
		$filteredTerm = $this->filter( $term );
		return $this->parseQuery( $filteredTerm, $tableName );;
	}
	
	/**
	 * Converts some characters for MySQL's indexing to grok it correctly,
	 * and pads short words to overcome limitations.
	 */
	function normalizeText( $string ) {
		$out = parent::normalizeText( $string );

		// MySQL fulltext index doesn't grok utf-8, so we
		// need to fold cases and convert to hex
		$out = preg_replace_callback(
			"/([\\xc0-\\xff][\\x80-\\xbf]*)/",
			array( $this, 'stripForSearchCallback' ),
			$this->lc( $out ) );

		// And to add insult to injury, the default indexing
		// ignores short words... Pad them so we can pass them
		// through without reconfiguring the server...
		$minLength = $this->minSearchLength();
		if( $minLength > 1 ) {
			$n = $minLength - 1;
			$out = preg_replace(
				"/\b(\w{1,$n})\b/",
				"$1u800",
				$out );
		}

		// Periods within things like hostnames and IP addresses
		// are also important -- we want a search for "example.com"
		// or "192.168.1.1" to work sanely.
		//
		// MySQL's search seems to ignore them, so you'd match on
		// "example.wikipedia.com" and "192.168.83.1" as well.
		$out = preg_replace(
			"/(\w)\.(\w|\*)/u",
			"$1u82e$2",
			$out );

		return $out;
	}

	/**
	 * Armor a case-folded UTF-8 string to get through MySQL's
	 * fulltext search without being mucked up by funny charset
	 * settings or anything else of the sort.
	 */
	protected function stripForSearchCallback( $matches ) {
		return 'u8' . bin2hex( $matches[1] );
	}

	/**
	 * Check MySQL server's ft_min_word_len setting so we know
	 * if we need to pad short words...
	 *
	 * @return int
	 */
	protected function minSearchLength() {
		global $wpdb;
		if( is_null( self::$mMinSearchLength ) ) {
			$sql = "SHOW GLOBAL VARIABLES LIKE 'ft\\_min\\_word\\_len'";
			$result = $wpdb->get_row( $sql );
			if( $result != null && $result->Variable_name == 'ft_min_word_len' ) {
				self::$mMinSearchLength = intval( $result->Value );
			} else {
				self::$mMinSearchLength = 0;
			}
		}
		return self::$mMinSearchLength;
	}
	
	/**
	 * @param $str string
	 * @return bool
	 */
	function isMultibyte( $str ) {
		return (bool)preg_match( '/[\x80-\xff]/', $str );
	}
	
	/**
	 * @param $str string
	 * @param $first bool
	 * @return mixed|string
	 */
	function lc( $str, $first = false ) {
		if ( $first ) {
			if ( $this->isMultibyte( $str ) ) {
				return mb_strtolower( mb_substr( $str, 0, 1 ) ) . mb_substr( $str, 1 );
			} else {
				return strtolower( substr( $str, 0, 1 ) ) . substr( $str, 1 );
			}
		} else {
			return $this->isMultibyte( $str ) ? mb_strtolower( $str ) : strtolower( $str );
		}
	}

}
