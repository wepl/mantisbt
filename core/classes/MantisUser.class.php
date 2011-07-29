<?php
class MantisUser extends MantisCacheable {
	protected $user_id;
	protected $username;
	protected $realname;
	protected $email;
	protected $password;
	protected $enabled;
	protected $protected;
	protected $access_level;
	protected $login_count;
	protected $lost_password_request_count;
	protected $failed_login_count;
	protected $cookie_string;
	protected $last_visit;
	protected $date_created;

	static $fields = null;
	private $_exists = true;
	private $loading = false;
	
	function MantisUser( $p_user_id=0 ) {
		if( self::$fields === null ) {
			self::$fields = getClassProperties('MantisUser', 'protected');
		}
		if( $p_user_id ) {
			$this->user_id = intval($p_user_id);
			if( $this->isCached() ) {
				log_event( LOG_FILTERING, 'CACHE HIT' );
				
				$cache = $this->getCache();
				foreach( self::$fields as $t_field=>$t) {
					$this->{$t_field} = $cache->{$t_field};
				}
				return;
			} else {
				$t_row = $this->user_cache_row( $p_user_id );

				if ( $t_row === false ) {
					// user not found
					$this->_exists = false;
				} else {
					$this->loadrow( $t_row );
				}
				$this->putCache(); 
			}
		}
	}
	
	public function Exists() {
		return $this->_exists;
	}
	
	public function getID() {
		return $this->user_id;
	}

	# --------------------
	# Cache a user row if necessary and return the cached copy
	#  If the second parameter is true (default), trigger an error
	#  if the user can't be found.  If the second parameter is
	#  false, return false if the user can't be found.
	function user_cache_row( $p_user_id, $p_trigger_errors = true ) {
		$query = "SELECT * FROM {user} WHERE id=" . db_param();
		$result = db_query_bound( $query, array( $p_user_id ) );

		$row = db_fetch_array( $result );
		
		if ( $row ) {
			return $row;	
		} else {
			if( $p_trigger_errors ) {
				error_parameters( (integer)$p_user_id );
				trigger_error( ERROR_USER_BY_ID_NOT_FOUND, ERROR );
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
		unset( $d['_exists'] );
		unset( $d['loading'] );
		return $d;
	}
	
	
	/**
	 * @private
	 */
	public function __set($name, $value) {
		if( in_array( $name, self::$fields) ){
			switch ($name) {
				case 'id':
					$value = (int)$value;
					break;
				default:
					$this->{$name} = $value;
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
	}

	/**
	 * Insert a new user into the database
	 * @return int integer representing the user id that was created
	 * @access public
	 */
	function create() {
		self::validate( true );

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
	
}