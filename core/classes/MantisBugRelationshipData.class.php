<?php

/**
 * RelationshipData Structure Definition
 * @package MantisBT
 * @subpackage classes
 */
class MantisBugRelationshipData {
	protected $id;
	protected $src_bug_id = null;
	protected $dest_bug_id = null;
	protected $type = null;

	protected $src_project_id;
	protected $dest_project_id;

	function MantisBugRelationshipData( $p_id = 0 ) {
		if( $p_id ) {
			$this->id = intval($p_id);

		}
	}

	/**
	 * @private
	 */
	public function __set($name, $value) {
		switch ($name) {
			// integer types
			case 'id':
			case 'src_bug_id':
			case 'src_project_id':
			case 'dest_bug_id':
			case 'dest_project_id':
			case 'type':
				$value = (int)$value;
				break;
		}
		$this->{$name} = $value;
	}

	/**
	 * @private
	 */
	public function __get($name) {
		return $this->{$name};
	}

	/**
	 * @private
	 */
	public function __isset($name) {
		return isset( $this->{$name} );
	}

	/**
	 * validate current object for database insert/update
	 */
	function validate() {
		if( $this->src_bug_id=== null || $this->dest_bug_id === null || $this->type === null ) {
			throw new MantisBT\Exception\Empty_Field( );
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

		$query = "INSERT INTO {bug_relationship}
				( source_bug_id, destination_bug_id, relationship_type )
				VALUES
				( %d,%d,%d)";
		$result = db_query( $query, array( $this->src_bug_id, $this->dest_bug_id, $this->type ) );

		$this->id = db_insert_id( '{bug_relationship}' );

		return $this->id;
	}

	/**
	 * Update an object from the given data structure
	 * @return bool (always true)
	 * @access public
	 */
	function update() {
		self::validate();

		$query = "UPDATE {bug_relationship}
				SET source_bug_id=%d, destination_bug_id=%d, relationship_type=%d
				WHERE id=%d";
		$result = db_query( $query, array( $this->src_bug_id, $this->dest_bug_id, $this->type, $this->id ) );

		return true;
	}

}