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

$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,jpeg,png';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['videofile_local_ext'] = 'mp4';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['videofile_external_ext'] = 'youtube,vimeo';

$GLOBALS['TYPO3_CONF_VARS']['GFX']['mediafile_ext'] = implode(',', [
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['videofile_local_ext'],
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['videofile_external_ext'],
]);

$GLOBALS['TYPO3_CONF_VARS']['GFX']['mediafile_local_ext'] = implode(',', [
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['videofile_local_ext'],
]);

$GLOBALS['TYPO3_CONF_VARS']['GFX']['mediafile_external_ext'] = implode(',', [
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
    $GLOBALS['TYPO3_CONF_VARS']['GFX']['videofile_external_ext'],
]);
