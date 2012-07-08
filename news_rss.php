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
 * Generate News Feed RSS
 *
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses access_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses gpc_api.php
 * @uses lang_api.php
 * @uses news_api.php
 * @uses project_api.php
 * @uses rss_api.php
 * @uses string_api.php
 * @uses user_api.php
 * @uses utility_api.php
 */

require_once( 'core.php' );
require_api( 'access_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'gpc_api.php' );
require_api( 'lang_api.php' );
require_api( 'news_api.php' );
require_api( 'project_api.php' );
require_api( 'rss_api.php' );
require_api( 'string_api.php' );
require_api( 'user_api.php' );
require_api( 'utility_api.php' );
require_lib( 'ezc/Base/src/base.php' );

$f_username = gpc_get_string( 'username', null );
$f_key = gpc_get_string( 'key', null );
$f_project_id = gpc_get_int( 'project_id', ALL_PROJECTS );

news_ensure_enabled();

# make sure RSS syndication is enabled.
if ( OFF == config_get( 'rss_enabled' ) ) {
	throw new MantisBT\Exception\Access_Denied();
}

# authenticate the user
if ( $f_username !== null ) {
	if ( !rss_login( $f_username, $f_key ) ) {
		throw new MantisBT\Exception\Access_Denied();
	}
} else {
	if ( OFF == config_get( 'anonymous_login' ) ) {
		throw new MantisBT\Exception\Access_Denied();
	}
}

# Make sure that the current user has access to the selected project (if not ALL PROJECTS).
if ( $f_project_id != ALL_PROJECTS ) {
	access_ensure_project_level( VIEWER, $f_project_id );
}

# construct rss file
$about = config_get( 'path' );
$title = string_rss_links( config_get( 'window_title' ) . ' - ' . lang_get( 'news' ) );

if ( $f_username !== null ) {
	$title .= " - ($f_username)";
}

$description = $title;
$image_link = helper_mantis_url( 'themes/' . config_get( 'theme' ) . '/images/logo.png' )

# only rss 2.0
$category = string_rss_links( project_get_name( $f_project_id ) );

# in minutes (only rss 2.0)
#$cache = '60';

$feed = new ezcFeed();
$feed->title = $title;
$feed->description = $description;
$feed->generator = 'Mantis Bug Tracker';

$rsscat = $feed->add( 'category' );
$rsscat->term = $category;

$link = $feed->add( 'link' );
$link->href = $about;

$date = (string) date( 'r' );
#$language = lang_get( 'phpmailer_language' );
# hourly / daily / weekly / ...
#$period = (string) 'daily';
# every X hours/days/...
#$frequency = (int) 1;

#$base = (string) date('Y-m-d\TH:i:sO');

# add missing : in the O part of the date.  PHP 5 supports a 'c' format which will output the format
# exactly as we want it.
# // 2002-10-02T10:00:00-0500 -> // 2002-10-02T10:00:00-05:00
#$base = utf8_substr( $base, 0, 22 ) . ':' . utf8_substr( $base, -2 );

$news_rows = news_get_limited_rows( 0 /* offset */, $f_project_id );
$t_news_count = count( $news_rows );

# Loop through results
for ( $i = 0; $i < $t_news_count; $i++ ) {
	$row = $news_rows[$i];
	extract( $row, EXTR_PREFIX_ALL, 'v' );

	# skip news item if private, or
	# belongs to a private project (will only happen
	if ( VS_PRIVATE == $v_view_state ) {
		continue;
	}

	$v_headline 	= string_rss_links( $v_headline );
	$v_body 	= string_rss_links( $v_body );

	$about = $url = config_get( 'path' ) . "news_view_page.php?news_id=$v_id";
	$title = $v_headline;
	$description = $v_body;

	$item = $feed->add( 'item' );
	$item->title = $title;
	$item->description = $description;
	$item->published = $v_date_posted;

	# author of item	
	$t_author_name = string_rss_links( user_get_name( $v_poster_id ) );
	
	$lauthor = $item->add( 'author' );	
	$lauthor->name = $t_author_name;	
	if ( access_has_global_level( config_get( 'show_user_email_threshold' ) ) ) {
		$t_author_email = user_get_field( $v_poster_id, 'email' );

		if ( !is_blank( $t_author_email ) ) {
			$lauthor->name = $t_author_name . ' &lt;' . $t_author_email . '&gt;';
		}
	}

	$link = $item->add( 'link' );
	$link->href = $url;
}

echo $feed->generate( 'rss2' );