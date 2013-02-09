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
 * Export API
 *
 * @package CoreAPI
 * @subpackage ExportAPI
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses authentication_api.php
 * @uses bug_api.php
 * @uses category_api.php
 * @uses columns_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses custom_field_api.php
 * @uses helper_api.php

 * @uses project_api.php
 * @uses user_api.php
 */

require_api( 'authentication_api.php' );
require_api( 'bug_api.php' );
require_api( 'category_api.php' );
require_api( 'columns_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'custom_field_api.php' );
require_api( 'helper_api.php' );
require_api( 'project_api.php' );
require_api( 'user_api.php' );


/**
 * Gets an Xml Row that contains all column titles.
 * @returns array The xml row.
 */
function export_get_titles_row() {
	$t_columns = export_get_columns();
    $t_ret = array();

	foreach( $t_columns as $t_column ) {
		$t_custom_field = column_get_custom_field_name( $t_column );
		if( $t_custom_field !== null ) {
			$t_ret[] = lang_get_defaulted( $t_custom_field );
		} else {
			$t_ret[] = column_get_title( $t_column );
		}
	}

	return $t_ret;
}

/**
 * Gets the download file name for the Excel export.  If 'All Projects' selected, default to <username>,
 * otherwise default to <projectname>.
 * @return string file name without extension
 */
function export_get_default_filename() {
	$t_current_project_id = helper_get_current_project();

	if( ALL_PROJECTS == $t_current_project_id ) {
		$t_filename = user_get_name( auth_get_current_user_id() );
	} else {
		$t_filename = project_get_field( $t_current_project_id, 'name' );
	}

	return $t_filename;
}

/**
 * Escapes the specified column value and includes it in a Cell Xml.
 * @param string $p_value The value
 * @return string The Cell Xml.
 */
function export_prepare_string( $p_value ) {
	$t_type = is_numeric( $p_value ) ? 'Number' : 'String';

	$t_value = str_replace( array ( '&', "\n", '<', '>'), array ( '&amp;', '&#10;', '&lt;', '&gt;' ),  $p_value );
	$t_ret = "<Cell><Data ss:Type=\"$t_type\">" . $t_value . "</Data></Cell>\n";

	return $t_ret;
}

/**
 * Gets the columns to be included in the Excel Xml export.
 * @return array column names.
 */
function export_get_columns() {
	$t_columns = helper_get_columns_to_view( COLUMNS_TARGET_EXPORT_PAGE );
	return $t_columns;
}

/**
 * Gets the formatted bug id value.
 * @param int $p_bug_id The bug id to be formatted.
 * @return string The bug id prefixed with 0s.
 */
function export_format_id( $p_bug_id ) {
	return bug_format_id( $p_bug_id );
}

/**
 * Gets the formatted project id value.
 * @param int $p_project_id The project id.
 * @return string The project name.
 */
function export_format_project_id( $p_project_id ) {
	return project_get_name( $p_project_id );
}

/**
 * Gets the formatted reporter id value.
 * @param int $p_reporter_id The reporter id.
 * @return string The reporter user name.
 */
function export_format_reporter_id( $p_reporter_id ) {
	return user_get_name( $p_reporter_id );
}

/**
 * Gets the formatted number of bug notes.
 * @param int $p_bugnotes_count  The number of bug notes.
 * @return string The number of bug notes.
 */
function export_format_bugnotes_count( $p_bugnotes_count ) {
	return $p_bugnotes_count;
}

/**
 * Gets the formatted handler id.
 * @param int $p_handler_id The handler id.
 * @return string The handler user name or empty string.
 */
function export_format_handler_id( $p_handler_id ) {
	if( $p_handler_id > 0 ) {
		return user_get_name( $p_handler_id );
	} else {
		return '';
	}
}

/**
 * Gets the formatted priority.
 * @param int $p_priority priority id.
 * @return string the priority text.
 */
function export_format_priority( $p_priority ) {
	return get_enum_element( 'priority', $p_priority );
}

/**
 * Gets the formatted severity.
 * @param int $p_severity severity id.
 * @return string the severity text.
 */
function export_format_severity( $p_severity ) {
	return get_enum_element( 'severity', $p_severity );
}

/**
 * Gets the formatted reproducibility.
 * @param int $p_reproducibility reproducibility id.
 * @return string the reproducibility text.
 */
function export_format_reproducibility( $p_reproducibility ) {
	return get_enum_element( 'reproducibility', $p_reproducibility );
}

/**
 * Gets the formatted view state,
 * @param int $p_view_state The view state (e.g. public vs. private)
 * @return string The view state
 */
function export_format_view_state( $p_view_state ) {
	return get_enum_element( 'view_state', $p_view_state );
}

/**
 * Gets the formatted projection.
 * @param int $p_projection projection id.
 * @return string the projection text.
 */
function export_format_projection( $p_projection ) {
	return get_enum_element( 'projection', $p_projection );
}

/**
 * Gets the formatted eta.
 * @param int $p_eta eta id.
 * @return string the eta text.
 */
function export_format_eta( $p_eta ) {
	return get_enum_element( 'eta', $p_eta );
}

/**
 * Gets the status field.
 * @param int $p_status The status field.
 * @return string the formatted status.
 */
function export_format_status( $p_status ) {
	return get_enum_element( 'status', $p_status );
}

/**
 * Gets the resolution field.
 * @param int $p_resolution The resolution field.
 * @return string the formatted resolution.
 */
function export_format_resolution( $p_resolution ) {
	return get_enum_element( 'resolution', $p_resolution );
}

/**
 * Gets the formatted version.
 * @param string $p_version The product version
 * @return string the product version.
 */
function export_format_version( $p_version ) {
	return $p_version;
}

/**
 * Gets the formatted fixed in version.
 * @param string $p_fixed_in_version The product fixed in version
 * @return string the fixed in version.
 */
function export_format_fixed_in_version( $p_fixed_in_version ) {
	return $p_fixed_in_version;
}

/**
 * Gets the formatted target version.
 * @param string $p_target_version The target version
 * @return string the target version.
 */
function export_format_target_version( $p_target_version ) {
	return $p_target_version;
}

/**
 * Gets the formatted category.
 * @param int $p_category_id The category ID
 * @return string the category.
 */
function export_format_category_id( $p_category_id ) {
	return category_full_name( $p_category_id, false );
}

/**
 * Gets the formatted operating system.
 * @param string $p_os The operating system
 * @return string the operating system.
 */
function export_format_os( $p_os ) {
	return $p_os;
}

/**
 * Gets the formatted operating system build (version).
 * @param string $p_os_build The operating system build (version)
 * @return string the operating system build (version)
 */
function export_format_os_build( $p_os_build ) {
	return $p_os_build;
}

/**
 * Gets the formatted product build,
 * @param string $p_build The product build
 * @return string the product build.
 */
function export_format_build( $p_build ) {
	return $p_build;
}

/**
 * Gets the formatted platform,
 * @param string $p_platform The platform
 * @return string the platform.
 */
function export_format_platform( $p_platform ) {
	return $p_platform;
}

/**
 * Gets the formatted date submitted.
 * @param int $p_date_submitted The date submitted
 * @return string the date submitted in short date format.
 */
function export_format_date_submitted( $p_date_submitted ) {
	return date( config_get( 'short_date_format' ), $p_date_submitted );
}

/**
 * Gets the formatted date last updated.
 * @param int $p_last_updated The date last updated.
 * @return string the date last updated in short date format.
 */
function export_format_last_updated( $p_last_updated ) {
	return date( config_get( 'short_date_format' ), $p_last_updated );
}

/**
 * Gets the summary field.
 * @param string $p_summary The summary.
 * @return string The formatted summary.
 */
function export_format_summary( $p_summary ) {
	return $p_summary;
}

/**
 * Gets the formatted selection.
 * @param mixed $p_param The selection value
 * @return string An formatted empty string.
 */
function export_format_selection( $p_param ) {
	return '';
}

/**
 * Gets the formatted description field.
 * @param string $p_description The description.
 * @return string The formatted description (multi-line).
 */
function export_format_description( $p_description ) {
	return $p_description;
}

/**
 * Gets the formatted 'steps to reproduce' field.
 * @param string $p_steps_to_reproduce The steps to reproduce.
 * @return string The formatted steps to reproduce (multi-line).
 */
function export_format_steps_to_reproduce( $p_steps_to_reproduce ) {
	return $p_steps_to_reproduce;
}

/**
 * Gets the formatted 'additional information' field.
 * @param string $p_additional_information The additional information field.
 * @return string The formatted additional information (multi-line).
 */
function export_format_additional_information( $p_additional_information ) {
	return $p_additional_information;
}

/**
 * Gets the formatted value for the specified issue id, project and custom field.
 * @param int $p_issue_id The issue id.
 * @param int $p_project_id The project id.
 * @param string $p_custom_field The custom field name (without 'custom_' prefix).
 * @return string The custom field value.
 */
function export_format_custom_field( $p_issue_id, $p_project_id, $p_custom_field ) {
	$t_field_id = custom_field_get_id_from_name( $p_custom_field );

	if( $t_field_id === false ) {
		return '@' . $p_custom_field . '@';
	}

	if( custom_field_is_linked( $t_field_id, $p_project_id ) ) {
		$t_def = custom_field_get_definition( $t_field_id );
		return string_custom_field_value( $t_def, $t_field_id, $p_issue_id );
	}

	// field is not linked to project
	return '';
}

/**
 * Gets the formatted due date.
 * @param int $p_due_date The due date.
 * @return string The formatted due date.
 */
function export_format_due_date( $p_due_date ) {
	return date( config_get( 'short_date_format' ), $p_due_date );
}

/**
 * return the sponsorship total for an issue
 * @param int $p_sponsorship_total
 * @return string
 * @access public
 */
function export_format_sponsorship_total( $p_sponsorship_total ) {
	return number_format( $p_sponsorship_total );
}