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
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.
 */

/**
 * Mantis Graph plugin
 */
class MantisGraphPlugin extends MantisPlugin  {
	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register( ) {
		$this->name = d___('plugin_MantisGraph', 'Mantis Graphs');
		$this->description = d___('plugin_MantisGraph', 'Official graph plugin.');
		$this->page = 'config';

		$this->version = '2.0.0';
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
	function config() {
		return array(
			'eczlibrary' => ON,

			'window_width' => 800,
			'bar_aspect' => 0.9,
			'summary_graphs_per_row' => 2,
			'font' => 'arial',

			'jpgraph_path' => '',
			'jpgraph_antialias' => ON,
		);
	}

	/**
	 * init function
	 */
	function init() {
		spl_autoload_register( array( 'MantisGraphPlugin', 'autoload' ) );

		$t_path = config_get_global('plugin_path' ). plugin_get_current() . '/core/';

		set_include_path(get_include_path() . PATH_SEPARATOR . $t_path);
	}

	/**
	 * class auto loader
	 * @param string $p_class class name
	 */
	public static function autoload( $p_class ) {
		if (class_exists( 'ezcBase' ) ) {
			ezcBase::autoload( $p_class );
		}
	}

	/**
	 * plugin hooks
	 */
	function hooks( ) {
		$hooks = array(
			'EVENT_MENU_SUMMARY' => 'summary_menu',
			'EVENT_SUBMENU_SUMMARY' => 'summary_submenu',
			'EVENT_MENU_FILTER' => 'graph_filter_menu',
		);
		return $hooks;
	}

	/**
	 * generate summary menu
	 */
	function summary_menu( ) {
		return array( '<a href="' . plugin_page( 'summary_jpgraph_page' ) . '">' . d___('plugin_MantisGraph', 'Advanced Summary') . '</a>', );
	}

	/**
	 * generate graph filter menu
	 */
	function graph_filter_menu( ) {
		return array( '<a href="' . plugin_page( 'bug_graph_page.php' ) . '">' . d___('plugin_MantisGraph', 'Graph') . '</a>', );
	}

	/**
	 * generate summary submenu
	 */
	function summary_submenu( ) {
		$t_url = helper_mantis_url( 'themes/' . config_get( 'theme' ) . '/images/' );
		return array( '<a href="' . helper_mantis_url( 'summary_page.php' ) . '"><img src="' . $t_url . 'synthese.png" alt="" />' . d___('plugin_MantisGraph', 'Synthesis') . '</a>',
			'<a href="' . plugin_page( 'summary_graph_imp_status.php' ) . '"><img src="' . $t_url . 'synthgraph.png" alt="" />' . d___('plugin_MantisGraph', 'Per state') . '</a>',
			'<a href="' . plugin_page( 'summary_graph_imp_priority.php' ) . '"><img src="' . $t_url . 'synthgraph.png" alt="" />' . d___('plugin_MantisGraph', 'Per priority') . '</a>',
			'<a href="' . plugin_page( 'summary_graph_imp_severity.php' ) . '"><img src="' . $t_url . 'synthgraph.png" alt="" />' . d___('plugin_MantisGraph', 'Per severity') . '</a>',
			'<a href="' . plugin_page( 'summary_graph_imp_category.php' ) . '"><img src="' . $t_url . 'synthgraph.png" alt="" />' . d___('plugin_MantisGraph', 'Per category') . '</a>',
			'<a href="' . plugin_page( 'summary_graph_imp_resolution.php' ) . '"><img src="' . $t_url . 'synthgraph.png" alt="" />' . d___('plugin_MantisGraph', 'Per resolution') . '</a>',
 		);
	}
}