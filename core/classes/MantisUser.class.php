<?php
class MantisUser extends MantisCacheable {
	protected $user_id;
	protected $username;
	protected $realname;
	protected $email;
	protected $password;
	protected $enabled = true;
	protected $protected = false;
	protected $access_level;
	protected $login_count = 0;
	protected $lost_password_request_count = 0;
	protected $failed_login_count = 0;
	protected $cookie_string;
	protected $last_visit;
	protected $date_created;

	static $fields = null;
	private $loading = false;
	
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
		
	public function getID() {
		return $this->user_id;
	}

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
	
	public static function getByUserName($p_name) {
		$t_row = self::GetFromDatabase( 'username', $p_name );
		if ( $t_row === null ) {
			throw new MantisBT\Exception\User_By_UserName_Not_Found( $p_name );
		}
		
		$t_user = new MantisUser();
		$t_user->loadrow( $t_row );
		
		return $t_user;
	}

	public static function getByCookieString($p_cookie) {
		$t_row = self::GetFromDatabase( 'cookie_string', $p_cookie );
		if ( $t_row === null ) {
			throw new MantisBT\Exception\User_By_Cookie_Not_Found( $p_cookie );
		}
		
		$t_user = new MantisUser();
		$t_user->loadrow( $t_row );
		
		return $t_user;
	}
	
	public static function getByUserID($p_user_id) {
		$t_row = self::GetFromDatabase( 'id', $p_user_id );
		if ( $t_row === null ) {
			throw new MantisBT\Exception\User_By_UserID_Not_Found( $p_user_id );
		}
		
		$t_user = new MantisUser();
		$t_user->loadrow( $t_row );
		
		return $t_user;
	}
	
	# Cache a user row if necessary and return the cached copy
	#  If the second parameter is true (default), trigger an error
	#  if the user can't be found.  If the second parameter is
	#  false, return false if the user can't be found.
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
				throw new MantisBT\Exception\Generic();
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
	 *  If the second parameter is true (default), trigger an error
	 *  if the user can't be found.  If the second parameter is
	 *  false, return false if the user can't be found.
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

	public function ToArray( ) {
		$d =  get_object_vars($this);
		unset( $d['loading'] );
		return $d;
	}
	
	
	/**
	 * @private
	 */
	public function __set($name, $p_value) {
		if( in_array( $name, self::$fields) ){
			if( $this->loading ) {
				switch ($name) {
					case 'id':
						$this->user_id = (int)$p_value;
						break;
					default:
						$this->{$name} = $p_value;
				}
				return;
			}

			switch ($name) {
				case 'id':
					$this->user_id = (int)$p_value;
					break;
				case 'username':
					//if( self::validate_username($p_value ) ) {
						$this->{$name} = $p_value;
					//} else {
					//	throw new MantisBT\Exception\User_Name_Invalid();
					//}
					break;
				case 'email':
					$p_value = trim($p_value);
					//if( self::validate_email($p_value ) ) {
						$this->{$name} = $p_value;
					//}
					break;
				case 'password':
					$this->{$name} = auth_process_plain_password( $p_value );
					break;
				default:
					$this->{$name} = $p_value;
					break;
			}			
		}
	}	
	
	/**
	 * validate current user object for database insert/update
	 * throws exception on failure
	 * @param bool $p_update_extended
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
	 */
	private function validate_username($p_username) {
		# The DB field is hard-coded. USERLEN should not be modified.
		if( utf8_strlen( $p_username ) > USERLEN ) {
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
			throw new MantisBT\Exception\User_Name_Not_Unique();
			return false;
		} else {
			return true;
		}
		
		return true;
	}
	
	private function validate_email( $p_email ) {
		email_ensure_valid( $p_email );

		return true;
	}
	
}