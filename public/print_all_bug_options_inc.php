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
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses authentication_api.php
 * @uses database_api.php
 * @uses form_api.php
 * @uses string_api.php
 * @uses user_api.php
 * @uses utility_api.php
 */

if ( !defined( 'PRINT_ALL_BUG_OPTIONS_INC_ALLOW' ) ) {
	return;
}

require_api( 'authentication_api.php' );
require_api( 'database_api.php' );
require_api( 'form_api.php' );
require_api( 'string_api.php' );
require_api( 'user_api.php' );
require_api( 'utility_api.php' );

function edit_printing_prefs( $p_user_id = null, $p_error_if_protected = true, $p_redirect_url = '' )
{
	if ( null === $p_user_id ) {
		$p_user_id = auth_get_current_user_id();
	}

	$c_user_id = db_prepare_int( $p_user_id );

	# protected account check
	if ( $p_error_if_protected ) {
		user_ensure_unprotected( $p_user_id );
	}

	if ( is_blank( $p_redirect_url ) ) {
		$p_redirect_url = 'print_all_bug_page.php';
	}

	$t_field_name_arr = array( 'id',
	                           'category',
	                           'severity',
	                           'reproducibility',
	                           'date_submitted',
	                           'last_update',
	                           'reporter',
	                           'assigned_to',
	                           'priority',
	                           'status',
	                           'build',
	                           'projection',
	                           'eta',
	                           'platform',
	                           'os',
	                           'os_version',
	                           'product_version',
	                           'resolution',
	                           'duplicate_id',
	                           'summary',
	                           'description',
	                           'steps_to_reproduce',
	                           'additional_information',
	                           'attached_files',
	                           'bugnote_title',
	                           'bugnote_date',
	                           'bugnote_description',
	                           'time_tracking'
	                         );
	$t_field_name_count = count( $t_field_name_arr );

	# Grab the data
	$t_query = "SELECT print_pref FROM {user_print_pref} WHERE user_id=" . db_param();
	$t_result = db_query_bound( $t_query, array( $c_user_id ) );

	$t_row = db_fetch_array( $t_result );

	## OOPS, No entry in the database yet.  Lets make one
	if ( !$t_row ) {
		# create a default array, same size than $t_field_name
		for ( $i = 0; $i < $t_field_name_count; $i++ ) {
			$t_default_arr[$i] = 1 ;
		}
		$t_default = implode( '', $t_default_arr ) ;

		# all fields are added by default
		$t_query = "INSERT INTO {user_print_pref} (user_id, print_pref) VALUES (" . db_param() . "," . db_param() . ")";
		$t_result = db_query_bound( $t_query, array( $c_user_id, $t_default ) );

		# Rerun select query
		$t_query = "SELECT print_pref FROM {user_print_pref} WHERE user_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $c_user_id ) );
		$t_row = db_fetch_array( $t_result );
	}

	# putting the query result into an array with the same size as $t_fields_arr
	$t_prefs = $t_row['print_pref'];

	# Account Preferences Form BEGIN
	$t_index_count=0;
?>
<br />
<div>
<form method="post" action="print_all_bug_options_update.php">
<?php echo form_security_field( 'print_all_bug_options_update' ) ?>
<input type="hidden" name="user_id" value="<?php echo $p_user_id ?>" />
<input type="hidden" name="redirect_url" value="<?php echo string_attribute( $p_redirect_url ) ?>" />
<table class="width75" cellspacing="1">
<tr>
	<td class="form-title">
		<?php echo _('Choose fields to print') ?>
	</td>
	<td class="right">
	</td>
</tr>


<?php # display the checkboxes
for ( $i = 0; $i < $t_field_name_count; $i++ ) {
	echo '<tr>';
?>

	<th class="category">
		<?php
			switch( $t_field_name_arr[$i] ) {
				case 'id':
					echo _('ID');
					break;
				case 'category':
					echo _('Category');
					break;
				case 'severity':
					echo _('Severity');
					break;
				case 'reproducibility':
					echo _('Reproducibility');
					break;
				case 'date_submitted':
					echo _('Date Submitted');
					break;
				case 'last_update':
					echo _('Last Update');
					break;
				case 'reporter':
					echo _('Reporter');
					break;
				case 'assigned_to':
					echo _('Assigned To');
					break;
				case 'priority':
					echo _('Priority');
					break;
				case 'status':
					echo _('Status');
					break;
				case 'build':
					echo _('Build');
					break;
				case 'projection':
					echo _('Projection');
					break;
				case 'eta':
					echo _('EA');
					break;
				case 'platform':
					echo _('Platform');
					break;
				case 'os':
					echo _('OS');
					break;
				case 'os_version':
					echo _('OS Version');
					break;
				case 'product_version':
					echo _('Product Version');
					break;
				case 'resolution':
					echo _('Resolution');
					break;
				case 'duplicate_id':
					echo _('Duplicate ID');
					break;
				case 'summary':
					echo _('Summary');
					break;
				case 'description':
					echo _('Description');
					break;
				case 'steps_to_reproduce':
					echo _('Steps To Reproduce');
					break;
				case 'additional_information':
					echo _('Additional Information');
					break;
				case 'attached_files':
					echo _('Attached Files');
					break;
				case 'bugnote_title':
					echo _('Note handler');
					break;
				case 'bugnote_description':
					echo _('Note description');
					break;
				case 'time_tracking':
					echo _('Time tracking');
					break;
				case default:
					echo string_display_line( $t_field_name_arr[$i] );
					break;
			}
		?>	                           
	</th>
	<td>
		<input type="checkbox" name="<?php echo 'print_' . string_attribute( $t_field_name_arr[$i] ) ?>"
		<?php if ( isset( $t_prefs[$i] ) && ( $t_prefs[$i] == 1 ) ) echo 'checked="checked"' ?> />
	</td>
</tr>

<?php
}
?>
<tr>
	<td>&#160;</td>
	<td>
		<input type="submit" class="button" value="<?php echo _('Update Prefs') ?>" />
	</td>
</tr>
</table>
</form>
</div>

<br />

<div class="border center">
	<form method="post" action="print_all_bug_options_reset.php">
	<?php echo form_security_field( 'print_all_bug_options_reset' ) ?>
	<input type="submit" class="button" value="<?php echo _('Reset Prefs') ?>" />
	</form>
</div>

<?php
}
