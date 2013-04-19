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
 * @copyright Copyright 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.
 * @link http://www.mantisbt.org
 * @package MantisBT
 */

/**
 * Mantis Context Handling Class
 */
class MantisContext {
	static $user_id = array(); 
 
	static $project_id = array();

	static function SetUser( $id ) {
		array_push(self::$user_id, $id);
	}

	static function PopUser(){
		array_pop(self::$user_id);
	}
	
	static function GetUser() { 
		if ( empty( self::$user_id ) ) {
			throw new /*MantisBT\Exception\Context\MissingContext*/ Exception();
		} else {
			return end( self::$user_id );
		}
	}

	
	static function SetProject( $id ) {
		array_push(self::$project_id, $id);
	}

	static function PopProject(){
		array_pop(self::$project_id);
	}
	
	static function GetProject() { 
		if ( empty( self::$project_id ) ) {
			throw new /*MantisBT\Exception\Context\MissingContext*/ Exception();
		} else {
			return end( self::$project_id );
		}
	}	
}