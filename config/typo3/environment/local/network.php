<?php

declare(strict_types=1);

/*
 * This file is part of the What's Up.

 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.+';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceReferrer'] = false;

if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] = '*';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxySSL'] = '*';
}
