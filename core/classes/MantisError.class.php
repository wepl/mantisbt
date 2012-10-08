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
 * A class that handles Mantis Errors
 *
 * @package MantisBT
 * @subpackage classes
 */
class MantisError
{
	/**
	 * Indicates previous errors
	 */
	private static $_allErrors = array();
		
	/**
	 * Indicates if an error/exception has been handled
	 * Note: this also indicates additional shutdown functions have been setup.
	 */
	private static $_handled = false;
	
	/**
	 * PHP Error constants
	 */
	private static $_errorConstants = array(
										'1'=>'E_ERROR',
										'2'=>'E_WARNING',
										'4'=>'E_PARSE',
										'8'=>'E_NOTICE',
										'16'=>'E_CORE_ERROR',
										'32'=>'E_CORE_WARNING',
										'64'=>'E_COMPILE_ERROR',
										'128'=>'E_COMPILE_WARNING',
										'256'=>'APPLICATION ERROR', 	// E_USER_ERROR
										'512'=>'APPLICATION WARNING', 	// E_USER_WARNING
										'1024'=>'E_USER_NOTICE',
										'2048'=>'E_STRICT',
										'4096'=>'E_RECOVERABLE_ERROR',
										'8192'=>'E_DEPRECATED',
										'16384'=>'E_USER_DEPRECATED',
									);

	/**
	 * Error Parameters
	 */
	private static $_parameters = array();
	
	/** 
	 * indicates if a fatal error has occured
	 */
	private static $_fatal = false;
	
	/**
	 * Init function
	 */
	public static function init(){
		if( self::$_handled === false ) {
			// first run
			register_shutdown_function(array('MantisError', 'display_errors'));
			
			self::$_handled = true;
		}
	}

	/**
	 * Exception Handler
	 * @param Exception exception
	 */
	public static function exception_handler( Exception $ex) {
		self::init();

        $errorInfo = new stdClass();
        $errorInfo->time = time();
        $errorInfo->type = 'EXCEPTION';
        $errorInfo->name = 'Mantis Exception';
        $errorInfo->code = $ex->getCode();
        $errorInfo->message = $ex->getMessage();
        $errorInfo->file = $ex->getFile();
        $errorInfo->line = $ex->getLine();
        $errorInfo->trace = $ex->getTrace();
		if( method_exists( $ex, 'getContext' ) ) {
			$errorInfo->context = $ex->getContext();
		} else {
			$errorInfo->context = null;
		}

        self::$_allErrors[] = $errorInfo;
	}

	/**
	 * Implement Error handler
	 * @param int type
	 * @param string error
	 * @param string file
	 * @param int line
	 * @param string context
	 */	
	public static function error_handler( $p_type, $p_error, $p_file, $p_line, $p_context ) {
		if (0 == error_reporting())
		{
			return;
		}

		if( substr( $p_error, 0, 7) == 'plugin_' || is_numeric( $p_error ) ) {
			$t_message = self::error_string( $p_error );
		} else {
			$t_message = $p_error;
		}

		throw new ErrorException($t_message, $p_type, /*severity*/0, $p_file, $p_line);
	}
	
	/**
	 * PHP Shutdown handler
	 */
	public static function shutdown_error_handler() { 
		$t_error = error_get_last();
		if( $t_error === null ) {
			return;
		}

		self::init();
		
		$errorInfo = new stdClass();
        $errorInfo->time = time();
        $errorInfo->type = 'ERROR_LAST';
        $errorInfo->name = isset( self::$_errorConstants[$t_error['type']] ) ? self::$_errorConstants[$t_error['type']] : 'UNKNOWN';
        $errorInfo->code = $t_error['type'];
        $errorInfo->message = $t_error['message'];
        $errorInfo->file = $t_error['file'];
        $errorInfo->line = $t_error['line'];
        $errorInfo->trace = debug_backtrace();
		$errorInfo->context = null;

        self::$_allErrors[] = $errorInfo;
	}

	/**
	 * Display errors 
	 */
	public static function display_errors() {
		# disable any further event callbacks
		if ( function_exists( 'event_clear_callbacks' ) ) {
			event_clear_callbacks();
		}

		$t_oblen = ob_get_length();
		if( self::error_handled() && $t_oblen > 0 ) {
			$t_old_contents = ob_get_contents();
		}

		$t_last = error_get_last();
		$t_fatal = false;
		
		if( isset($t_last['type']) && ( $t_last['type'] == E_PARSE ) ) {
			// FATAL error state - we'll try and get through this as quickly as possible
			self::$_fatal = true;
		}

		
		# We need to ensure compression is off - otherwise the compression headers are output.
		compress_disable();	

		# then clean the buffer, leaving output buffering on.
		if( $t_oblen > 0 ) {
			ob_clean();
		}

		echo '<?xml version="1.0" encoding="utf-8"?>';
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" >';
		echo '<head><title>Error Page</title>';
		echo '<style>table.width70		{ width: 70%;  border: solid 1px #000000; }</style></head><body>';
		echo '<p align="center"><img src="' . helper_mantis_url( 'themes/' . config_get( 'theme' ) . '/images/logo.png' ) . '" /></p>';
		echo '<hr />';

		echo '<div align="center">';
		if ( !self::$_fatal ) {
			try {
				echo lang_get( 'error_no_proceed' );
			} catch (Exception $e) {
			}
		}
		echo '<br />';

		foreach ( self::$_allErrors as $key => $errorInfo ) {
			self::display_error( $errorInfo );

			if ( $key == 0 && sizeof( self::$_allErrors ) > 1 ) {
				echo '<p>Previous non-fatal errors occurred:</p>';
			}
		}
		echo '</div>';
		
		if ( !config_get( 'show_friendly_errors' ) ) {
			if( isset( $t_old_contents ) ) {
				echo '<p>Page contents follow.</p>';
				echo '<div style="border: solid 1px black;padding: 4px"><pre>';
				echo htmlspecialchars($t_old_contents);
				echo '</pre></div>';
			}
		}
		echo '<hr /><br /><br /><br />';
		echo '</body></html>', "\n";
		exit();
	}

	/**
	 * Display single error
	 * @param object Error Object
	 */
	public static function display_error( $p_error) {
		echo '<br /><div><table class="width70" cellspacing="1">';
		echo '<tr><td class="form-title">' . $p_error->name . '</td></tr>';
		if ( !self::$_fatal ) {
			echo '<tr><td><p class="center" style="color:red">' . nl2br( lang_get_defaulted( $p_error->message ) ) . '</p></td></tr>';
		} else {
			echo '<tr><td><p class="center" style="color:red">' . nl2br( $p_error->message ) . '</p></td></tr>';
		}
		echo '<tr><td>';
		self::error_print_details( basename( $p_error->file ), $p_error->line, $p_error->context );
		echo '</td></tr>';
		if ( !config_get( 'show_friendly_errors' ) ) {
			echo '<tr><td>';
			self::error_print_stack_trace( $p_error->trace );
			echo '</td></tr>';
		}
		echo '</table></div>';
	}
	
	/**
	 * Print out the error details including context
	 * @param string $p_file
	 * @param int $p_line
	 * @param string $p_context
	 * @return null
	 */
	public static function error_print_details( $p_file, $p_line, $p_context ) {
		if ( !config_get( 'show_friendly_errors' ) ) {
		?>
			<table class="width75">
				<tr>
					<td>Filename: <?php echo htmlentities( $p_file, ENT_COMPAT, 'UTF-8' );?></td>
				</tr>
				<tr>
					<td>Line: <?php echo $p_line?></td>
				</tr>
				<tr>
					<td>
						<?php self::error_print_context( $p_context )?>
					</td>
				</tr>
			</table>
		<?php
		} else {
			if( strpos( $p_file, '.' ) !== false ) {
				$t_components = explode( '.', $p_file );
				$p_file = current( $t_components );
			}
		?>
			<table class="width75">
				<tr>
					<td>ID: <?php echo htmlentities( $p_file, ENT_COMPAT, 'UTF-8' );?>:<?php echo $p_line?></td>
				</tr>
			</table>				
		<?php
		}
	}
	
	/**
	 * Print out the variable context given
	 * @param string $p_context
	 * @return null
	 */
	public static function error_print_context( $p_context ) {
		if( !is_array( $p_context ) && !is_object( $p_context )) {
			return;
		}

		echo '<table class="width100"><tr><th>Variable</th><th>Value</th><th>Type</th></tr>';

		# print normal variables
		foreach( $p_context as $t_var => $t_val ) {
			if( !is_array( $t_val ) && !is_object( $t_val ) ) {
				$t_type = gettype( $t_val );
				$t_val = htmlentities( (string) $t_val, ENT_COMPAT, 'UTF-8' );

				# Mask Passwords
				if( strpos( $t_var, 'pass' ) !== false ) {
					$t_val = '**********';
				}

				echo '<tr><td>', $t_var, '</td><td>', $t_val, '</td><td>', $t_type, '</td></tr>', "\n";
			}
		}

		# print arrays
		foreach( $p_context as $t_var => $t_val ) {
			if( is_array( $t_val ) && ( $t_var != 'GLOBALS' ) ) {
				echo '<tr><td colspan="3"><br /><strong>', $t_var, '</strong></td></tr>';
				echo '<tr><td colspan="3">';
				self::error_print_context( $t_val );
				echo '</td></tr>';
			}
		}

		echo '</table>';
	}

	/**
	 * Print out error stack trace
	 * @param array PHP stack trace
	 */
	public static function error_print_stack_trace( $p_stack ) {
		echo '<table class="width75">';
		echo '<tr><th>Filename</th><th>Line</th><th></th><th></th><th>Function</th><th>Args</th></tr>';

		# remove the call to the error handler from the stack trace
		array_shift( $p_stack );
	

		foreach( $p_stack as $t_frame ) {
			echo '<tr>';
			echo '<td>', ( isset( $t_frame['file'] ) ? htmlentities( $t_frame['file'], ENT_COMPAT, 'UTF-8' ) : '-' ), '</td><td>', ( isset( $t_frame['line'] ) ? $t_frame['line'] : '-' ), '</td><td>', ( isset( $t_frame['class'] ) ? $t_frame['class'] : '-' ), '</td><td>', ( isset( $t_frame['type'] ) ? $t_frame['type'] : '-' ), '</td><td>', ( isset( $t_frame['function'] ) ? $t_frame['function'] : '-' ), '</td>';

			$t_args = array();
			if( isset( $t_frame['args'] ) && !empty( $t_frame['args'] ) ) {
				foreach( $t_frame['args'] as $t_value ) {
					$t_args[] = self::error_build_parameter_string( $t_value );
				}
				echo '<td>( ', htmlentities( implode( $t_args, ', ' ), ENT_COMPAT, 'UTF-8' ), ' )</td></tr>';
			} else {
				echo '<td>-</td></tr>';
			}
		}
		echo '</table>';
	}

	/**
	 * Print out part of error stacktrace
	 * @param mixed
	 * @param bool show object type
	 * @param int current depth (for infinite loop protection)
     * @return string
	 */
	public static function error_build_parameter_string( $p_param, $p_showtype = true, $p_depth = 0 ) {
		if( $p_depth++ > 10 ) {
			return '<strong>***Nesting Level Too Deep***</strong>';
		}

		if( is_array( $p_param ) ) {
			$t_results = array();

			foreach( $p_param as $t_key => $t_value ) {
				# Mask Passwords
				if( strpos( $t_key, 'pass' ) !== false ) {
					$t_value = '**********';
				}
				$t_results[] = '[' . self::error_build_parameter_string( $t_key, false, $p_depth ) . ']' . ' => ' . self::error_build_parameter_string( $t_value, false, $p_depth );
			}

			return '<Array> { ' . implode( $t_results, ', ' ) . ' }';
		}
		else if( is_object( $p_param ) ) {
			$t_results = array();

			$t_class_name = get_class( $p_param );
			$t_inst_vars = get_object_vars( $p_param );

			foreach( $t_inst_vars as $t_name => $t_value ) {
				$t_results[] = "[$t_name]" . ' => ' . self::error_build_parameter_string( $t_value, false, $p_depth );
			}

			return '<Object><' . $t_class_name . '> ( ' . implode( $t_results, ', ' ) . ' )';
		} else {
			if( $p_showtype ) {
				return '<' . gettype( $p_param ) . '>' . var_export( $p_param, true );
			} else {
				return var_export( $p_param, true );
			}
		}
	}
	
	/**
	 * Return an error string (in the current language) for the given error.
	 * @param int $p_error
	 * @return string
	 * @access public
	 */
	public static function error_string( $p_error ) {
		# We pad the parameter array to make sure that we don't get errors if
		#  the caller didn't give enough parameters for the error string
		$t_padding = array_pad( array(), 10, '' );

		if( is_numeric( $p_error ) ) {
			$t_error = lang_get( $p_error, null, false );
		} else {
			$t_error = lang_get_defaulted( $p_error );
		}

		if( $t_error == '' ) {
			return lang_get( 'missing_error_string' ) . $p_error;
		}
		
		# ripped from string_api
		$t_string = call_user_func_array( 'sprintf', array_merge( array( $t_error ), self::$_parameters, $t_padding ) );
		return preg_replace( "/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", @htmlspecialchars( $t_string, ENT_COMPAT, 'UTF-8' ) );
	}

	/**
	 * Set additional info parameters to be used when displaying the next error
	 * This function takes a variable number of parameters
	 *
	 * When writing internationalized error strings, note that you can change the
	 *  order of parameters in the string.  See the PHP manual page for the
	 *  sprintf() function for more details.
	 * @param array Arguments
	 * @access public
	 * @return null
	 */	
	public static function error_parameters( $p_args ) {
		self::$_parameters = $p_args;
	}

	/**
	 * Check if we have handled an error during this page
	 * Return true if an error has been handled, false otherwise
	 * @return bool
	 */	
	public static function error_handled() {
		return self::$_handled;
	}
	
}
?>