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
 * XML Import / Export Plugin
 * @package MantisPlugin
 * @subpackage MantisPlugin
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

/**
 * XmlImportExportPlugin Class
 */
class XmlImportExportPlugin extends MantisPlugin {

	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register( ) {
		$this->name = d___('plugin_XmlImportExport', 'Import/Export issues');
		$this->description = d___('plugin_XmlImportExport', 'Adds XML based import and export capabilities to MantisBT.');
		$this->page = '';

		$this->version = '1.0';
		$this->requires = array(
			'MantisCore' => '2.0.0',
		);

		$this->author = 'MantisBT Team';
		$this->contact = 'mantisbt-dev@lists.sourceforge.net';
		$this->url = 'http://www.mantisbt.org';
	}

	/**
	 * Default plugin configuration.
	 */
	function hooks( ) {
		$hooks = array(
			'EVENT_MENU_MANAGE' => 'import_issues_menu',
			'EVENT_MENU_FILTER' => 'export_issues_menu',
		);
		return $hooks;
	}

	/**
	 * Import Issues Menu
	 */
	function import_issues_menu( ) {
		return array( '<a href="' . plugin_page( 'import' ) . '">' . d___('plugin_XmlImportExport', 'Import issues') . '</a>', );
	}

	/**
	 * Export Issues Menu
	 */
	function export_issues_menu( ) {
		return array( '<a href="' . plugin_page( 'export' ) . '">' . d___('plugin_XmlImportExport', 'XML Export') . '</a>', );
	}

	/**
	 * Plugin Installation
	 */
	function install() {
		$result = extension_loaded("xmlreader") && extension_loaded("xmlwriter");
		if ( ! $result ) {
			throw new MantisBT\Exception\Plugin\PluginInstallationFailed(  d___('plugin_XmlImportExport', 'Import/Export issues'), d___('plugin_XmlImportExport', 'can not find xmlreader and xmlwriter extensions') );
		}
		return $result;
	}
}
