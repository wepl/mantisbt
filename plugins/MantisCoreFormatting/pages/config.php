<?php
# MantisBT - A PHP based bugtracking system
# Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.net
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
 * Edit Core Formatting Configuration
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top( d___('plugin_MantisCoreFormatting', 'MantisBT Formatting') );

print_manage_menu( );

?>

<br/>
<form action="<?php echo plugin_page( 'config_edit' )?>" method="post">
<fieldset>
<?php echo form_security_field( 'plugin_format_config_edit' ) ?>
</fieldset>
<table class="width50" cellspacing="1">
<colgroup>
	<col style="width:60%;" />
	<col style="width:20%;" />
	<col style="width:20%;" />
</colgroup>

<tr>
	<td class="form-title" colspan="3">
		<?php echo d___('plugin_MantisCoreFormatting', 'MantisBT Formatting Configuration') ?>
	</td>
</tr>

<tr>
	<td class="category">
		<?php echo d___('plugin_MantisCoreFormatting', 'Text Processing') ?>
		<br /><span class="small"><?php echo d___('plugin_MantisCoreFormatting', 'Do not turn off unless you really know what you\'re doing. In the off state, cross site scripting (XSS) attacks are possible.') ?></span>
	</td>
	<td class="center">
		<label><input type="radio" name="process_text" value="1" <?php echo( ON == plugin_config_get( 'process_text' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo d___('plugin_MantisCoreFormatting', 'On') ?></label>
	</td>
	<td class="center">
		<label><input type="radio" name="process_text" value="0" <?php echo( OFF == plugin_config_get( 'process_text' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo d___('plugin_MantisCoreFormatting', 'Off') ?></label>
	</td>
</tr>

<tr>
	<th class="category">
		<?php echo d___('plugin_MantisCoreFormatting', 'URL Processing') ?>
	</th>
	<td class="center">
		<label><input type="radio" name="process_urls" value="1" <?php echo( ON == plugin_config_get( 'process_urls' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo d___('plugin_MantisCoreFormatting', 'On') ?></label>
	</td>
	<td class="center">
		<label><input type="radio" name="process_urls" value="0" <?php echo( OFF == plugin_config_get( 'process_urls' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo d___('plugin_MantisCoreFormatting', 'Off') ?></label>
	</td>
</tr>

<tr>
	<th class="category">
		<?php echo d___('plugin_MantisCoreFormatting', 'MantisBT Links ( Issue/Issuenote )') ?>
	</th>
	<td class="center">
		<label><input type="radio" name="process_buglinks" value="1" <?php echo( ON == plugin_config_get( 'process_buglinks' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo d___('plugin_MantisCoreFormatting', 'On') ?></label>
	</td>
	<td class="center">
		<label><input type="radio" name="process_buglinks" value="0" <?php echo( OFF == plugin_config_get( 'process_buglinks' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo d___('plugin_MantisCoreFormatting', 'Off') ?></label>
	</td>
</tr>

<tr>
	<td class="center" colspan="3">
		<input type="submit" class="button" value="<?php echo _( 'Update Configuration' )?>" />
	</td>
</tr>

</table>
</form>

<?php
html_page_bottom();
