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
 * @copyright Copyright 2012 MantisBT Team - mantisbt-dev@lists.sourceforge.
 * @link http://www.mantisbt.org
 * @package MantisBT
 */

namespace MantisBT\Exception;

/**
 * Exception Abstract
 * @package MantisBT
 * @subpackage classes
 */
 abstract class ExceptionAbstract extends \Exception
{
	/**
	 * Exception Message
	 */
    protected $message = 'Unknown Exception';

	/**
	 * Mantis Context
	 */
	private $context = null;

	/**
	 * Constructor
     * @param string $p_message exception message
	 * @param int $p_code error code
	 * @param array $p_parameters
	 * @param \Exception previous exception
	 */
    public function __construct($p_message = '', $p_code = 0, $p_parameters = null, \Exception $previous = null)
    {
		$this->context = $p_parameters;
        parent::__construct($p_message, $p_code, $previous);
    }

	/**
	 * overloaded function
	 */
    public function __toString()
    {
        return get_class($this) . " '{$this->message}' in {$this->file}({$this->line})\n"
                                . "{$this->getTraceAsString()}";
    }

	/**
	 * Get Context
	 */
	public function getContext() {
		return $this->context;
	}
}
?>