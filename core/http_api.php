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
 * HTTP API
 *
 * Provides functions to manage HTTP response headers.
 *
 * @package CoreAPI
 * @subpackage HTTPAPI
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses config_api.php
 */

require_api( 'config_api.php' );

/**
 * Check to see if the client is using Microsoft Internet Explorer so we can
 * enable quirks and hacky non-standards-compliant workarounds.
 * @return boolean True if Internet Explorer is detected as the user agent
 */
function is_browser_internet_explorer() {
	$t_user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'none';

	if ( strpos( $t_user_agent, 'MSIE' ) ) {
		return true;
	}

	return false;
}

/**
 * Checks to see if the client is using Google Chrome so we can enable quirks
 * and hacky non-standards-compliant workarounds.
 * @return boolean True if Chrome is detected as the user agent
 */
function is_browser_chrome() {
	$t_user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'none';

	if ( strpos( $t_user_agent, 'Chrome/' ) ) {
		return true;
	}

	return false;
}

function is_browser_mobile() {
  $t_user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'none';
  if(preg_match( '/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $t_user_agent ) || 
     preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i', substr( $t_user_agent, 0, 4 ) ) ) {
    return true;
  } else {
    return false;
  }
}

/**
 * Send a Content-Disposition header. This is more complex than it sounds
 * because only a few browsers properly support RFC2231. For those browsers
 * which are behind the times or are otherwise broken, we need to use
 * some hacky workarounds to get them to work 'nicely' with attachments and
 * inline files. See http://greenbytes.de/tech/tc2231/ for full reasoning.
 * @param string Filename
 * @param boolean Display file inline (optional, default = treat as attachment)
 */
function http_content_disposition_header( $p_filename, $p_inline = false ) {
	if ( !headers_sent() ) {
		$t_encoded_filename = rawurlencode( $p_filename );
		$t_disposition = '';
		if ( !$p_inline ) {
			$t_disposition = 'attachment;';
		}
		if ( is_browser_internet_explorer() || is_browser_chrome() ) {
			// Internet Explorer does not support RFC2231 however it does
			// incorrectly decode URL encoded filenames and we can use this to
			// get UTF8 filenames to work with the file download dialog. Chrome
			// behaves in the same was as Internet Explorer in this respect.
			// See http://greenbytes.de/tech/tc2231/#attwithfnrawpctenclong
			header( 'Content-Disposition:' . $t_disposition . ' filename="' . $t_encoded_filename . '"' );
		} else {
			// For most other browsers, we can use this technique:
			// http://greenbytes.de/tech/tc2231/#attfnboth2
			header( 'Content-Disposition:' . $t_disposition . ' filename*=UTF-8\'\'' . $t_encoded_filename . '; filename="' . $t_encoded_filename . '"' );
		}
	}
}

/**
 * Set caching headers that will allow or prevent browser caching.
 * @param boolean Allow caching
 */
function http_caching_headers( $p_allow_caching=false ) {
	global $g_allow_browser_cache;

	// Headers to prevent caching
	// with option to bypass if running from script
	if ( !headers_sent() ) {
		if ( $p_allow_caching || ( isset( $g_allow_browser_cache ) && ON == $g_allow_browser_cache ) ) {
			if ( is_browser_internet_explorer() ) {
				header( 'Cache-Control: private, proxy-revalidate' );
			} else {
				header( 'Cache-Control: private, must-revalidate' );
			}
		} else {
			header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		}

		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s \G\M\T', time() ) );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', time() ) );
	}
}

/**
 * Set content-type headers.
 */
function http_content_headers() {
	if ( !headers_sent() ) {
		// Only use the application/xhtml+xml MIME type if the browser
		// has indicated support for this type. Internet Explorer
		// prior to version 9 only supports the text/html MIME type.
		if ( stristr( $_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml' ) ) {
			header( 'Content-Type: application/xhtml+xml; charset=UTF-8' );
		} else {
			header( 'Content-Type: text/html; charset=UTF-8' );
		}

		// Disallow Internet Explorer from attempting to second guess the Content-Type
		// header as per http://blogs.msdn.com/ie/archive/2008/07/02/ie8-security-part-v-comprehensive-protection.aspx
		header( 'X-Content-Type-Options: nosniff' );
	}
}

/**
 * Set security headers (frame busting, clickjacking/XSS/CSRF protection).
 */
function http_security_headers() {
	if ( !headers_sent() ) {
		header( 'X-Frame-Options: DENY' );
		$t_avatar_img_allow = '';
		if ( config_get_global( 'show_avatar' ) ) {
			if ( isset( $_SERVER['HTTPS'] ) && ( utf8_strtolower( $_SERVER['HTTPS'] ) != 'off' ) ) {
				$t_avatar_img_allow = "; img-src 'self' https://secure.gravatar.com:443";
			} else {
				$t_avatar_img_allow = "; img-src 'self' http://www.gravatar.com:80";
			}
		}
		header( "X-Content-Security-Policy: allow 'self';$t_avatar_img_allow; frame-ancestors 'none'" );
		if ( isset( $_SERVER['HTTPS'] ) && ( utf8_strtolower( $_SERVER['HTTPS'] ) != 'off' ) ) {
			header( 'Strict-Transport-Security: max-age=7776000' );
		}
	}
}

/**
 * Load and set any custom headers defined by the site configuration.
 */
function http_custom_headers() {
	if ( !headers_sent() ) {
		// send user-defined headers
		foreach( config_get_global( 'custom_headers' ) as $t_header ) {
			header( $t_header );
		}
	}
}

/**
 * Set all headers used by a normal page load.
 */
function http_all_headers() {
	global $g_bypass_headers;

	if ( !$g_bypass_headers && !headers_sent() ) {
		http_content_headers();
		http_caching_headers();
		http_security_headers();
		http_custom_headers();
	}
}
