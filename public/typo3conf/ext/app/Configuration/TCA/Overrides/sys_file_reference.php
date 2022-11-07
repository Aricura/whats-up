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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || exit();

call_user_func(static function (): void {
    // Palettes

    //<editor-fold desc="Palette: customImagePalette" defaultstate="collapsed">
    $GLOBALS['TCA']['sys_file_reference']['palettes']['customImagePalette']['showitem'] = '';

    ExtensionManagementUtility::addFieldsToPalette(
            'sys_file_reference',
            'customImagePalette',
            'title,alternative,--linebreak--,crop'
        );
    //</editor-fold>

    //<editor-fold desc="Palette: customVideoPalette" defaultstate="collapsed">
    $GLOBALS['TCA']['sys_file_reference']['palettes']['customVideoPalette']['showitem'] = '';

    ExtensionManagementUtility::addFieldsToPalette(
            'sys_file_reference',
            'customVideoPalette',
            ''
        );
    //</editor-fold>
});
