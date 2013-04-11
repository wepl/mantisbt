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
 * PHP Compatibility API
 *
 * Provides functions to assist with backwards compatibility between PHP
 * versions.
 *
 * @package CoreAPI
 * @subpackage PHPCompatibilityAPI
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

/**
 * Constant for our minimum required PHP version
 */
define( 'PHP_MIN_VERSION', '5.3.2' );

/**
 * Determine if PHP is running in CLI or CGI mode and return the mode.
 * @return int PHP mode
 */
function php_mode() {
	static $s_mode = null;

	if ( is_null( $s_mode ) ) {
		# Check to see if this is CLI mode or CGI mode
		if ( isset( $_SERVER['SERVER_ADDR'] )
			|| isset( $_SERVER['LOCAL_ADDR'] )
			|| isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$s_mode = PHP_CGI;
		} else {
			$s_mode = PHP_CLI;
		}
	}

	return $s_mode;
}

# Define a multibyte/UTF-8 aware string padding function based on PHP's
# str_pad function. IMPORTANT NOTE: "length" in this context refers to the
# number of graphemes in the string, not the number of bytes!
function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT) {
	$input_length = mb_strlen($input);
	if ($pad_length <= $input_length) {
		return $input;
	}
	$pad_characters_required = $pad_length - $input_length;
	$pad_string_length = mb_strlen($pad_string);
	$padded_string = $input;
	switch ($pad_type) {
		case STR_PAD_RIGHT:
			$repetitions = ceil($pad_length / $pad_string_length );
			$padded_string = mb_substr($input . str_repeat($pad_string, $repetitions), 0, $pad_length);
			break;
		case STR_PAD_LEFT:
			$repetitions = ceil($pad_length / $pad_string_length );
			$padded_string = mb_substr(str_repeat($pad_string, $repetitions), 0, $pad_length) . $input;
			break;
		case STR_PAD_BOTH:
			$pad_amount_left = floor($pad_length / 2);
			$pad_amount_right = ceil($pad_length / 2);
			$repetitions_left = ceil($pad_amount_left / $pad_string_length);
			$repetitions_right = ceil($pad_amount_right / $pad_string_length);
			$padding_left = mb_substr(str_repeat($pad_string, $repetitions_left), 0, $pad_amount_left);
			$padding_right = mb_substr(str_repeat($pad_string, $repetitions_right), 0, $pad_amount_right);
			$padded_string = $padding_left . $input . $padding_right;
			break;
	}
	return $padded_string;
}