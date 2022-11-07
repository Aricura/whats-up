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

defined('TYPO3_MODE') || exit();

call_user_func(static function (): void {
    // Fields

    //<editor-fold desc="Field: header" defaultstate="collapsed">
    $GLOBALS['TCA']['tt_content']['columns']['header']['config']['type'] = 'text';
    $GLOBALS['TCA']['tt_content']['columns']['header']['config']['rows'] = 1;
    $GLOBALS['TCA']['tt_content']['columns']['header']['config']['cols'] = 30;
    //</editor-fold>

    //<editor-fold desc="Field: colPos" defaultstate="collapsed">
    $GLOBALS['TCA']['tt_content']['columns']['colPos']['exclude'] = false;
    //</editor-fold>

    //<editor-fold desc="Field: image" defaultstate="collapsed">
    $GLOBALS['TCA']['tt_content']['columns']['image']['config']['appearance']['fileUploadAllowed'] = false;
    //</editor-fold>

    //<editor-fold desc="Field: bodytext" defaultstate="collapsed">
    $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['enableRichtext'] = true;
    //</editor-fold>

});
