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
 * LDAP API
 *
 * @package CoreAPI
 * @subpackage LDAPAPI
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses logging_api.php
 * @uses user_api.php
 * @uses utility_api.php
 */

require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'logging_api.php' );
require_api( 'user_api.php' );
require_api( 'utility_api.php' );

$g_cache_ldap_field = array();

/**
 * Connect and bind to the LDAP directory
 * @param string $p_binddn
 * @param string $p_password
 * @return resource or false
 */
function ldap_connect_bind( $p_binddn = '', $p_password = '' ) {
	if( !extension_loaded( 'ldap' ) ) {
		log_event( LOG_LDAP, "Error: LDAP extension missing in php" );
		throw new MantisBT\Exception\LDAP_Extension_Not_Loaded();
	}

	$t_ldap_server_cfg = config_get( 'ldap_server' );
	$t_ldap_options = config_get( 'ldap_options' );

	if( !is_array( $t_ldap_server_cfg ) ) {
		$t_ldap_servers[] = $t_ldap_server_cfg;
	} else {
		$t_ldap_servers = $t_ldap_server_cfg;
	}

	foreach( $t_ldap_servers as $t_ldap_server ) {
		log_event( LOG_LDAP, "Attempting connection to LDAP server '{$t_ldap_server}'." );
		$t_ds = @ldap_connect( $t_ldap_server );
		if ( $t_ds !== false && $t_ds > 0 ) {
			log_event( LOG_LDAP, "Connection accepted to LDAP server" );

			foreach( $t_ldap_options as $t_ldap_option => $t_ldap_value ) {
				log_event( LOG_LDAP, "Setting LDAP protocol option (PHP Constant Value) '{$t_ldap_option}' to '{$t_ldap_value}'." );
				ldap_set_option( $t_ds, $t_ldap_option, $t_ldap_value );
			}

			# If no Bind DN and Password is set, attempt to login as the configured
			#  Bind DN.
			if( is_blank( $p_binddn ) && is_blank( $p_password ) ) {
				$p_binddn = config_get( 'ldap_bind_dn', '' );
				$p_password = config_get( 'ldap_bind_passwd', '' );
			}

			if( !is_blank( $p_binddn ) && !is_blank( $p_password ) ) {
				log_event( LOG_LDAP, "Attempting bind to ldap server with username and password" );
				$t_br = @ldap_bind( $t_ds, $p_binddn, $p_password );
			} else {
				# Either the Bind DN or the Password are empty, so attempt an anonymous bind.
				log_event( LOG_LDAP, "Attempting anonymous bind to ldap server" );
				$t_br = @ldap_bind( $t_ds );
			}

			if ( $t_br ) {				
				log_event( LOG_LDAP, "bind to ldap server successful" );
				return $t_ds;
			} else {
				log_event( LOG_LDAP, "bind to ldap server failed - " . ldap_err2str( ldap_errno( $t_ds ) ) );
			}
		}
	}

	log_event( LOG_LDAP, "Connection to ldap server failed" );
	throw new MantisBT\Exception\LDAP_Server_Connect_Failed();
}

/**
 * Returns an email address from LDAP, given a userid
 * @param int $p_user_id
 * @return string
 */
function ldap_email( $p_user_id ) {
	$t_username = user_get_field( $p_user_id, 'username' );

	$t_ldap_email_field	= config_get( 'ldap_email_field' );

	$t_email = ldap_get_field_from_username( $t_username, $t_ldap_email_field );
	if ( $t_email === null ) {
		return '';
	}

	return $t_email;
}

/**
 * Gets a user's real name (common name) given the id.
 *
 * @param int $p_user_id  The user id.
 * @return string real name.
 */
function ldap_realname( $p_user_id ) {
	$t_username = user_get_field( $p_user_id, 'username' );

	$t_ldap_realname_field	= config_get( 'ldap_realname_field' );
	$t_realname = ldap_get_field_from_username( $t_username, $t_ldap_realname_field );
	if ( $t_realname === null ) {
		return '';
	}

	return $t_realname;
}

/**
 * Escapes the LDAP string to disallow injection.
 *
 * @param string $p_string The string to escape.
 * @return string The escaped string.
 */
function ldap_escape_string( $p_string ) {
	$t_find = array( '\\', '*', '(', ')', '/', "\x00" );
	$t_replace = array( '\5c', '\2a', '\28', '\29', '\2f', '\00' );

	$t_string = str_replace( $t_find, $t_replace, $p_string );

	return $t_string;
}

/**
 * Gets the value of a specific field from LDAP given the user name
 * and LDAP field name.
 *
 * @todo Implement caching by retrieving all needed information in one query.
 * @todo Implement logging to LDAP queries same way like DB queries.
 *
 * @param string $p_username The user name.
 * @param string $p_field The LDAP field name.
 * @return string The field value or null if not found.
 */
function ldap_get_field_from_username( $p_username, $p_field ) {
	global $g_cache_ldap_field;

	if( isset( $g_cache_ldap_field[ $p_username ] ) ) {
		if( !is_array( $g_cache_ldap_field[ $p_username ][$p_field] ) ) {	
			$t_value =  $g_cache_ldap_field[ $p_username ][$p_field];
		} else {
			$t_value =  $g_cache_ldap_field[ $p_username ][$p_field][0];
		}
		log_event( LOG_LDAP, "Found value '{$t_value}' for field '{$p_field}'." );
		return $t_value;
	}

	
	$t_ldap_organization	= config_get( 'ldap_organization' );
	$t_ldap_contexts		= config_get( 'ldap_contexts' );

	# Bind
	log_event( LOG_LDAP, "Binding to LDAP server" );
	$t_ds = ldap_connect_bind();
	if ( $t_ds === false ) {
		log_event( LOG_LDAP, "ldap_connect_bind() returned false." );
		return null;
	}

	$c_username = ldap_escape_string( $p_username );

	# Search
	$t_ldap_uid_field		= config_get( 'ldap_uid_field' );
	$t_ldap_realname_field 	= config_get( 'ldap_realname_field' );
	$t_ldap_email_field 	= config_get( 'ldap_email_field' );
	
	$t_search_filter        = "(&$t_ldap_organization($t_ldap_uid_field=$c_username))";
	$t_search_attrs         = array( $t_ldap_uid_field, $t_ldap_realname_field, $t_ldap_email_field, 'dn' );

	foreach( $t_ldap_contexts as $t_ldap_context => $t_ldap_subtree ) {
		log_event( LOG_LDAP, "Searching for $t_search_filter (Context $t_ldap_context)" );
		if( $t_ldap_subtree == true ) {
			$t_sr = ldap_search( $t_ds, $t_ldap_context, $t_search_filter, $t_search_attrs );
		} else {
			$t_sr = ldap_list( $t_ds, $t_ldap_context, $t_search_filter, $t_search_attrs );
		}
		if( $t_sr === false ) {
			ldap_unbind( $t_ds );
			throw new MantisBT\Exception\LDAP_Search_Failed();
			return null;
		}
	
		# Get results
		$t_info = ldap_get_entries( $t_ds, $t_sr );
		if( $t_info === false ) {
			ldap_unbind( $t_ds );
			throw new MantisBT\Exception\LDAP_Search_Failed();
			return null;
		}
		
		switch( $t_info['count'] ) {
			case 0:
				ldap_free_result( $t_sr ) ;
				continue;
			case 1:
				ldap_free_result( $t_sr );				
				$g_cache_ldap_field[ $p_username ] = $t_info[0];
		}
	}
	ldap_unbind( $t_ds );

	if( !is_array( $t_info[0][$p_field] ) ) {	
		$t_value =  $t_info[0][$p_field];
	} else {
		$t_value =  $t_info[0][$p_field][0];
	}
	log_event( LOG_LDAP, "Found value '{$t_value}' for field '{$p_field}'." );

	return $t_value;
}

/**
 * Attempt to authenticate the user against the LDAP directory
 * return true on successful authentication, false otherwise
 * @param int $p_user_id
 * @param string $p_password
 * @return bool
 */
function ldap_authenticate( $p_user_id, $p_password ) {
	# if password is empty and ldap allows anonymous login, then
	# the user will be able to login, hence, we need to check
	# for this special case.
	if ( is_blank( $p_password ) ) {
		return false;
	}

	$t_username = user_get_field( $p_user_id, 'username' );

	return ldap_authenticate_by_username( $t_username, $p_password );
}

/**
 * Authenticates an user via LDAP given the username and password.
 *
 * @param string $p_username The user name.
 * @param string $p_password The password.
 * @return true: authenticated, false: failed to authenticate.
 */
function ldap_authenticate_by_username( $p_username, $p_password ) {
	$t_dn = ldap_get_field_from_username( $p_username, 'dn' );
	$t_authenticated = false;

	if( $t_dn !== null ) {
		log_event( LOG_LDAP, "Binding to LDAP server" );
		$t_ds = ldap_connect_bind();
		if ( $t_ds === false ) {
			log_event( LOG_LDAP, "ldap_connect_bind() returned false." );
			return null;
		}

		if ( @ldap_bind( $t_ds, $t_dn, $p_password ) ) {
			$t_authenticated = true;
		}

		ldap_free_result( $t_sr );
		ldap_unbind( $t_ds );
	}

	return $t_authenticated;
}