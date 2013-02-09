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
 * Lost Password Functionality
 *
 * @package MantisBT
 * @author Marcello Scata' <marcelloscata at users.sourceforge.net> ITALY
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses form_api.php
 * @uses helper_api.php
 * @uses html_api.php

 * @uses print_api.php
 */

require_once( 'core.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'form_api.php' );
require_api( 'helper_api.php' );
require_api( 'html_api.php' );
require_api( 'print_api.php' );
require_css( 'login.css' );

# lost password feature disabled or reset password via email disabled -> stop here!
if ( LDAP == config_get_global( 'login_method' ) ||
	OFF == config_get( 'lost_password_feature' ) ||
	OFF == config_get( 'send_reset_password' )  ||
	OFF == config_get( 'enable_email_notification' ) ) {
		throw new MantisBT\Exception\Authentication\LostPasswordDisabled();
}

# don't index lost password page
html_robots_noindex();

html_page_top1();
html_page_top2a();
?>
<div id="lost-password-div" class="form-container">
	<form id="lost-password-form" method="post" action="lost_pwd.php">
		<fieldset>
			<legend><span><?php echo _( 'Password Reset' ); ?></span></legend>
			 <ul id="login-links">
				<li><a href="login_page.php"><?php echo _( 'Login' ); ?></a></li>
				<li><a href="signup_page.php"><?php echo lang_get( 'signup_link' ); ?></a></li>
            </ul>
			<?php
			echo form_security_field( 'lost_pwd' );

			$t_allow_passwd = helper_call_custom_function( 'auth_can_change_password', array() );
			if ( $t_allow_passwd ) { ?>
			<div class="field-container">
				<label for="username"><span><?php echo _( 'Username' ) ?></span></label>
				<span class="input"><input id="username" type="text" name="username" size="32" maxlength="<?php echo USERLEN;?>" class="autofocus" /></span>
				<span class="label-style"></span>
			</div>
			<div class="field-container">
				<label for="email-field"><span><?php echo _( 'E-mail' ) ?></span></label>
				<span class="input"><?php print_email_input( 'email', '' ) ?></span>
				<span class="label-style"></span>
			</div>
			<span id="lost-password-msg"><?php echo lang_get( 'lost_password_info' ); ?></span>
			<span class="submit-button"><input type="submit" class="button" value="<?php echo _( 'Submit' ) ?>" /></span><?php
			} else {
				echo '<span id="no-password-msg">';
				echo _( 'Your password is managed by another system. Please contact your system administrator.' );
				echo '</span>';
			} ?>
		</fieldset>
	</form>
</div><?php

html_page_bottom1a( __FILE__ );
