<?php

require_api( 'authentication_api.php' );
require_api( 'constant_inc.php' );
require_api( 'filter_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'tokens_api.php' );
require_api( 'user_api.php' );
require_api( 'user_pref_api.php' );
require_api( 'utility_api.php' );

/**
 * Returns the issue filter parameters for the current user
 *
 * @param int $p_project_id project id
 * @return mixed Active issue filter for current user or false if no filter is currently defined.
 * @access public
 */
function current_user_get_bug_filter( $p_project_id = null ) {
	$f_filter_string = gpc_get_string( 'filter', '' );
	$t_filter = '';

	if( !is_blank( $f_filter_string ) ) {
		if( is_numeric( $f_filter_string ) ) {
			$t_token = token_get_value( TOKEN_FILTER );
			if( null != $t_token ) {
				$t_filter = unserialize( $t_token );
			}
		} else {
			$t_filter = unserialize( $f_filter_string );
		}
	} else if( !filter_is_cookie_valid() ) {
		return false;
	} else {
		$t_user_id = auth_get_current_user_id();
		$t_filter = user_get_bug_filter( $t_user_id, $p_project_id );
	}

	$t_filter = filter_ensure_valid_filter( $t_filter );
	return $t_filter;
}
