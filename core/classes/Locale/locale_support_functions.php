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

/* GNU gettext's pgettext function implemented in PHP. Upstream definition:
 * http://git.savannah.gnu.org/cgit/gettext.git/tree/gnulib-local/lib/gettext.h
 *   pgettext(Msgctxt, Msgid)
 */
function pgettext($context, $message) {
	$contextPrefixedMessage = "{$context}\004{$message}";
	$translation = gettext($contextPrefixedMessage);
	if ($translation === $contextPrefixedMessage)
		return $message;
	else
		return $translation;
}

/* GNU gettext's npgettext function implemented in PHP. Upstream definition:
 * http://git.savannah.gnu.org/cgit/gettext.git/tree/gnulib-local/lib/gettext.h
 *   npgettext(Msgctxt, Msgid, MsgidPlural, N)
 */
function npgettext($context, $messageSingular, $messagePlural, $number) {
	$contextPrefixedMessageSingular = "{$context}\004{$messageSingular}";
	$contextPrefixedMessagePlural = "{$context}\004{$messagePlural}";
	$translation = ngettext($contextPrefixedMessageSingular, $contextPrefixedMessagePlural, $number);
	if ($translation === $contextPrefixedMessageSingular || $translation === $contextPrefixedMessagePlural)
		return $messageSingular;
	else
		return $translation;
}

/* GNU gettext's dpgettext function implemented in PHP. Upstream definition:
 * http://git.savannah.gnu.org/cgit/gettext.git/tree/gnulib-local/lib/gettext.h
 *   dpgettext(Domainname, Msgctxt, Msgid)
 */
function dpgettext($domain, $context, $message) {
	$contextPrefixedMessage = "{$context}\004{$message}";
	$translation = dgettext($domain, $contextPrefixedMessage);
	if ($translation === $contextPrefixedMessage)
		return $message;
	else
		return $translation;
}

/* GNU gettext's dnpgettext function implemented in PHP. Upstream definition:
 * http://git.savannah.gnu.org/cgit/gettext.git/tree/gnulib-local/lib/gettext.h
 *   dnpgettext(Domainname, Msgctxt, Msgid, MsgidPlural, N)
 */
function dnpgettext($domain, $context, $messageSingular, $messagePlural, $number) {
	$contextPrefixedMessageSingular = "{$context}\004{$messageSingular}";
	$contextPrefixedMessagePlural = "{$context}\004{$messagePlural}";
	$translation = dngettext($domain, $contextPrefixedMessageSingular, $contextPrefixedMessagePlural, $number);
	if ($translation === $contextPrefixedMessageSingular || $translation === $contextPrefixedMessagePlural)
		return $messageSingular;
	else
		return $translation;
}

/* Shortcut wrapper for pgettext/gettext
 */
function ___($message, $context = null) {
	if ($context !== null)
		return pgettext($context, $message);
	else
		return gettext($message);
}

/* Shortcut wrapper for npgettext/ngettext
 */
function n___($messageSingular, $messagePlural, $number, $context = null) {
	if ($context !== null)
		return npgettext($context, $messageSingular, $messagePlural, $number);
	else
		return ngettext($messageSingular, $messagePlural, $number);
}

/* Shortcut wrapper for dpgettext/dgettext
 */
function d___($domain, $message, $context = null) {
	if ($context !== null)
		return dpgettext($domain, $context, $message);
	else
		return dgettext($domain, $message);
}

/* Shortcut wrapper for dnpgettext/dngettext
 */
function dn___($domain, $messageSingular, $messagePlural, $number, $context = null) {
	if ($context !== null)
		return dnpgettext($domain, $context, $messageSingular, $messagePlural, $number);
	else
		return dngettext($domain, $messageSingular, $messagePlural, $number);
}
