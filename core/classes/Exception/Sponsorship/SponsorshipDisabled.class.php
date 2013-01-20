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

namespace MantisBT\Exception\Sponsorship;
use MantisBT\Exception\ExceptionAbstract;

/**
 * Sponsorship Disabled Exception
 * @package MantisBT
 * @subpackage classes
 */
class SponsorshipDisabled extends ExceptionAbstract
{
	/**
	 * Constructor
	 * @param array $p_parameters parameters
	 * @param \Exception previous exception
	 */
    public function __construct($p_parameters = null, \Exception $p_previous = null)
    {
        if ($p_parameters === null) {
            $t_message = _('Sponsorship support not enabled.');
        } else {
            $t_message = vsprintf( _('Sponsorship support not enabled.'), $p_parameters);
        }
        parent::__construct($t_message, 500, $p_previous);
    }
}
?>