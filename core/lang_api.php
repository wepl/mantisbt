<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language (Internationalization) API
 *
 * @package CoreAPI
 * @subpackage LanguageAPI
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses authentication_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses error_api.php
 * @uses plugin_api.php
 * @uses user_pref_api.php
 */

require_api( 'authentication_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'error_api.php' );
require_api( 'plugin_api.php' );
require_api( 'user_pref_api.php' );

/**
 * Determine the preferred language
 * @return string
 */
function lang_get_default() {
	$t_lang = false;

	# Confirm that the user's language can be determined
	if( function_exists( 'auth_is_user_authenticated' ) && auth_is_user_authenticated() ) {
		$t_lang = user_pref_get_language( auth_get_current_user_id() );
	}

	# Otherwise fall back to default
	if( !$t_lang ) {
		$t_lang = config_get_global( 'default_language' );
	}

	if( $t_lang == 'auto' ) {
		$t_lang = lang_map_auto();
	}

	# Remember the language
	MantisLanguage::ActiveLanguage( $t_lang );

	return $t_lang;
}

/**
 *
 * @return string
 */
function lang_map_auto() {
	$t_lang = config_get( 'fallback_language' );

	if( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
		$t_accept_langs = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
		$t_auto_map = config_get( 'language_auto_map' );

		# Expand language map
		$t_auto_map_exp = array();
		foreach( $t_auto_map as $t_encs => $t_enc_lang ) {
			$t_encs_arr = explode( ',', $t_encs );

			foreach( $t_encs_arr as $t_enc ) {
				$t_auto_map_exp[trim( $t_enc )] = $t_enc_lang;
			}
		}

		# Find encoding
		foreach( $t_accept_langs as $t_accept_lang ) {
			$t_tmp = explode( ';', utf8_strtolower( $t_accept_lang ) );

			if( isset( $t_auto_map_exp[trim( $t_tmp[0] )] ) ) {
				$t_valid_langs = config_get( 'language_choices_arr' );
				$t_found_lang = $t_auto_map_exp[trim( $t_tmp[0] )];

				if( in_array( $t_found_lang, $t_valid_langs, true ) ) {
					$t_lang = $t_found_lang;
					break;
				}
			}
		}
	}

	return $t_lang;
}

/**
* Check if the given language is valid in the current configuration.
*
* @param string $p_lang the language name
* @return boolean
*/
function lang_is_valid( $p_lang ) {
	$t_valid_langs = config_get( 'language_choices_arr' );
	$t_valid = in_array( $p_lang, $t_valid_langs, true );
	return $t_valid;
}

/**
 * language stack implementation
 * push a language onto the stack
 * @param string $p_lang
 * @return null
 */
function lang_push( $p_lang = null ) {
	MantisLanguage::Push( $p_lang );
}

/**
 * pop a language onto from stack and return it
 * @return string
 */
function lang_pop() {
	return MantisLanguage::Pop();
}

/**
 * Retrieves an internationalized string
 * This function will return one of (in order of preference):
 *  1. The string in the current user's preferred language (if defined)
 *  2. The string in English
 * @param string $p_string
 * @param string $p_lang
 * @param bool $p_error default: true - error if string not found
 * @return string
 */
function lang_get( $p_string, $p_lang = null, $p_error = true ) {
	return MantisLanguage::Get( $p_string, $p_lang, $p_error );
}

/**
 * Get language:
 * - If found, return the appropriate string (as lang_get()).
 * - If not found, no default supplied, return the supplied string as is.
 * - If not found, default supplied, return default.
 * @param string $p_string
 * @param string $p_default
 * @param string $p_lang
 * @return string
 */
function lang_get_defaulted( $p_string, $p_default = null, $p_lang = null ) {
	$t_string = lang_get( $p_string, $p_lang, false );
	if( $t_string != '' ) {
		return $t_string;
	} else {
		if( null === $p_default ) {
			return $p_string;
		} else {
			return $p_default;
		}
	}
}
