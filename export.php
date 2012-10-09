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
 * Export page:
 *  Excel (OpenXML Format) 
 *  CSV Format
 *
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses authentication_api.php
 * @uses bug_api.php
 * @uses columns_api.php
 * @uses config_api.php
 * @uses export_api.php
 * @uses file_api.php
 * @uses filter_api.php
 * @uses gpc_api.php
 * @uses helper_api.php
 * @uses print_api.php
 * @uses utility_api.php
 */

require_once( 'core.php' );
require_api( 'authentication_api.php' );
require_api( 'bug_api.php' );
require_api( 'columns_api.php' );
require_api( 'config_api.php' );
require_api( 'export_api.php' );
require_api( 'file_api.php' );
require_api( 'filter_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'print_api.php' );
require_api( 'utility_api.php' );

/** PHPExcel */
require_lib( 'PHPExcel/PHPExcel.php' );

$f_export = gpc_get_string( 'export', '' );

$f_type = strtolower( gpc_get_string( 'type', 'csv' ) );

auth_ensure_user_authenticated();

helper_begin_long_process();

$t_short_date_format = config_get( 'short_date_format' );

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

$t_export_title = export_get_default_filename();

// Set properties
$objPHPExcel->getProperties()->setCreator("MantisBT")
							 ->setLastModifiedBy("MantisBT")
							 ->setTitle($t_export_title)
							 ->setSubject("MantisBT Export")
							 ->setDescription("MantisBT Export")
							 ->setKeywords("office 2007 openxml php");

$sheet = $objPHPExcel->setActiveSheetIndex(0);
							 
$headers = export_get_titles_row();

$index_pos = 0;
foreach($headers as $text){
	$sheet->setCellValue(num2alpha($index_pos) . '1', $text);
    $index_pos++;
}

/**
 * Convert number to a character representation for excel columns e.g. 1->A, 2->B
 * @param int $p_number Column number
 * @return string
 */
function num2alpha($p_number)
{ 
    for($t_string = ""; $p_number >= 0; $p_number = intval($p_number / 26) - 1)
        $t_string = chr($p_number%26 + 0x41) . $t_string;
    return $t_string;
} 


# This is where we used to do the entire actual filter ourselves
$t_page_number = gpc_get_int( 'page_number', 1 );
$t_per_page = 100;
$t_bug_count = null;
$t_page_count = null;

$result = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count );
if ( $result === false ) {
	print_header_redirect( 'view_all_set.php?type=0&print=1' );
}

$f_bug_arr = explode( ',', $f_export );

$t_columns = export_get_columns();

do
{
	$t_more = true;
	$t_row_count = count( $result );

	for( $i = 0; $i < $t_row_count; $i++ ) {
		$t_row = $result[$i];
		$t_bug = null;

		if ( is_blank( $f_export ) || in_array( $t_row->id, $f_bug_arr ) ) {
			$index_pos = 0;
			foreach($t_columns as $t_column){
				$t_custom_field = column_get_custom_field_name( $t_column );
				if ( $t_custom_field !== null ) {
					$sheet->setCellValue(num2alpha($index_pos) . ($i+2), excel_format_custom_field( $t_row->id, $t_row->project_id, $t_custom_field ) );
				} else {
					$t_function = 'export_format_' . $t_column;
					$sheet->setCellValue(num2alpha($index_pos) . ($i+2), $t_function( $t_row->$t_column ) );
				}
				$index_pos++;
			}
		} #in_array
	} #for loop

	// If got a full page, then attempt for the next one.
	// @@@ Note that since we are not using a transaction, there is a risk that we get a duplicate record or we miss
	// one due to a submit or update that happens in parallel.
	if ( $t_row_count == $t_per_page ) {
		$t_page_number++;
		$t_bug_count = null;
		$t_page_count = null;

		$result = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count );
		if ( $result === false ) {
			$t_more = false;
		}
	} else {
		$t_more = false;
	}
} while ( $t_more );

// Rename sheet
$objPHPExcel->getActiveSheet()->setTitle('Issues Export');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

switch( $f_type ) {
	case 'excel2007':
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . urlencode( file_clean_name( $t_export_title ) ) . '.xlsx"');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		break;
	case 'csv':
		header( 'Content-Type: text/plain' );
		header( 'Content-Transfer-Encoding: BASE64;' );
		header('Content-Disposition: attachment;filename="' . urlencode( file_clean_name( $t_export_title ) ) . '.csv"');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
		break;
}

header('Cache-Control: max-age=0');

//$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'HTML');
//$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
$objWriter->save('php://output');
exit;
