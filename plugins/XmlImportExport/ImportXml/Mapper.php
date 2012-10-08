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
  * Mapper class
  *
  * it will store the ( type, old, new ) triplet for later retrieval
  */
class ImportXml_Mapper {
	/**
	 * Issues
	 * @var array
	 */
	private $issue = array( );

	/**
	 * add
	 * @param mixed type
	 * @param mixed old
	 * @param mixed new
	 */
	public function add( $type, $old, $new ) {
		$this->{$type}[ $old ] = $new;
	}

	/**
	 * check if entry exists within array
	 * @param mixed type
	 * @param mixed id
     * @return bool
	 */
	public function exists( $type, $id ) {
		return array_key_exists( $id, $this->{$type} );
	}

	/**
	 * get new id
	 * @param mixed type
	 * @param mixed old
	 */
	public function getNewID( $type, $old ) {
		if( $this->exists( $type, $old ) ) {
			return $this->{$type}[ $old ];
		} else {
			return $old;
		}
	}

	/**
	 * get all by type
	 * @param mixed type
	 */
	public function getAll( $type ) {
		return $this->{$type};
	}
}
