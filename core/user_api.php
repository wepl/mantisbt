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
 * User API
 *
 * @package CoreAPI
 * @subpackage UserAPI
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses access_api.php
 * @uses authentication_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses database_api.php
 * @uses email_api.php
 * @uses error_api.php
 * @uses filter_api.php
 * @uses helper_api.php
 * @uses lang_api.php
 * @uses ldap_api.php
 * @uses project_api.php
 * @uses project_hierarchy_api.php
 * @uses string_api.php
 * @uses user_pref_api.php
 * @uses utility_api.php
 */

require_api( 'access_api.php' );
require_api( 'authentication_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'database_api.php' );
require_api( 'email_api.php' );
require_api( 'error_api.php' );
require_api( 'filter_api.php' );
require_api( 'helper_api.php' );
require_api( 'lang_api.php' );
require_api( 'ldap_api.php' );
require_api( 'project_api.php' );
require_api( 'project_hierarchy_api.php' );
require_api( 'string_api.php' );
require_api( 'user_pref_api.php' );
require_api( 'utility_api.php' );

function user_cache_array_rows( $p_user_id_array ) {
	return MantisUser::getByArray( 'id', $p_user_id_array);
}

/**
 * check to see if user exists by id
 * return true if it does, false otherwise
 *
 * Use user_cache_row() to benefit from caching if called multiple times
 * and because if the user does exist the data may well be wanted
 */
function user_exists( $p_user_id ) {
	try {
		$t_user = MantisUser::getByUserID( $p_user_id );
	} catch ( MantisBT\Exception\User_By_UserID_Not_Found $e ) {
		return false;
	}
}

/**
 * check to see if project exists by id
 * if it doesn't exist then error
 *  otherwise let execution continue undisturbed
 */
function user_ensure_exists( $p_user_id ) {
	if ( !user_exists( $p_user_id ) ) {
		throw new MantisBT\Exception\User_By_ID_Not_Found( $p_user_id );
	}
}

/**
 * Check if the realname is a valid username (does not account for uniqueness)
 * Return 0 if it is invalid, The number of matches + 1
 */
function user_is_realname_unique( $p_username, $p_realname ) {
	if ( is_blank( $p_realname ) ) {
		# don't bother checking if realname is blank
		return 1;
	}

	$p_username = trim( $p_username );
	$p_realname = trim( $p_realname );

	# allow realname to match username
	$t_duplicate_count = 0;
	if ( $p_realname !== $p_username ) {
		# check realname does not match an existing username
		#  but allow it to match the current user
		$t_target_user = user_get_id_by_name( $p_username );
		$t_other_user = user_get_id_by_name( $p_realname );
		if ( ( $t_other_user !== 0 ) && ( $t_target_user !== $t_other_user ) ) {
			return 0;
		}

		# check to see if the realname is unique
		$t_query = 'SELECT id FROM {user} WHERE realname=%s';
		$t_result = db_query_bound( $t_query, array( $p_realname ) );

		$t_users = array();
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_users[] = $t_row;
		}
		$t_duplicate_count = count( $t_users );

		if ( $t_duplicate_count > 0 ) {
			# set flags for non-unique realnames
			if ( config_get( 'differentiate_duplicates' ) ) {
				for ( $i = 0; $i < $t_duplicate_count; $i++ ) {
					$t_user_id = $t_users[$i]['id'];
					user_set_field( $t_user_id, 'duplicate_realname', ON );
				}
			}
		}
	}
	return $t_duplicate_count + 1;
}

/**
 * Check if the realname is a unique
 * Trigger an error if the username is not valid
 */
function user_ensure_realname_unique( $p_username, $p_realname ) {
	if( 1 > user_is_realname_unique( $p_username, $p_realname ) ) {
		throw new MantisBT\Exception\User_Real_Match_User();
	}
}

/**
 * return whether user is monitoring bug for the user id and bug id
 */
function user_is_monitoring_bug( $p_user_id, $p_bug_id ) {
	$query = 'SELECT COUNT(*) FROM {bug_monitor} WHERE user_id=%d AND bug_id=%d';

	$result = db_query_bound( $query, array( (int)$p_user_id, (int)$p_bug_id ) );

	if( 0 == db_result( $result ) ) {
		return false;
	} else {
		return true;
	}
}

/**
 * return true if the user has access of ADMINISTRATOR or higher, false otherwise
 */
function user_is_administrator( $p_user_id ) {
	$t_access_level = user_get_field( $p_user_id, 'access_level' );

	if( $t_access_level >= config_get_global( 'admin_site_threshold' ) ) {
		return true;
	} else {
		return false;
	}
}

/*
 * Check if a user has a protected user account.
 * Protected user accounts cannot be updated without manage_user_threshold
 * permission. If the user ID supplied is that of the anonymous user, this
 * function will always return true. The anonymous user account is always
 * considered to be protected.
 *
 * @param int $p_user_id
 * @return true: user is protected; false: user is not protected.
 * @access public
 */
function user_is_protected( $p_user_id ) {
	if( user_is_anonymous( $p_user_id ) || ON == user_get_field( $p_user_id, 'protected' ) ) {
		return true;
	}
	return false;
}

/**
 * Check if a user is the anonymous user account.
 * When anonymous logins are disabled this function will always return false.
 *
 * @param int $p_user_id
 * @return true: user is the anonymous user; false: user is not the anonymous user.
 * @access public
 */
function user_is_anonymous( $p_user_id ) {
	if( ON == config_get( 'allow_anonymous_login' ) && 
		user_get_field( $p_user_id, 'username' ) == config_get( 'anonymous_account' ) ) {
		return true;
	}
	return false;
}

/**
 * Trigger an ERROR if the user account is protected
 */
function user_ensure_unprotected( $p_user_id ) {
	if( user_is_protected( $p_user_id ) ) {
		throw new MantisBT\Exception\Protected_Account();
	}
}

/**
 * return true is the user account is enabled, false otherwise
 */
function user_is_enabled( $p_user_id ) {
	if( ON == user_get_field( $p_user_id, 'enabled' ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * count the number of users at or greater than a specific level
 */
function user_count_level( $p_level = ANYBODY ) {
	$query = 'SELECT COUNT(id) FROM {user} WHERE access_level>=%d';
	$result = db_query_bound( $query, array( (int)$p_level ) );

	# Get the list of connected users
	$t_users = db_result( $result );

	return $t_users;
}

/**
 * Create a user.
 * returns false if error, the generated cookie string if ok
 */
function user_create( $p_username, $p_password = '', $p_email = '' ) {
	user_ensure_realname_unique( $p_username, $p_realname );

	# Users are added with protected set to FALSE in order to be able to update
	# preferences.  Now set the real value of protected.
	if( $c_protected ) {
		user_set_field( $t_user_id, 'protected', 1 );
	}

	# Send notification email
	if( !is_blank( $p_email ) ) {
		$t_confirm_hash = auth_generate_confirm_hash( $t_user_id );
		email_signup( $t_user_id, $p_password, $t_confirm_hash, $p_admin_name );
	}

	return $t_cookie_string;
}

/**
 * delete project-specific user access levels.
 * returns true when successfully deleted
 */
function user_delete_project_specific_access_levels( $p_user_id ) {
	user_ensure_unprotected( $p_user_id );

	$query = 'DELETE FROM {project_user_list} WHERE user_id=%d';
	db_query_bound( $query, array( (int)$p_user_id ) );

	return true;
}

/**
 * delete a user account (account, profiles, preferences, project-specific access levels)
 * returns true when the account was successfully deleted
 */
function user_delete( $p_user_id ) {
	$c_user_id = db_prepare_int( $p_user_id );

	user_ensure_unprotected( $p_user_id );

	# Remove associated profiles
	profile_delete_all( $p_user_id );

	# Remove associated preferences
	user_pref_delete_all( $p_user_id );

	# Remove project specific access levels
	user_delete_project_specific_access_levels( $p_user_id );

	# unset non-unique realname flags if necessary
	if ( config_get( 'differentiate_duplicates' ) ) {
		$c_realname = user_get_field( $p_user_id, 'realname' );
		$t_query = "SELECT id FROM {user} WHERE realname=%s";
		$t_result = db_query_bound( $t_query, array( $c_realname ) );

		$t_users = array();
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_users[] = $t_row;
		}

		$t_user_count = count( $t_users );

		if ( $t_user_count == 2 ) {
			# unset flags if there are now only 2 unique names
			for ( $i = 0; $i < $t_user_count; $i++ ) {
				$t_user_id = $t_users[$i]['id'];
				user_set_field( $t_user_id, 'duplicate_realname', OFF );
			}
		}
	}

	# Remove account
	$t_query = "DELETE FROM {user} WHERE id=%d";
	db_query_bound( $t_query, array( $c_user_id ) );

	return true;
}

/**
 * get a user id from a username
 * return false if the username does not exist
 */
function user_get_id_by_name( $p_username ) {
	$query = 'SELECT * FROM {user} WHERE username=%s';
	$result = db_query_bound( $query, array( $p_username ) );

	$row = db_fetch_array( $result );
	if( $row ) {		
		return $row['id'];
	}
	return false;
}


/**
 * Get a user id from their real name
 */
function user_get_id_by_realname( $p_realname ) {
	global $g_cache_user;

	$query = 'SELECT * FROM {user} WHERE realname=%d';
	$result = db_query_bound( $query, array( $p_realname ) );

	$row = db_fetch_array( $result );
	
	if( !$row ) {
		return false;
	} else {
		return $row['id'];
	}
}

/**
 * return a user row
 */
function user_get_row( $p_user_id ) {
	$t_user = MantisUser::getByUserID( $p_user_id );
	return $t_user->ToArray();
}

/**
 * return the specified user field for the user id
 */
function user_get_field( $p_user_id, $p_field_name ) {
	if( NO_USER == $p_user_id ) {
		trigger_error( 'user_get_field() for NO_USER', WARNING );
		return '@null@';
	}

	$t_user = MantisUser::getByUserID( $p_user_id );
	$row = $t_user->ToArray();
	
	if( isset( $row[$p_field_name] ) ) {
		return $row[$p_field_name];
	} else {
		throw new MantisBT\Exception\DB_Field_Not_Found( $p_field_name);
		return '';
	}
}

/**
 * lookup the user's email in LDAP or the db as appropriate
 */
function user_get_email( $p_user_id ) {
	$t_email = '';
	if( ON == config_get( 'use_ldap_email' ) ) {
		$t_email = ldap_email( $p_user_id );
	}
	if( is_blank( $t_email ) ) {
		$t_email = user_get_field( $p_user_id, 'email' );
	}
	return $t_email;
}

/**
 * lookup the user's realname
 */
function user_get_realname( $p_user_id ) {
	$t_realname = '';

	if ( ON == config_get( 'use_ldap_realname' ) ) {
		$t_realname = ldap_realname( $p_user_id );
	}

	if ( is_blank( $t_realname ) ) {
		$t_realname = user_get_field( $p_user_id, 'realname' );
	}

	return $t_realname;
}

/**
 * return the username or a string "user<id>" if the user does not exist
 * if show_realname is set and real name is not empty, return it instead
 */
function user_get_name( $p_user_id ) {
	$t_user = MantisUser::getByUserID( $p_user_id );
	$row = $t_user->user_cache_row( $p_user_id );
	
	if( false == $row ) {
		return lang_get( 'prefix_for_deleted_users' ) . (int) $p_user_id;
	} else {
		if( ON == config_get( 'show_realname' ) ) {
			if( is_blank( $row['realname'] ) ) {
				return $row['username'];
			} else {
				if( isset( $row['duplicate_realname'] ) && ( ON == $row['duplicate_realname'] ) ) {
					return $row['realname'] . ' (' . $row['username'] . ')';
				} else {
					return $row['realname'];
				}
			}
		} else {
			return $row['username'];
		}
	}
}

/**
* Return the user avatar image URL
* in this first implementation, only gravatar.com avatars are supported
* @return array|bool an array( URL, width, height ) or false when the given user has no avatar
*/
function user_get_avatar( $p_user_id, $p_size = 80 ) {
	$t_email = utf8_strtolower( user_get_email( $p_user_id ) );
	if( is_blank( $t_email ) ) {
		$t_result = false;
	} else {
		$t_default_image = config_get( 'default_avatar' );
		$t_size = $p_size;

		$t_use_ssl = false;
		if( isset( $_SERVER['HTTPS'] ) && ( utf8_strtolower( $_SERVER['HTTPS'] ) != 'off' ) ) {
			$t_use_ssl = true;
		}

		if( !$t_use_ssl ) {
			$t_gravatar_domain = 'http://www.gravatar.com/';
		} else {
			$t_gravatar_domain = 'https://secure.gravatar.com/';
		}

		$t_avatar_url = $t_gravatar_domain . 'avatar/' . md5( $t_email ) . '?default=' . urlencode( $t_default_image ) . '&size=' . $t_size . '&rating=G';
		$t_result = array(
			$t_avatar_url,
			$t_size,
			$t_size,
		);
	}

	return $t_result;
}

/**
 * return the user's access level
 * account for private project and the project user lists
 */
function user_get_access_level( $p_user_id, $p_project_id = ALL_PROJECTS ) {
	$t_access_level = user_get_field( $p_user_id, 'access_level' );

	if( user_is_administrator( $p_user_id ) ) {
		return $t_access_level;
	}

	$t_project_access_level = project_get_local_user_access_level( $p_project_id, $p_user_id );

	if( false === $t_project_access_level ) {
		return $t_access_level;
	} else {
		return $t_project_access_level;
	}
}

$g_user_accessible_projects_cache = null;

/**
 * retun an array of project IDs to which the user has access
 */
function user_get_accessible_projects( $p_user_id, $p_show_disabled = false ) {
	global $g_user_accessible_projects_cache;

	if( null !== $g_user_accessible_projects_cache && auth_get_current_user_id() == $p_user_id && false == $p_show_disabled ) {
		return $g_user_accessible_projects_cache;
	}

	if( access_has_global_level( config_get( 'private_project_threshold' ), $p_user_id ) ) {
		$t_projects = project_hierarchy_get_subprojects( ALL_PROJECTS, $p_show_disabled );
	} else {
		$t_public = VS_PUBLIC;
		$t_private = VS_PRIVATE;

		$result = null;

		$query = "SELECT p.id, p.name, ph.parent_id
						  FROM {project} p
						  LEFT JOIN {project_user_list} u
						    ON p.id=u.project_id AND u.user_id=%d
						  LEFT JOIN {project_hierarchy} ph
						    ON ph.child_id = p.id
						  WHERE " . ( $p_show_disabled ? '' : ( 'p.enabled = %d AND ' ) ) . "
							( p.view_state=%d
							    OR (p.view_state=%d
								    AND
							        u.user_id=%d )
							) 
						  ORDER BY p.name";
		$result = db_query_bound( $query, ( $p_show_disabled ? array( $p_user_id, $t_public, $t_private, $p_user_id ) : array( $p_user_id, true, $t_public, $t_private, $p_user_id ) ) );

		$t_projects = array();

		while ( $row = db_fetch_array( $result ) ) {
			$t_projects[(int)$row['id']] = ( $row['parent_id'] === NULL ) ? 0 : (int)$row['parent_id'];
		}

		# prune out children where the parents are already listed. Make the list
		#  first, then prune to avoid pruning a parent before the child is found.
		$t_prune = array();
		foreach( $t_projects as $t_id => $t_parent ) {
			if(( $t_parent !== 0 ) && isset( $t_projects[$t_parent] ) ) {
				$t_prune[] = $t_id;
			}
		}
		foreach( $t_prune as $t_id ) {
			unset( $t_projects[$t_id] );
		}
		$t_projects = array_keys( $t_projects );
	}

	if( auth_get_current_user_id() == $p_user_id ) {
		$g_user_accessible_projects_cache = $t_projects;
	}

	return $t_projects;
}

$g_user_accessible_subprojects_cache = null;

/**
 * retun an array of subproject IDs of a certain project to which the user has access
 */
function user_get_accessible_subprojects( $p_user_id, $p_project_id, $p_show_disabled = false ) {
	global $g_user_accessible_subprojects_cache;

	if( null !== $g_user_accessible_subprojects_cache && auth_get_current_user_id() == $p_user_id && false == $p_show_disabled ) {
		if( isset( $g_user_accessible_subprojects_cache[$p_project_id] ) ) {
			return $g_user_accessible_subprojects_cache[$p_project_id];
		} else {
			return array();
		}
	}

	$t_public = VS_PUBLIC;
	$t_private = VS_PRIVATE;

	if( access_has_global_level( config_get( 'private_project_threshold' ), $p_user_id ) ) {
		$t_enabled_clause = $p_show_disabled ? '' : 'p.enabled = %d AND';
		$query = "SELECT DISTINCT p.id, p.name, ph.parent_id
					  FROM {project} p
					  LEFT JOIN {project_hierarchy} ph
					    ON ph.child_id = p.id
					  WHERE $t_enabled_clause
					  	 ph.parent_id IS NOT NULL
					  ORDER BY p.name";
		$result = db_query_bound( $query, ( $p_show_disabled ? null : array( true ) ) );
	} else {
		$query = "SELECT DISTINCT p.id, p.name, ph.parent_id
					  FROM {project} p
					  LEFT JOIN {project_user_list} u
					    ON p.id = u.project_id AND u.user_id=%d
					  LEFT JOIN {project_hierarchy} ph
					    ON ph.child_id = p.id
					  WHERE " . ( $p_show_disabled ? '' : ( 'p.enabled = %d AND ' ) ) . '
					  	ph.parent_id IS NOT NULL AND
						( p.view_state=%d
						    OR (p.view_state=%d
							    AND
						        u.user_id=%d )
						)
					  ORDER BY p.name';
		$result = db_query_bound( $query, ( $p_show_disabled ? array( $p_user_id, $t_public, $t_private, $p_user_id ) : array( $p_user_id, 1, $t_public, $t_private, $p_user_id ) ) );
	}

	$t_projects = array();

	while( $row = db_fetch_array( $result ) ) {
		if( !isset( $t_projects[(int)$row['parent_id']] ) ) {
			$t_projects[(int)$row['parent_id']] = array();
		}

		array_push( $t_projects[(int)$row['parent_id']], (int)$row['id'] );
	}

	if( auth_get_current_user_id() == $p_user_id ) {
		$g_user_accessible_subprojects_cache = $t_projects;
	}

	if( !isset( $t_projects[(int)$p_project_id] ) ) {
		$t_projects[(int)$p_project_id] = array();
	}

	return $t_projects[(int)$p_project_id];
}

/**
 *
 */
function user_get_all_accessible_subprojects( $p_user_id, $p_project_id ) {
	/** @todo (thraxisp) Should all top level projects be a sub-project of ALL_PROJECTS implicitly?
	 *  affects how news and some summaries are generated
	 */
	$t_todo = user_get_accessible_subprojects( $p_user_id, $p_project_id );
	$t_subprojects = array();

	while( $t_todo ) {
		$t_elem = (int)array_shift( $t_todo );
		if( !in_array( $t_elem, $t_subprojects ) ) {
			array_push( $t_subprojects, $t_elem );
			$t_todo = array_merge( $t_todo, user_get_accessible_subprojects( $p_user_id, $t_elem ) );
		}
	}

	return $t_subprojects;
}

/**
 *
 */
function user_get_all_accessible_projects( $p_user_id, $p_project_id ) {
	if( ALL_PROJECTS == $p_project_id ) {
		$t_topprojects = $t_project_ids = user_get_accessible_projects( $p_user_id );
		foreach( $t_topprojects as $t_project ) {
			$t_project_ids = array_merge( $t_project_ids, user_get_all_accessible_subprojects( $p_user_id, $t_project ) );
		}

		$t_project_ids = array_unique( $t_project_ids );
	} else {
		access_ensure_project_level( VIEWER, $p_project_id );
		$t_project_ids = user_get_all_accessible_subprojects( $p_user_id, $p_project_id );
		array_unshift( $t_project_ids, $p_project_id );
	}

	return $t_project_ids;
}

/**
 * Get a list of projects the specified user is assigned to.
 * @param int $p_user_id
 * @return array An array of projects by project id the specified user is assigned to.
 *		The array contains the id, name, view state, and project access level for the user.
 */
function user_get_assigned_projects( $p_user_id ) {
	$t_query = "SELECT DISTINCT p.id, p.name, p.view_state, u.access_level
                FROM {project} p
                LEFT JOIN {project_user_list} u
                ON p.id=u.project_id
                WHERE p.enabled = '1' AND
                    u.user_id=%d
                ORDER BY p.name";
	$t_result = db_query_bound( $t_query, array( $p_user_id ) );

	$t_projects = array();
	while( $t_row = db_fetch_array( $t_result ) ) {
		$t_project_id = $t_row['id'];
		$t_projects[$t_project_id] = $t_row;
	}
	return $t_projects;
}

/**
 * List of users that are NOT in the specified project and that are enabled
 * if no project is specified use the current project
 * also exclude any administrators
 *	@param int $p_project_id
 *	@return array List of users not assigned to the specified project
 */
function user_get_unassigned_by_project_id( $p_project_id = null ) {
    if( null === $p_project_id ) {
        $p_project_id = helper_get_current_project();
    }

    $t_adm = config_get_global( 'admin_site_threshold' );
    $t_query = "SELECT DISTINCT u.id, u.username, u.realname
                FROM {user} u
                LEFT JOIN {project_user_list} p
                ON p.user_id=u.id AND p.project_id=%d
                WHERE u.access_level<%d AND
                    u.enabled = %d AND
                    p.user_id IS NULL
                ORDER BY u.realname, u.username";
    $t_result = db_query_bound( $t_query, array( $p_project_id, $t_adm, true ) );
    $t_display = array();
    $t_sort = array();
    $t_users = array();
    $t_show_realname = ( ON == config_get( 'show_realname' ) );
    $t_sort_by_last_name = ( ON == config_get( 'sort_by_last_name' ) );

	while( $t_row = db_fetch_array( $t_result ) ) {
        $t_users[] = $t_row['id'];
        $t_user_name = string_attribute( $t_row['username'] );
        $t_sort_name = $t_user_name;
        if(( isset( $t_row['realname'] ) ) && ( $t_row['realname'] <> '' ) && $t_show_realname ) {
            $t_user_name = string_attribute( $t_row['realname'] );
            if( $t_sort_by_last_name ) {
                $t_sort_name_bits = explode( ' ', utf8_strtolower( $t_user_name ), 2 );
                $t_sort_name = ( isset( $t_sort_name_bits[1] ) ? $t_sort_name_bits[1] . ', ' : '' ) . $t_sort_name_bits[0];
            } else {
                $t_sort_name = utf8_strtolower( $t_user_name );
            }
        }
        $t_display[] = $t_user_name;
        $t_sort[] = $t_sort_name;
    }
    array_multisort( $t_sort, SORT_ASC, SORT_STRING, $t_users, $t_display );
    $t_count = count( $t_sort );
	$t_user_list = array();
    for( $i = 0;$i < $t_count; $i++ ) {
		$t_user_list[$t_users[$i]] = $t_display[$i];
    }
	return $t_user_list;
}

/**
 * return the number of open assigned bugs to a user in a project
 */
function user_get_assigned_open_bug_count( $p_user_id, $p_project_id = ALL_PROJECTS ) {
	$t_where_prj = helper_project_specific_where( $p_project_id, $p_user_id ) . ' AND';

	$t_resolved = config_get( 'bug_resolved_status_threshold' );

	$query = "SELECT COUNT(*) FROM {bug} WHERE $t_where_prj
				  		status<'$t_resolved' AND
				  		handler_id=%d";
	$result = db_query_bound( $query, array( $p_user_id ) );

	return db_result( $result );
}

/**
 * return the number of open reported bugs by a user in a project
 */
function user_get_reported_open_bug_count( $p_user_id, $p_project_id = ALL_PROJECTS ) {
	$t_where_prj = helper_project_specific_where( $p_project_id, $p_user_id ) . ' AND';

	$t_resolved = config_get( 'bug_resolved_status_threshold' );

	$query = "SELECT COUNT(*) FROM {bug} WHERE $t_where_prj
						  status<'$t_resolved' AND
						  reporter_id=%d";
	$result = db_query_bound( $query, array( $p_user_id ) );

	return db_result( $result );
}

/**
 * return a profile row
 */
function user_get_profile_row( $p_user_id, $p_profile_id ) {
	$query = "SELECT * FROM {user_profile} WHERE id=%d AND user_id=%d";
	$result = db_query_bound( $query, array( $p_profile_id, $p_user_id ) );

	$row = db_fetch_array( $result );
	if( !$row ) {
		throw new MantisBT\Exception\User_Profile_Not_Found();
	}

	return $row;
}

/**
 * Get failed login attempts
 */
function user_is_login_request_allowed( $p_user_id ) {
	$t_max_failed_login_count = config_get( 'max_failed_login_count' );
	$t_failed_login_count = user_get_field( $p_user_id, 'failed_login_count' );
	return( $t_failed_login_count < $t_max_failed_login_count || OFF == $t_max_failed_login_count );
}

/**
 * Get 'lost password' in progress attempts
 */
function user_is_lost_password_request_allowed( $p_user_id ) {
	if( OFF == config_get( 'lost_password_feature' ) ) {
		return false;
	}
	$t_max_lost_password_in_progress_count = config_get( 'max_lost_password_in_progress_count' );
	$t_lost_password_in_progress_count = user_get_field( $p_user_id, 'lost_password_request_count' );
	return( $t_lost_password_in_progress_count < $t_max_lost_password_in_progress_count || OFF == $t_max_lost_password_in_progress_count );
}

/**
 * return the bug filter parameters for the specified user
 */
function user_get_bug_filter( $p_user_id, $p_project_id = null ) {
	if( null === $p_project_id ) {
		$t_project_id = helper_get_current_project();
	} else {
		$t_project_id = $p_project_id;
	}

	$t_view_all_cookie_id = filter_db_get_project_current( $t_project_id, $p_user_id );
	$t_view_all_cookie = filter_db_get_filter( $t_view_all_cookie_id, $p_user_id );
	$t_cookie_detail = explode( '#', $t_view_all_cookie, 2 );

	if( !isset( $t_cookie_detail[1] ) ) {
		return false;
	}

	$t_filter = unserialize( $t_cookie_detail[1] );

	$t_filter = filter_ensure_valid_filter( $t_filter );

	return $t_filter;
}

/**
 * Update the last_visited field to be now
 */
function user_update_last_visit( $p_user_id ) {
	$c_user_id = db_prepare_int( $p_user_id );
	$c_value = db_now();

	$query = "UPDATE {user} SET last_visit=%d WHERE id=%d";

	db_query_bound( $query, array( $c_value, $c_user_id ) );

	return true;
}

/**
 * Increment the number of times the user has logged in
 * This function is only called from the login.php script
 */
function user_increment_login_count( $p_user_id ) {
	$query = "UPDATE {user} SET login_count=login_count+1 WHERE id=%d";

	db_query_bound( $query, array( $p_user_id ) );

	# db_query errors on failure so:
	return true;
}

/**
 * Reset to zero the failed login attempts
 */
function user_reset_failed_login_count_to_zero( $p_user_id ) {
	$query = "UPDATE {user} SET failed_login_count=0 WHERE id=%d";
	db_query_bound( $query, array( $p_user_id ) );

	return true;
}

/**
 * Increment the failed login count by 1
 */
function user_increment_failed_login_count( $p_user_id ) {
	$query = "UPDATE {user} SET failed_login_count=failed_login_count+1 WHERE id=%d";
	db_query_bound( $query, array( $p_user_id ) );

	return true;
}

/**
 * Reset to zero the 'lost password' in progress attempts
 */
function user_reset_lost_password_in_progress_count_to_zero( $p_user_id ) {
	$query = "UPDATE {user} SET lost_password_request_count=0 WHERE id=%d";
	db_query_bound( $query, array( $p_user_id ) );

	return true;
}

/**
 * Increment the failed login count by 1
 */
function user_increment_lost_password_in_progress_count( $p_user_id ) {
	$query = "UPDATE {user} SET lost_password_request_count=lost_password_request_count+1
				WHERE id=%d";
	db_query_bound( $query, array( $p_user_id ) );

	return true;
}

/**
 * Set a user field
 */
function user_set_field( $p_user_id, $p_field_name, $p_field_value ) {
	$c_user_id = db_prepare_int( $p_user_id );
	$c_field_name = db_prepare_string( $p_field_name );

	if( $p_field_name != 'protected' ) {
		user_ensure_unprotected( $p_user_id );
	}

	$query = 'UPDATE {user} SET ' . $c_field_name . '=%s WHERE id=%d';

	db_query_bound( $query, array( $p_field_value, $c_user_id ) );

	# db_query errors on failure so:
	return true;
}

/**
 *
 */
function user_set_default_project( $p_user_id, $p_project_id ) {
	return user_pref_set_pref( $p_user_id, 'default_project', (int) $p_project_id );
}

/**
 * Set the user's password to the given string, encoded as appropriate
 */
function user_set_password( $p_user_id, $p_password, $p_allow_protected = false ) {
	if( !$p_allow_protected ) {
		user_ensure_unprotected( $p_user_id );
	}

	$t_email = user_get_field( $p_user_id, 'email' );
	$t_username = user_get_field( $p_user_id, 'username' );

	# When the password is changed, invalidate the cookie to expire sessions that
	# may be active on all browsers.
	$t_seed = $t_email . $t_username;
	$c_cookie_string = auth_generate_unique_cookie_string( $t_seed );

	$c_user_id = db_prepare_int( $p_user_id );
	$c_password = auth_process_plain_password( $p_password );

	$query = 'UPDATE {user} SET password=%s, cookie_string=%s WHERE id=%d';
	db_query_bound( $query, array( $c_password, $c_cookie_string, $c_user_id ) );

	# db_query errors on failure so:
	return true;
}

/**
 * Reset the user's password
 *  Take into account the 'send_reset_password' setting
 *   - if it is ON, generate a random password and send an email
 *      (unless the second parameter is false)
 *   - if it is OFF, set the password to blank
 *  Return false if the user is protected, true if the password was
 *   successfully reset
 */
function user_reset_password( $p_user_id, $p_send_email = true ) {
	$t_protected = user_get_field( $p_user_id, 'protected' );

	# Go with random password and email it to the user
	if( ON == $t_protected ) {
		return false;
	}

	# @@@ do we want to force blank password instead of random if
	#      email notifications are turned off?
	#     How would we indicate that we had done this with a return value?
	#     Should we just have two functions? (user_reset_password_random()
	#     and user_reset_password() )?
	if(( ON == config_get( 'send_reset_password' ) ) && ( ON == config_get( 'enable_email_notification' ) ) ) {

		# Create random password
		$t_email = user_get_field( $p_user_id, 'email' );
		$t_password = auth_generate_random_password( $t_email );
		$t_password2 = auth_process_plain_password( $t_password );

		user_set_field( $p_user_id, 'password', $t_password2 );

		# Send notification email
		if( $p_send_email ) {
			$t_confirm_hash = auth_generate_confirm_hash( $p_user_id );
			email_send_confirm_hash_url( $p_user_id, $t_confirm_hash );
		}
	} else {

		# use blank password, no emailing
		$t_password = auth_process_plain_password( '' );
		user_set_field( $p_user_id, 'password', $t_password );

		# reset the failed login count because in this mode there is no emailing
		user_reset_failed_login_count_to_zero( $p_user_id );
	}

	return true;
}
