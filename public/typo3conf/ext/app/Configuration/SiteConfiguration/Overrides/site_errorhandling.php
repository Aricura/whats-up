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

use App\Error\AbstractPageErrorHandler;

defined('TYPO3_MODE') || exit();

call_user_func(static function (): void {
    $GLOBALS['SiteConfiguration']['site_errorhandling']['columns'][AbstractPageErrorHandler::PAGE_ID_FIELD] = [
        'label' => 'LLL:EXT:app/Resources/Private/Language/locallang_siteconfiguration.xlf:errorHandling.errorPageId.label',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'pages',
            'maxitems' => 1,
            'minitems' => 0,
            'size' => 1,
            'default' => 1,
            'suggestOptions' => [
                'default' => [
                    'additionalSearchFields' => 'nav_title, alias, url',
                    'addWhere' => 'AND pages.doktype = 1 AND pages.hidden=0 and pages.deleted=0',
                ],
            ],
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_errorhandling']['types']['PHP']['showitem'] .= ','.implode(',', [AbstractPageErrorHandler::PAGE_ID_FIELD]);
});
