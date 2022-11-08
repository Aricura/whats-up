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

use App\Domain\Event\Model\EventCategory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

return [
    'ctrl' => [
        'title' => 'Event Category',
        'label' => 'title',
        'label_alt' => 'color_code',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'dividers2tabs' => '1',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'searchFields' => implode(',', [
            'title',
        ]),
        'hideTable' => false,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => ExtensionManagementUtility::extPath('app', '/Resources/Public/Icons/category.png'),
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => EventCategory::getTableName(),
                'foreign_table_where' => 'AND '.EventCategory::getTableName().'.pid=###CURRENT_PID### AND '.EventCategory::getTableName().'.sys_language_uid IN (-1,0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,required',
                'max' => 128,
            ],
        ],
        'color_code' => [
            'label' => 'Color Code (HEX)',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,required',
                'max' => 7,
                'default' => '#',
                'placeholder' => '#ffaa00',
            ],
        ],
    ],
    'palettes' => [
        'hidden_palette' => [
            'showitem' => 'sys_language_uid',
            'isHiddenPalette' => true,
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => implode(',', [
                '--palette--;;hidden_palette',
                'title',
                'color_code',
            ]),
        ],
    ],
];
