<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package MantisBT
 * @author Marcello Scata' <marcelloscata at users.sourceforge.net> ITALY
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses config_api.php
 * @uses crypto_api.php
 * @uses gpc_api.php
 * @uses utility_api.php
 */

/**
 * MantisBT Core API's
 */
require_once( 'core.php' );
require_api( 'config_api.php' );
require_api( 'crypto_api.php' );
require_api( 'gpc_api.php' );
require_api( 'utility_api.php' );

$f_public_key = gpc_get_string( 'public_key' );

$t_private_key = substr( hash( 'whirlpool', 'captcha' . config_get_global( 'crypto_master_salt' ) . $f_public_key, false ), 0, 5 );

$captcha = new MantisCaptcha();
$captcha->TTF_folder = get_font_path();
$captcha->TTF_RANGE = config_get( 'font_per_captcha' );
$captcha->make_captcha( $t_private_key );