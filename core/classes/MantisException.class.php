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
 * @link http://www.mantisbt.org
 * @package MantisBT
 */

 /**
 * Mantis Database Exception
 * @package MantisBT
 * @subpackage classes
 */
abstract class MantisException extends Exception
{
	/**
	 * Exception message
	 */
    protected $message = 'Unknown exception';

	/**
	 * Unknown
	 */
    private $string;
	
	/**
	 * User-defined exception code
	 */
    protected $code    = 0;

	/**
	 * Source filename of exception
	 */
    protected $file;

	/**
	 * Source line of exception
	 */
    protected $line;

	/**
	 * Unknown
	 */
    private $trace;

	/**
	 * Mantis Context
	 */
	private $context = null;

	/**
	 * Constructor
	 * @param int code
	 * @param int parameters
	 * @param Exception Previous exception
	 */
    public function __construct($code = 0, $parameters, Exception $previous = null)
    {
		$message = var_export( $parameters, true);
		
		$this->context = $parameters;
        parent::__construct($message, $code, $previous);
    }

	/**
	 * Return exception details as string
	 */
    public function __toString()
    {
        return get_class($this) . " '{$this->message}' in {$this->file}({$this->line})\n"
                                . "{$this->getTraceAsString()}";
    }

	/**
	 * Get Exception Context
	 */
	public function getContext() {
		return $this->context;
	}
}
?>