<?php 

/**
 * Bug Data Structure Definition
 * @package MantisBT
 * @subpackage classes
 */
class MantisBug extends MantisCacheable {
	protected $id;
	protected $project_id = null;
	protected $reporter_id = 0;
	protected $handler_id = 0;
	protected $duplicate_id = 0;
	protected $priority = NORMAL;
	protected $severity = MINOR;
	protected $reproducibility = 10;
	protected $status = NEW_;
	protected $resolution = OPEN;
	protected $projection = 10;
	protected $category_id = 1;
	protected $date_submitted = '';
	protected $last_updated = '';
	protected $eta = 10;
	protected $os = '';
	protected $os_build = '';
	protected $platform = '';
	protected $version = '';
	protected $fixed_in_version = '';
	protected $target_version = '';
	protected $build = '';
	protected $view_state = VS_PUBLIC;
	protected $summary = '';
	protected $sponsorship_total = 0;
	protected $sticky = 0;
	protected $due_date = 0;

	# omitted:
	protected $profile_id = 0;

	# extended info
	protected $description = '';
	protected $steps_to_reproduce = '';
	protected $additional_information = '';

	# internal helper objects
	private $_stats = null;

	public $attachment_count = null;
	public $bugnotes_count = null;

	static $fields = null;	
	private $_exists = true;
	private $loading = false;

	function MantisBug( $p_bug_id=0, $p_get_extended = false ) {
		if( self::$fields === null ) {
			self::$fields = getClassProperties('MantisBug', 'protected');
		}

		if( $p_bug_id ) {
			$this->id = intval($p_bug_id);
			/*if( $this->isCached() ) {
				log_event( LOG_FILTERING, 'CACHE HIT' );
				$cache = $this->getCache();
				foreach( self::$fields as $t_field=>$t) {
					$this->{$t_field} = $cache->{$t_field};
				}				
			} else {*/
				if( $p_get_extended ) {
					$t_row = $this->bug_get_extended_row( $p_bug_id );
				} else {
					$t_row = $this->bug_get_row( $p_bug_id );
				}

				if ( $t_row === false ) {
					// bug not found
					$this->_exists = false;
				} else {
					$this->loadrow( $t_row );
				}
				$this->putCache(); 
			//}
		}
	}
	
	public function Exists() {
		return $this->_exists;
	}
	
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns the extended record of the specified bug, this includes
	 * the bug text fields
	 * @todo include reporter name and handler name, the problem is that
	 *      handler can be 0, in this case no corresponding name will be
	 *      found.  Use equivalent of (+) in Oracle.
	 * @param int p_bug_id integer representing bug id
	 * @return array
	 * @access public
	 */
	function bug_get_extended_row( $p_bug_id ) {
		$t_base = $this->bug_cache_row( $p_bug_id );
		$t_text = $this->bug_text_cache_row( $p_bug_id );

		# merge $t_text first so that the 'id' key has the bug id not the bug text id
		return array_merge( $t_text, $t_base );
	}

	/**
	 * Returns the record of the specified bug
	 * @param int p_bug_id integer representing bug id
	 * @return array
	 * @access public
	 */
	function bug_get_row( $p_bug_id ) {
		return $this->bug_cache_row( $p_bug_id );
	}	

	/**
	 * Cache a bug row if necessary and return the cached copy
	 * @param array p_bug_id id of bug to cache from mantis_bug_table
	 * @param array p_trigger_errors set to true to trigger an error if the bug does not exist.
	 * @return bool|array returns an array representing the bug row if bug exists or false if bug does not exist
	 * @access public
	 * @uses database_api.php
	 */
	function bug_cache_row( $p_bug_id, $p_trigger_errors = false ) {
		global $g_cache_bug;

		if( isset( $g_cache_bug[$p_bug_id] ) ) {
			return $g_cache_bug[$p_bug_id];
		}

		$c_bug_id = (int) $p_bug_id;

		$t_query = 'SELECT * FROM {bug} WHERE id=%d';
		$t_result = db_query( $t_query, array( $c_bug_id ) );

		$t_row = db_fetch_array( $t_result );
		
		if( !$t_row ) {
			$g_cache_bug[$c_bug_id] = false;

			if( $p_trigger_errors ) {
				throw new MantisBT\Exception\Bug_Not_Found( $p_bug_id );
			} else {
				return false;
			}
		}

		return $t_row;
	}	
	
	/**
	 * return number of file attachment's linked to current bug
	 * @return int
	 */
	public function get_attachment_count() {
		if ( $this->attachment_count === null ) {
			$this->attachment_count = file_bug_attachment_count( $this->id );
			return $this->attachment_count;
		} else {
			return $this->attachment_count;
		}
	}

	/**
	 * return number of bugnotes's linked to current bug
	 * @return int
	 */
	public function get_bugnotes_count() {
		if ( $this->bugnotes_count === null ) {
			$this->bugnotes_count = self::bug_get_bugnote_count();
			return $this->bugnotes_count;
		} else {
			return $this->bugnotes_count;
		}
	}

	/**
	 * @private
	 */
	public function __set($name, $value) {
		switch ($name) {
			// integer types
			case 'id':
			case 'project_id':
			case 'reporter_id':
			case 'handler_id':
			case 'duplicate_id':
			case 'priority':
			case 'severity':
			case 'reproducibility':
			case 'status':
			case 'resolution':
			case 'projection':
			case 'category_id':
				$value = (int)$value;
				break;
			case 'target_version':
				if ( !$this->loading ) {
					# Only set target_version if user has access to do so
					if( !access_has_project_level( config_get( 'roadmap_update_threshold' ) ) ) {
						throw new MantisBT\Exception\Access_Denied();
					}
				}
				break;
			case 'due_date':
				if ( !is_numeric( $value ) ) {
					$value = strtotime($value);
				}
				break;
		}
		$this->{$name} = $value;
	}

	/**
	 * @private
	 */
	public function __get($name) {
		if( $this->is_extended_field($name) )
			$this->fetch_extended_info();
		return $this->{$name};
	}

	/**
	 * @private
	 */
	public function __isset($name) {
		return isset( $this->{$name} );
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
	 * Retrieves extended information for bug (e.g. bug description)
	 * @return null
	 */
	private function fetch_extended_info() {
		if ( $this->description == '' ) {
			$t_text = $this->bug_text_cache_row($this->id);

			$this->description = $t_text['description'];
			$this->steps_to_reproduce = $t_text['steps_to_reproduce'];
			$this->additional_information = $t_text['additional_information'];
		}
	}

	/**
	 * Returns if the field is an extended field which needs fetch_extended_info()
	 * @return boolean
	 */
	private function is_extended_field( $p_field_name ) {
		switch( $p_field_name ) {
			case 'description':
			case 'steps_to_reproduce':
			case 'additional_information':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Returns the number of bugnotes for the given bug_id
	 * @return int number of bugnotes
 	 * @access private
	 * @uses database_api.php
 	 */
	private function bug_get_bugnote_count() {
		if( !access_has_project_level( config_get( 'private_bugnote_threshold' ), $this->project_id ) ) {
			$t_restriction = 'AND view_state=' . VS_PUBLIC;
		} else {
			$t_restriction = '';
		}

		$t_query = "SELECT COUNT(*) FROM {bugnote} WHERE bug_id=%d $t_restriction";
		$t_result = db_query( $t_query, array( $this->bug_id ) );

		return db_result( $t_result );
	}

	/**
	 * validate current bug object for database insert/update
	 * triggers error on failure
	 * @param bool $p_update_extended
	 */
	function validate( $p_update_extended =  true) {
		# Summary cannot be blank
		if( is_blank( $this->summary ) ) {
			throw new MantisBT\Exception\Empty_Field( lang_get( 'summary' ) );
		}

		if( $p_update_extended ) {
			# Description field cannot be empty
			if( is_blank( $this->description ) ) {
				throw new MantisBT\Exception\Empty_Field( lang_get( 'description' ) );
			}
		}

		# Make sure a category is set
		if( 0 == $this->category_id && !config_get( 'allow_no_category' ) ) {
			throw new MantisBT\Exception\Empty_Field( lang_get( 'category' ) );
		}

		if( !is_blank( $this->duplicate_id ) && ( $this->duplicate_id != 0 ) && ( $this->id == $this->duplicate_id ) ) {
			throw new MantisBT\Exception\Bug_Duplicate_Self();
			# never returns
		}
	}

	/**
	 * Insert a new bug into the database
	 * @return int integer representing the bug id that was created
	 * @access public
	 * @uses database_api.php
	 * @uses lang_api.php
	 */
	function create() {
		self::validate( true );

		# check due_date format
		if( is_blank( $this->due_date ) ) {
			$this->due_date = date_get_null();
		}
		# check date submitted and last modified
		if( is_blank( $this->date_submitted ) ) {
			$this->date_submitted = db_now();
		}
		if( is_blank( $this->last_updated ) ) {
			$this->last_updated = db_now();
		}

		# check to see if we want to assign this right off
		$t_starting_status  = config_get( 'bug_submit_status' );
		$t_original_status = $this->status;

		# if not assigned, check if it should auto-assigned.
		if( 0 == $this->handler_id ) {
			# if a default user is associated with the category and we know at this point
			# that that the bug was not assigned to somebody, then assign it automatically.
			$query = 'SELECT user_id FROM {category} WHERE id=%d';
			$result = db_query( $query, array( $this->category_id ) );

			if( $t_result = db_result( $result ) ) {
				$this->handler_id = $t_result;
			}
		}

		# Check if bug was pre-assigned or auto-assigned.
		if( ( $this->handler_id != 0 ) && ( $this->status == $t_starting_status ) && ( ON == config_get( 'auto_set_status_to_assigned' ) ) ) {
			$t_status = config_get( 'bug_assigned_status' );
		} else {
			$t_status = $this->status;
		}

		# Insert the rest of the data
		$t_query = "INSERT INTO {bug}
					    ( project_id,reporter_id, handler_id,duplicate_id,
					      priority,severity, reproducibility,status,
					      resolution,projection, category_id,date_submitted,
					      last_updated,eta, os,
					      os_build,platform, version,build,
					      profile_id, summary, view_state, sponsorship_total, 
						  sticky, fixed_in_version, target_version, due_date,
						  description, steps_to_reproduce, additional_information
					    )
					  VALUES
					    ( %d,%d,%d,%d,
					      %d,%d,%d,%d,
					      %d,%d,%d,%d,
					      %d,%d,%s,
					      %s,%s,%s,%s,
					      %d,%s,%d,%d,
					      %d,%d,%s,%d,
						  %s, %s,%s)";

		db_query( $t_query, array( $this->project_id, $this->reporter_id, $this->handler_id, $this->duplicate_id, $this->priority, $this->severity, $this->reproducibility, $t_status, $this->resolution, $this->projection, $this->category_id, $this->date_submitted, $this->last_updated, $this->eta, $this->os, $this->os_build, $this->platform, $this->version, $this->build, $this->profile_id, $this->summary, $this->view_state, $this->sponsorship_total, $this->sticky, $this->fixed_in_version, $this->target_version, $this->due_date, $this->description, $this->steps_to_reproduce, $this->additional_information ) );

		$this->id = db_insert_id( '{bug}' );

		# log new bug
		history_log_event_special( $this->id, NEW_BUG );

		# log changes, if any (compare happens in history_log_event_direct)
		history_log_event_direct( $this->id, 'status', $t_original_status, $t_status );
		history_log_event_direct( $this->id, 'handler_id', 0, $this->handler_id );

		return $this->id;
	}

	/**
	 * Update a bug from the given data structure
	 *  If the third parameter is true, also update the longer strings table
	 * @param bool p_update_extended
	 * @param bool p_bypass_email Default false, set to true to avoid generating emails (if sending elsewhere)
	 * @return bool (always true)
	 * @access public
	 */
	function update( $p_update_extended = false, $p_bypass_mail = false ) {
		self::validate( $p_update_extended );

		if( is_blank( $this->due_date ) ) {
			$this->due_date = date_get_null();
		}

		$t_old_data = bug_get( $this->id, true );

		# Update all fields
		# Ignore date_submitted and last_updated since they are pulled out
		#  as unix timestamps which could confuse the history log and they
		#  shouldn't get updated like this anyway.  If you really need to change
		#  them use bug_set_field()
		$t_query = "UPDATE {bug}
					SET project_id=%d, reporter_id=%d,
						handler_id=%d, duplicate_id=%d,
						priority=%d, severity=%d,
						reproducibility=%d, status=%d,
						resolution=%d, projection=%d,
						category_id=%d, eta=%d,
						os=%s, os_build=%s,
						platform=%s, version=%s,
						build=%s, fixed_in_version=%s,";

		$t_fields = array(
			$this->project_id, $this->reporter_id,
			$this->handler_id, $this->duplicate_id,
			$this->priority, $this->severity,
			$this->reproducibility, $this->status,
			$this->resolution, $this->projection,
			$this->category_id, $this->eta,
			$this->os, $this->os_build,
			$this->platform, $this->version,
			$this->build, $this->fixed_in_version,
		);
		$t_roadmap_updated = false;
		if( access_has_project_level( config_get( 'roadmap_update_threshold' ) ) ) {
			$t_query .= "
						target_version=%s,";
			$t_fields[] = $this->target_version;
			$t_roadmap_updated = true;
		}

		$t_query .= "
						view_state=%d,
						summary=%s,
						sponsorship_total=%d,
						sticky=%d,
						due_date=%d
					WHERE id=%d";
		$t_fields[] = $this->view_state;
		$t_fields[] = $this->summary;
		$t_fields[] = $this->sponsorship_total;
		$t_fields[] = (bool)$this->sticky;
		$t_fields[] = $this->due_date;
		$t_fields[] = $this->id;

		db_query( $t_query, $t_fields );

		bug_clear_cache( $this->id );

		# log changes
		history_log_event_direct( $this->id, 'project_id', $t_old_data->project_id, $this->project_id );
		history_log_event_direct( $this->id, 'reporter_id', $t_old_data->reporter_id, $this->reporter_id );
		history_log_event_direct( $this->id, 'handler_id', $t_old_data->handler_id, $this->handler_id );
		history_log_event_direct( $this->id, 'priority', $t_old_data->priority, $this->priority );
		history_log_event_direct( $this->id, 'severity', $t_old_data->severity, $this->severity );
		history_log_event_direct( $this->id, 'reproducibility', $t_old_data->reproducibility, $this->reproducibility );
		history_log_event_direct( $this->id, 'status', $t_old_data->status, $this->status );
		history_log_event_direct( $this->id, 'resolution', $t_old_data->resolution, $this->resolution );
		history_log_event_direct( $this->id, 'projection', $t_old_data->projection, $this->projection );
		history_log_event_direct( $this->id, 'category', category_full_name( $t_old_data->category_id, false ), category_full_name( $this->category_id, false ) );
		history_log_event_direct( $this->id, 'eta', $t_old_data->eta, $this->eta );
		history_log_event_direct( $this->id, 'os', $t_old_data->os, $this->os );
		history_log_event_direct( $this->id, 'os_build', $t_old_data->os_build, $this->os_build );
		history_log_event_direct( $this->id, 'platform', $t_old_data->platform, $this->platform );
		history_log_event_direct( $this->id, 'version', $t_old_data->version, $this->version );
		history_log_event_direct( $this->id, 'build', $t_old_data->build, $this->build );
		history_log_event_direct( $this->id, 'fixed_in_version', $t_old_data->fixed_in_version, $this->fixed_in_version );
		if( $t_roadmap_updated ) {
			history_log_event_direct( $this->id, 'target_version', $t_old_data->target_version, $this->target_version );
		}
		history_log_event_direct( $this->id, 'view_state', $t_old_data->view_state, $this->view_state );
		history_log_event_direct( $this->id, 'summary', $t_old_data->summary, $this->summary );
		history_log_event_direct( $this->id, 'sponsorship_total', $t_old_data->sponsorship_total, $this->sponsorship_total );
		history_log_event_direct( $this->id, 'sticky', $t_old_data->sticky, $this->sticky );

		history_log_event_direct( $this->id, 'due_date', ( $t_old_data->due_date != date_get_null() ) ? $t_old_data->due_date : null, ( $this->due_date != date_get_null() ) ? $this->due_date : null );

		# Update extended info if requested
		if( $p_update_extended ) {
			$query = "UPDATE {bug}
							SET description=%s,
								steps_to_reproduce=%s,
								additional_information=%s
							WHERE id=%d";
			db_query( $query, array( $this->description, $this->steps_to_reproduce, $this->additional_information, $this->id ) );

			$t_current_user = auth_get_current_user_id();

			if( $t_old_data->description != $this->description ) {
				if ( bug_revision_count( $this->id, REV_DESCRIPTION ) < 1 ) {
					$t_revision_id = bug_revision_add( $this, $t_current_user, REV_DESCRIPTION, $t_old_data->description, 0, $t_old_data->last_updated );
				}
				$t_revision_id = bug_revision_add( $this, $t_current_user, REV_DESCRIPTION, $this->description );
				history_log_event_special( $this->id, DESCRIPTION_UPDATED, $t_revision_id );
			}

			if( $t_old_data->steps_to_reproduce != $this->steps_to_reproduce ) {
				if ( bug_revision_count( $this->id, REV_STEPS_TO_REPRODUCE ) < 1 ) {
					$t_revision_id = bug_revision_add( $this, $t_current_user, REV_STEPS_TO_REPRODUCE, $t_old_data->steps_to_reproduce, 0, $t_old_data->last_updated );
				}
				$t_revision_id = bug_revision_add( $this, $t_current_user, REV_STEPS_TO_REPRODUCE, $this->steps_to_reproduce );
				history_log_event_special( $this->id, STEP_TO_REPRODUCE_UPDATED, $t_revision_id );
			}

			if( $t_old_data->additional_information != $this->additional_information ) {
				if ( bug_revision_count( $this->id, REV_ADDITIONAL_INFO ) < 1 ) {
					$t_revision_id = bug_revision_add( $this, $t_current_user, REV_ADDITIONAL_INFO, $t_old_data->additional_information, 0, $t_old_data->last_updated );
				}
				$t_revision_id = bug_revision_add( $this, $t_current_user, REV_ADDITIONAL_INFO, $this->additional_information );
				history_log_event_special( $this->id, ADDITIONAL_INFO_UPDATED, $t_revision_id );
			}
		}

		# Update the last update date
		bug_update_date( $this->id );

		# allow bypass if user is sending mail separately
		if( false == $p_bypass_mail ) {
			# bug assigned
			if( $t_old_data->handler_id != $this->handler_id ) {
				email_generic( $this->id, 'owner', 'email_notification_title_for_action_bug_assigned' );
				return true;
			}

			# status changed
			if( $t_old_data->status != $this->status ) {
				$t_status = MantisEnum::getLabel( config_get( 'status_enum_string' ), $this->status );
				$t_status = str_replace( ' ', '_', $t_status );
				email_generic( $this->id, $t_status, 'email_notification_title_for_status_bug_' . $t_status );
				return true;
			}

			# @todo handle priority change if it requires special handling
			# generic update notification
			email_generic( $this->id, 'updated', 'email_notification_title_for_action_bug_updated' );
		}

		return true;
	}

	/**
	 * allows bug deletion :
	 * delete the bug, bugtext, bugnote, and bugtexts selected
	 * @return bool (always true)
	 * @access public
	 */
	function delete() {
		# call pre-deletion custom function
		helper_call_custom_function( 'issue_delete_validate', array( $this->id ) );

		# log deletion of bug - removed later on in this function by history_delete
		history_log_event_special( $this->id, BUG_DELETED, bug_format_id( $this->id ) );

		email_bug_deleted( $this->id );

		# call post-deletion custom function.  We call this here to allow the custom function to access the details of the bug before
		# they are deleted from the database given it's id.  The other option would be to move this to the end of the function and
		# provide it with bug data rather than an id, but this will break backward compatibility.
		helper_call_custom_function( 'issue_delete_notify', array( $this->id ) );

		# Unmonitor bug for all users
		bug_unmonitor( $this->id, null );

		# Delete custom fields
		custom_field_delete_all_values( $this->id );

		# Delete bugnotes
		bugnote_delete_all( $this->id );

		# Delete all sponsorships
		sponsorship_delete( sponsorship_get_all_ids( $this->id ) );

		# delete any relationships
		relationship_delete_all( $this->id );

		# Delete files
		file_delete_attachments( $this->id );

		# Detach tags
		tag_bug_detach_all( $this->id, false );

		# Delete the bug history
		history_delete( $this->id );

		# Delete bug info revisions
		bug_revision_delete( $this->id );

		# Delete the bug entry
		$t_query = 'DELETE FROM {bug} WHERE id=%d';
		db_query( $t_query, array( $this->id ) );

		bug_clear_cache( $this->id );

		# db_query errors on failure so:
		return true;
	}

	/**
	 * Cache a bug text row if necessary and return the cached copy
	 * @param int p_bug_id integer bug id to retrieve text for
	 * @param bool p_trigger_errors If the second parameter is true (default), trigger an error if bug text not found.
	 * @return bool|array returns false if not bug text found or array of bug text
	 * @access public
	 * @uses database_api.php
	 */
	function bug_text_cache_row( $p_bug_id, $p_trigger_errors = true ) {
		$c_bug_id = (int) $p_bug_id;

		$t_query = "SELECT b.* FROM {bug} b WHERE b.id=%d";
		$t_result = db_query( $t_query, array( $c_bug_id ) );

		$row = db_fetch_array( $t_result );
		
		if( !$row ) {
			if( $p_trigger_errors ) {
				throw new MantisBT\Exception\Bug_Not_Found( $p_bug_id );
			} else {
				return false;
			}
		}

		return $row;
	}
}