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
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.
 * @link http://www.mantisbt.org
 * @package MantisBT
 */

/**
 * Mantis User class
 *
 * @package MantisBT
 * @subpackage classes
 */
 class MantisUser extends MantisCacheable {
	/**
	 * User ID
	 */
	protected $user_id;

	/**
	 * Username
	 */
	protected $username;

	/**
	 * Real Name
	 */
	protected $realname;

	/** 
	 * Email
	 */
	protected $email;

	/**
	 * Password
	 */
	protected $password;

	/**
	 * User Account enabled flag
	 */
	protected $enabled = true;

	/**
	 * User account protected flag
	 */
	protected $protected = false;

	/**
	 * Access level
	 */
	protected $access_level;

	/**
	 * Login count
	 */
	protected $login_count = 0;

	/**
	 * Lost Password Request Count
	 */
	protected $lost_password_request_count = 0;

	/**
	 * Failed login count
	 */
	protected $failed_login_count = 0;

	/**
	 * User cookie string
	 */
	protected $cookie_string;

	/**
	 * Last visit timestamp
	 */
	protected $last_visit;

	/**
	 * Date created timestamp
	 */
	protected $date_created;

	/**
	 * Field names on user object
	 */
	static $fields = null;

	/**
	 * indicates whether we are currently loading from db - shortcuts some checks if true
	 * for performance
	 */
	private $loading = false;

	/**
	 * Constructor
	 * @param string $p_username username
	 * @param string $p_password password
	 * @param string $p_email email
	 */
	function MantisUser( $p_username = null, $p_password = null, $p_email = null ) {
		if( self::$fields === null ) {
			self::$fields = getClassProperties('MantisUser', 'protected');
		}
		
		if( $p_username !== null ) {
			$this->username = $p_username;
		}
		if( $p_password !== null ) {
			$this->password = $p_password;
		}
		if( $p_email !== null ) {
			$this->email = $p_email;
		}		
	}
	
	/**
	 * Return user id
	 */
	public function getID() {
		return $this->user_id;
	}

	/**
	 * overloaded function
	 * @param string $p_name property name
	 * @private
	 */
	public function __get($p_name) {
		return $this->{$p_name};
	}

	/**
	 * return a set of users matching given field
	 * @param string $p_field field name to search by
	 * @param string $p_values value to search for
     * @return array
	 */
	public static function getByArray($p_field, $p_values ) {
		if( empty( $p_values ) ) {
			return array();
		}
		
		$t_rows = self::GetFromDatabase( $p_field, $p_values );

		$t_users = array();
		foreach( $t_rows as $t_row ) {
			$t_user = new MantisUser();
			$t_user->loadrow( $t_row );
			$t_users[] = $t_user;			
		}
		
		return $t_users;
	}

     /**
      * Get MantisUser by username
      * @param string $p_name user name
      * @throws MantisBT\Exception\User_By_UserName_Not_Found
      * @return MantisUser
      */
	public static function getByUserName($p_name) {
		$t_row = self::GetFromDatabase( 'username', $p_name );
		if ( $t_row === null ) {
			throw new MantisBT\Exception\User_By_UserName_Not_Found( $p_name );
		}
		
		$t_user = new MantisUser();
		$t_user->loadrow( $t_row );
		
		return $t_user;
	}

     /**
      * Get MantisUser by cookiestring
      * @param string $p_cookie cookie string
      * @throws MantisBT\Exception\User_By_Cookie_Not_Found
      * @return MantisUser
      */
	public static function getByCookieString($p_cookie) {
		$t_row = self::GetFromDatabase( 'cookie_string', $p_cookie );
		if ( $t_row === null ) {
			throw new MantisBT\Exception\User_By_Cookie_Not_Found( $p_cookie );
		}
		
		$t_user = new MantisUser();
		$t_user->loadrow( $t_row );
		
		return $t_user;
	}

     /**
      * Get MantisUser by user id
      * @param string $p_user_id user id
      * @throws MantisBT\Exception\User_By_UserID_Not_Found
      * @return MantisUser
      */
	public static function getByUserID($p_user_id) {
		$t_row = self::GetFromDatabase( 'id', $p_user_id );
		if ( $t_row === null ) {
			throw new MantisBT\Exception\User_By_UserID_Not_Found( $p_user_id );
		}
		
		$t_user = new MantisUser();
		$t_user->loadrow( $t_row );
		
		return $t_user;
	}
	
	/**
	 * Cache a user row if necessary and return the cached copy
	 * If the second parameter is true (default), trigger an error if the user can't be found.
	 * If the second parameter is false, return false if the user can't be found.
	 * @param string $p_field field name
	 * @param mixed $p_value array or single value to retrieve
     * @throws MantisBT\Exception\UnknownException
     * @return array|null
	 */
	private static function GetFromDatabase( $p_field, $p_value ) {
		switch( $p_field ) {
			case 'id':
				$t_type = '%d';
				break;
			case 'username':
			case 'realname':
			case 'email':
			case 'cookie_string':
				$t_type = '%s';
				break;
			default:
				throw new MantisBT\Exception\UnknownException();
		}
		
		if( is_array( $p_value ) ) {
			$t_query = 'SELECT * FROM {user} WHERE ' . $p_field . ' IN (' . implode( ',', $p_value ) . ')';
			$t_result = db_query( $t_query );
			$t_rows = array();
			while( $t_row = db_fetch_array( $t_result ) ) {
				$t_rows[] = $t_row;
			}
			return $t_rows;
		} else {
			$t_query = 'SELECT * FROM {user} WHERE ' . $p_field . '=' . $t_type;
			$t_result = db_query( $t_query, array( $p_value ) );
			$t_row = db_fetch_array( $t_result );
			
			if ( $t_row ) {
				return $t_row;	
			} else {
				return null;
			}
		}
	}

     /**
      * Cache a user row if necessary and return the cached copy
      * If the second parameter is true (default), trigger an error if the user can't be found.
      * If the second parameter is false, return false if the user can't be found.
      * @param int $p_user_id user id
      * @param bool $p_trigger_errors trigger errors
      * @throws MantisBT\Exception\User_By_ID_Not_Found
      * @return array
      */
	function user_cache_row( $p_user_id, $p_trigger_errors = true ) {
		$t_query = 'SELECT * FROM {user} WHERE id=%d';
		$t_result = db_query( $t_query, array( $p_user_id ) );

		$t_row = db_fetch_array( $t_result );
		
		if ( $t_row ) {
			return $t_row;	
		} else {
			if( $p_trigger_errors ) {
				throw new MantisBT\Exception\User_By_ID_Not_Found( $p_user_id );
			}
			return false;
		}
	}
	
	/**
	 * fast-load database row into bugobject
	 * @param array $p_row
	 */
	public function loadrow( $p_row ) {
		$this->loading = true;

		foreach( $p_row as $var => $val ) {
			$this->__set( $var, $p_row[$var] );
		}
		$this->loading = false;
	}

	/**
	 * Return user object as array
	 */
	public function ToArray( ) {
		$d =  get_object_vars($this);
		unset( $d['loading'] );
		return $d;
	}

	/**
	 * overloaded function
	 * @param string $p_name name
	 * @param string $p_value value
	 * @private
	 */
	public function __set($p_name, $p_value) {
		if( in_array( $p_name, self::$fields) ){
			if( $this->loading ) {
				switch ($p_name) {
					case 'id':
						$this->user_id = (int)$p_value;
						break;
					default:
						$this->{$p_name} = $p_value;
				}
				return;
			}

			switch ($p_name) {
				case 'id':
					$this->user_id = (int)$p_value;
					break;
				case 'username':
					//if( self::validate_username($p_value ) ) {
						$this->{$p_name} = $p_value;
					//} else {
					//	throw new MantisBT\Exception\User_Name_Invalid();
					//}
					break;
				case 'email':
					$p_value = trim($p_value);
					//if( self::validate_email($p_value ) ) {
						$this->{$p_name} = $p_value;
					//}
					break;
				case 'password':
					$this->{$p_name} = auth_process_plain_password( $p_value );
					break;
				default:
					$this->{$p_name} = $p_value;
					break;
			}			
		}
	}	
	
	/**
	 * validate current user object for database insert/update
	 * throws exception on failure
	 */
	function validate() {
		// Generate a password if not valid
		if( $this->password == '' ) {
			$this->password = auth_generate_random_password( $this->email . $this->username );
		}
		
		if( null === $this->access_level ) {
			$this->access_level = config_get( 'default_new_account_access_level' );
		}
		
		if( null === $this->cookie_string ) {
			$this->cookie_string = auth_generate_unique_cookie_string( $this->email . $this->username );
		}
	}

	/**
	 * Insert a new user into the database
	 * @return int integer representing the user id that was created
	 * @access public
	 */
	function create() {
		self::validate( true );

		$query = "INSERT INTO {user}
						( username, email, password, date_created, last_visit,
						 enabled, access_level, login_count, cookie_string, realname )
					  VALUES
						( %s, %s, %s, %d, %d,
						 %d,%d,%d,%s, %s)";
		db_query( $query, array( $p_username, $p_email, $t_password, db_now(), db_now(), $c_enabled, $c_access_level, 0, $t_cookie_string, $p_realname ) );

		$t_user_id = db_insert_id( '{user}' );		
		
		return $this->getID();
	}

	/**
	 * Update a user from the given data structure
	 * @return bool (always true)
	 * @access public
	 */
	function update() {
		self::validate();

		return true;
	}

	/**
	 * Delete a user
	 * @return bool (always true)
	 * @access public
	 */
	function delete() {
		return true;
	}
	
	/** 
	 * Check if the username is a valid username.
	 * Return true if it is, false otherwise
	 * @param string $p_username username
     * @return bool
     * @throws MantisBT\Exception\User\UserNameNotUnique
	 */
	private function validate_username($p_username) {
		# The DB field is hard-coded. USERLEN should not be modified.
		if( mb_strlen( $p_username ) > USERLEN ) {
			return false;
		}

		# username must consist of at least one character
		if( is_blank( $p_username ) ) {
			return false;
		}

		# Only allow a basic set of characters
		if( 0 == preg_match( config_get( 'user_login_valid_regex' ), $p_username ) ) {
			return false;
		}
		
		$t_query = 'SELECT username FROM {user} WHERE username=%s';
		$t_result = db_query( $t_query, array( $p_username ), 1 );

		if( db_result( $t_result ) ) {
			throw new MantisBT\Exception\User\UserNameNotUnique();
		} else {
			return true;
		}
	}

	/**
	 * validate email address
	 * @param string $p_email email address
     * @return bool
	 */
	private function validate_email( $p_email ) {
		email_ensure_valid( $p_email );

		return true;
	}
}