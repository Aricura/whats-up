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

use App\Domain\Enum\CountryEnum;
use App\Domain\Event\Model\EventLocation;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

return [
    'ctrl' => [
        'title' => 'Event Location',
        'label' => 'name',
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
            'name',
            'postal_code',
            'city',
            'street',
            'additional_information',
            'phone_number',
            'email_address',
        ]),
        'hideTable' => false,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => ExtensionManagementUtility::extPath('app', '/Resources/Public/Icons/location.png'),
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
                'foreign_table' => EventLocation::getTableName(),
                'foreign_table_where' => 'AND '.EventLocation::getTableName().'.pid=###CURRENT_PID### AND '.EventLocation::getTableName().'.sys_language_uid IN (-1,0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'name' => [
            'label' => 'Name',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,required',
                'max' => 128,
            ],
        ],
        'country_code' => [
            'label' => 'Country',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['-- unknown --', CountryEnum::UNKNOWN],
                    ['Austria', CountryEnum::AUSTRIA],
                    ['Germany', CountryEnum::GERMANY],
                ],
                'default' => CountryEnum::UNKNOWN,
            ],
        ],
        'postal_code' => [
            'label' => 'Postal Code',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 16,
            ],
        ],
        'city' => [
            'label' => 'City',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 64,
            ],
        ],
        'street' => [
            'label' => 'Street + House Number',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'additional_information' => [
            'label' => 'Additional Information',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'phone_number' => [
            'label' => 'Phone Number',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 64,
            ],
        ],
        'email_address' => [
            'label' => 'Email Address',
            'config' => [
                'type' => 'input',
                'eval' => 'email',
                'max' => 128,
            ],
        ],
        'online' => [
            'label' => 'Online / Virtual Location?',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => 'no',
                        1 => 'yes',
                    ],
                ],
            ],
        ],
        'latitude' => [
            'label' => 'Latitude',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'eval' => 'double2,null',
            ],
        ],
        'longitude' => [
            'label' => 'Longitude',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'eval' => 'double2,null',
            ],
        ],
    ],
    'palettes' => [
        'hidden_palette' => [
            'showitem' => 'sys_language_uid',
            'isHiddenPalette' => true,
        ],
        'general' => [
            'showitem' => implode(',', [
                'name',
                'online',
            ]),
        ],
        'address' => [
            'showitem' => implode(',', [
                'country_code',
                '--linebreak--',
                'postal_code',
                'city',
                '--linebreak--',
                'street',
                'additional_information',
            ]),
        ],
        'contact' => [
            'showitem' => implode(',', [
                'phone_number',
                'email_address',
            ]),
        ],
        'geo' => [
            'showitem' => implode(',', [
                'longitude',
                'latitude',
            ]),
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => implode(',', [
                '--palette--;;hidden_palette',
                '--palette--;General;general',
                '--palette--;Address;address',
                '--palette--;Contact;contact',
                '--palette--;Geo-Location;geo',
            ]),
        ],
    ],
];
