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
 * Mantis Database Exception
 * @package MantisBT
 * @subpackage classes
 */
class MantisDatabaseException extends MantisException {
	/**
	 * Constructor
	 * @param int $p_code
	 * @param int $p_parameters
	 * @param \Exception Previous exception
	 */
    public function __construct($p_code = 0, $p_parameters = null, \Exception $previous = null)
    {
		/* if we have some form of database exception, assume that the database don't want to treat
		 * the database as connected in the exception handler anymore
		 */
		global $g_db_connected;
		$g_db_connected = false;
		
		parent::__construct($p_code, $p_parameters, $previous);
	}
}