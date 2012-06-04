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
 */
 
/**
 * RelationshipData Structure Definition
 * @package MantisBT
 * @subpackage classes
 */
class MantisBugRelationshipData {
	/**
	 * Relationship id
	 */
	protected $id;

	/**
	 * Source Bug id
	 */
	protected $src_bug_id = null;

	/**
	 * Destination Bug id
	 */
	protected $dest_bug_id = null;

	/**
	 * Type
	 */
	protected $type = null;

	/**
	 * Source project id
	 */
	protected $src_project_id;

	/**
	 * Destination project id
	 */
	protected $dest_project_id;

	/**
	 * Constructor
	 * @param int id
	 */
	function MantisBugRelationshipData( $p_id = 0 ) {
		if( $p_id ) {
			$this->id = intval($p_id);

		}
	}

	/**
	 * overloaded function
	 * @private
	 * @param string property name
	 * @param string value
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
	 * overloaded function
	 * @private
	 * @param string property name
	 */
	public function __get($name) {
		return $this->{$name};
	}

	/**
	 * overloaded function
	 * @private
	 * @param string property name
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