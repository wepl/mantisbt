<?php
/**
 * MantisBT - A PHP based bugtracking system
 *
 * MantisBT is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MantisBT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.
 * @link http://www.mantisbt.org
 * @package MantisBT
 */

/**
 * Mantis Language Handling Class
 */
class MantisLanguage {
	/**
	 * Cache of localization strings in the language files
	 */
	static $s_lang_strings = array();

	/**
	 * stack for language overrides
	 */
	static $s_lang_overrides = array();

	/**
	 * To be used in custom_strings_inc.php :
	 */
	static $s_active_language = '';

	/**
	 * Loads the specified language and stores it in $s_lang_strings, to be used by Get()
	 * @param string $p_lang
	 * @param string $p_dir
	 * @return null
	 */
	private static function Load( $p_lang, $p_dir = null ) {
		self::$s_active_language = $p_lang;
		if( isset( self::$s_lang_strings[$p_lang] ) && is_null( $p_dir ) ) {
			return;
		}

		if( !lang_is_valid( $p_lang ) ) {
			return;
		}

		// Step 1 - Load Requested Language file
		// @@ and if file doesn't exist???
		if( $p_dir === null ) {
			include_once( config_get( 'language_path' ) . 'strings_' . $p_lang . '.txt' );
		} else {
			if( is_file( $p_dir . 'strings_' . $p_lang . '.txt' ) ) {
				include_once( $p_dir . 'strings_' . $p_lang . '.txt' );
			}
		}

		// Step 2 - Allow overriding strings declared in the language file.
		//          custom_strings_inc.php can use $g_active_language
		// 2 formats:
		// $s_* - old format
		// $s_custom_strings array - new format
		// NOTE: it's not expected that you'd mix/merge old/new formats within this file.
		$t_custom_strings = config_get( 'custom_strings_file' ) ;
		if( file_exists( $t_custom_strings ) ) {
			# this may be loaded multiple times, once per language
			require( $t_custom_strings );		
		}
		
		// Step 3  - New Language file format
		// Language file consists of an array
		if( isset( $s_messages ) ) {
			// lang strings array entry can only be set if $p_dir is not null - i.e. in a plugin
			if( isset( self::$s_lang_strings[$p_lang] ) ) {
				if( isset( $s_custom_messages[$p_lang] ) ) {
					// Step 4 - handle merging in custom strings:
					// Possible states:
					// 4.a - new string format + new custom string format	
					self::$s_lang_strings[$p_lang] = array_replace( ((array)self::$s_lang_strings[$p_lang]), (array)$s_messages, (array)$s_custom_messages[$p_lang]);
					return;
				} else {
					self::$s_lang_strings[$p_lang] = array_replace( ((array)self::$s_lang_strings[$p_lang]), (array)$s_messages);
				}
			} else {
				// new language loaded
				self::$s_lang_strings[$p_lang] = $s_messages;
				if( isset( $s_custom_messages[$p_lang] ) ) {
					// 4.a - new string format + new custom string format	
					self::$s_lang_strings[$p_lang] = array_replace( ((array)self::$s_lang_strings[$p_lang]), (array)$s_custom_messages[$p_lang]);
					return;
				}
			}
		}

		// 4.b new string format + old custom string format
		// 4.c - old string format + old custom string format
		if( !isset( $s_messages ) || file_exists( $t_custom_strings ) ) {
			$t_vars = get_defined_vars();

			foreach( array_keys( $t_vars ) as $t_var ) {
				$t_lang_var = preg_replace( '/^s_/', '', $t_var );
				if( $t_lang_var != $t_var ) {
					self::$s_lang_strings[$p_lang][$t_lang_var] = $$t_var;
				}
				else if( 'MANTIS_ERROR' == $t_var ) {
					if( isset( self::$s_lang_strings[$p_lang][$t_lang_var] ) ) {
						foreach( $$t_var as $key => $val ) {
							self::$s_lang_strings[$p_lang][$t_lang_var][$key] = $val;
						}
					} else {
						self::$s_lang_strings[$p_lang][$t_lang_var] = $$t_var;
					}
				}
			}
			// 4.d old string format + new custom string format
			// merge new custom strings into array in same way we merge in 4.a
			if( isset( $s_custom_messages[$p_lang] ) ) {
				self::$s_lang_strings[$p_lang] = array_replace( ((array)self::$s_lang_strings[$p_lang]), (array)$s_custom_messages[$p_lang]);
			}
		}
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
     * @throws MantisBT\Exception\Language\LanguageStringNotFound
	 */
	public static function Get( $p_string, $p_lang = null, $p_error = true ) {
		# If no specific language is requested, we'll
		#  try to determine the language from the users
		#  preferences

		$t_lang = $p_lang;

		if( null === $t_lang ) {
			$t_lang = MantisLanguage::GetCurrentLanguage();
		}

		// Now we'll make sure that the requested language is loaded
		self::EnsureLoaded( $t_lang );

		// Step 1 - see if language string exists in requested language
		if( MantisLanguage::StringExists( $p_string, $t_lang ) ) {
			return self::$s_lang_strings[$t_lang][$p_string];
		} else {
			// Language string doesn't exist in requested language
			
			// Step 2 - See if language string exists in current plugin
			$t_plugin_current = plugin_get_current();
			if( !is_null( $t_plugin_current ) ) {
				// Step 3 - Plugin exists: load language file
				if( $t_lang != 'english' ) {
					MantisLanguage::Load( $t_lang, config_get( 'plugin_path' ) . $t_plugin_current . '/lang/' );
					if( MantisLanguage::StringExists( $p_string, $t_lang ) ) {
						return self::$s_lang_strings[$t_lang][$p_string];
					}
				}
				
				// Step 4 - Localised language entry didn't exist - fallback to english for plugin
				MantisLanguage::Load( 'english', config_get( 'plugin_path' ) . $t_plugin_current . '/lang/' );
				if( MantisLanguage::StringExists( $p_string, $t_lang ) ) {
					return self::$s_lang_strings[$t_lang][$p_string];
				}			
			}

			// Step 5 - string didn't exist, try fall back to english:
			if( $t_lang == 'english' ) {
				if( $p_error ) {
					throw new MantisBT\Exception\Language\LanguageStringNotFound( $p_string );
				}
				return '';
			} else {
				// if string is not found in a language other than english, then retry using the english language.
				return self::Get( $p_string, 'english' );
			}
		}
	}
	
	/**
	 * Ensures that a language file has been loaded
	 * @param string $p_lang the language name
	 * @return null
	 */
	private static function EnsureLoaded( $p_lang ) {
		if( !isset( self::$s_lang_strings[$p_lang] ) ) {
			MantisLanguage::Load( $p_lang );
		}
	}	

	/**
	 * Set the active language
	 * @param string $p_lang the language name
	 */
	public static function ActiveLanguage( $p_lang ) {
		self::$s_active_language = $p_lang;
	}
	
	/**
	 * language stack implementation
	 * push a language onto the stack
	 * @param string $p_lang
	 * @return null
	 */
	public static function Push( $p_lang = null ) {
		# If no specific language is requested, we'll
		#  try to determine the language from the users
		#  preferences
		$t_lang = $p_lang;

		if( null === $t_lang ) {
			$t_lang = config_get( 'default_language' );
		}

		# don't allow 'auto' as a language to be pushed onto the stack
		#  The results from auto are always the local user, not what the
		#  override wants, unless this is the first language setting
		if(( 'auto' == $t_lang ) && ( 0 < count( self::$s_lang_overrides ) ) ) {
			$t_lang = config_get( 'fallback_language' );
		}

		self::$s_lang_overrides[] = $t_lang;

		# Remember the language
		self::$s_active_language = $t_lang;

		# make sure it's loaded
		self::EnsureLoaded( $t_lang );
	}
	
	/**
	 * Pop a language off the stack and return it
	 * @return string
	 */
	public static function Pop() {
		return array_pop( self::$s_lang_overrides );
	}
	
	/**
	 * Check if the language string currently exists e.g. it has been loaded. If found return true, otherwise return false.
	 * @param string $p_string
	 * @param string $p_lang
	 * @return bool
	 */
	private static function StringExists( $p_string, $p_lang ) {
		return( isset( self::$s_lang_strings[$p_lang] ) && isset( self::$s_lang_strings[$p_lang][$p_string] ) );
	}

	/**
	 * return the value on top of the language stack.
	 * return default if stack is empty
	 * @return string
	 */
	private static function GetCurrentLanguage() {
		$t_count_overrides = count( self::$s_lang_overrides );
		if( $t_count_overrides > 0 ) {
			$t_lang = self::$s_lang_overrides[$t_count_overrides - 1];
		} else {
			$t_lang = lang_get_default();
		}

		return $t_lang;
	}
}