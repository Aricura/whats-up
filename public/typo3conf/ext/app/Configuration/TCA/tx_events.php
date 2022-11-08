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

use App\Domain\Enum\EventSourceEnum;
use App\Domain\Enum\SeatedEnum;
use App\Domain\Event\Model\Event;
use App\Domain\Event\Model\EventCategory;
use App\Domain\Event\Model\EventLocation;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

return [
    'ctrl' => [
        'title' => 'Event',
        'label' => 'title',
        'label_alt' => 'start_date',
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
            'short_description',
            'source',
        ]),
        'hideTable' => false,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => ExtensionManagementUtility::extPath('app', '/Resources/Public/Icons/event.png'),
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
                'foreign_table' => Event::getTableName(),
                'foreign_table_where' => 'AND '.Event::getTableName().'.pid=###CURRENT_PID### AND '.Event::getTableName().'.sys_language_uid IN (-1,0)',
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
                'max' => 255,
            ],
        ],
        'slug' => [
            'label' => 'Slug / Url Path Segment',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                    'fieldSeparator' => '-',
                    'prefixParentPageSlug' => false,
                    'replacements' => [
                        '/' => '-',
                        '_' => '-',
                        ' ' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'default' => '',
            ],
        ],
        'short_description' => [
            'label' => 'Short Description / Intro Text',
            'config' => [
                'type' => 'text',
                'eval' => 'trim',
                'max' => 8000,
                'cols' => 80,
                'rows' => 5,
            ],
        ],
        'description' => [
            'label' => 'Description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
        'gallery' => [
            'label' => 'Gallery',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig('gallery', [
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'appearance' => [
                    'fileUploadAllowed' => false,
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference',
                ],
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.crop',
                            'config' => [
                                'type' => 'imageManipulation',
                                'cropVariants' => [
                                    'default' => [
                                        'title' => 'Default',
                                        'allowedAspectRatios' => [
                                            'default' => [
                                                'title' => '16:9',
                                                'value' => 16 / 9,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'types' => [
                        '0' => [
                            'showitem' => '
                                --palette--;;customImagePalette,
                                --palette--;;filePalette',
                        ],
                        File::FILETYPE_IMAGE => [
                            'showitem' => '
                                --palette--;;customImagePalette,
                                --palette--;;filePalette',
                        ],
                        File::FILETYPE_VIDEO => [
                            'showitem' => '
                                --palette--;;customVideoPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ], $GLOBALS['TYPO3_CONF_VARS']['GFX']['mediafile_ext']),
        ],
        'source' => [
            'label' => 'Source',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['-- inserted manually --', EventSourceEnum::MANUALLY],
                    ['Imported from jazzit website', EventSourceEnum::JAZZIT],
                    ['Imported from rockhouse website', EventSourceEnum::ROCKHOUSE],
                ],
                'default' => EventSourceEnum::MANUALLY,
                'readOnly' => true,
            ],
        ],
        'external_url' => [
            'label' => 'External Url',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
            ],
        ],
        'ticket_url' => [
            'label' => 'Ticketing Url',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
            ],
        ],
        'start_date' => [
            'label' => 'Start Date & Time',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
            ],
        ],
        'end_date' => [
            'label' => 'End Date & Time',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
            ],
        ],
        'location' => [
            'label' => 'Location',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['-- unknown --', 0],
                ],
                'foreign_table' => EventLocation::getTableName(),
                'foreign_table_where' => 'AND '.EventLocation::getTableName().'.sys_language_uid IN (-1,0)',
            ],
        ],
        'additional_location_information' => [
            'label' => 'Additional Location Information',
            'config' => [
                'type' => 'text',
                'eval' => 'trim',
                'max' => 8000,
                'cols' => 80,
                'rows' => 5,
            ],
        ],
        'categories' => [
            'label' => 'Categories',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [],
                'size' => 3,
                'multiple' => false,
                'foreign_table' => EventCategory::getTableName(),
                'foreign_table_where' => 'AND '.EventCategory::getTableName().'.sys_language_uid IN (-1,0)',
            ],
        ],
        'price_information' => [
            'label' => 'Price Information',
            'config' => [
                'type' => 'text',
                'eval' => 'trim',
                'max' => 255,
                'cols' => 80,
                'rows' => 5,
            ],
        ],
        'free_of_charge' => [
            'label' => 'Free of Charge?',
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
        'sold_out' => [
            'label' => 'Already sold out?',
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
        'pre_registration' => [
            'label' => 'Pre Registration recommended?',
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
        'seated' => [
            'label' => 'Seated Event?',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['-- unknown --', SeatedEnum::UNKNOWN],
                    ['Seated', SeatedEnum::SEATED],
                    ['Partially Seated', SeatedEnum::PARTIALLY_SEATED],
                    ['Standing', SeatedEnum::STANDING],
                ],
                'default' => SeatedEnum::UNKNOWN,
            ],
        ],
        'insider' => [
            'label' => 'Insider Event by Larissa?',
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
    ],
    'palettes' => [
        'hidden_palette' => [
            'showitem' => 'sys_language_uid',
            'isHiddenPalette' => true,
        ],
        'general' => [
            'showitem' => implode(',', [
                'title',
                'slug',
                '--linebreak--',
                'source',
                '--linebreak--',
                'categories',
            ]),
        ],
        'date' => [
            'showitem' => implode(',', [
                'start_date',
                'end_date',
            ]),
        ],
        'location' => [
            'showitem' => implode(',', [
                'location',
                '--linebreak--',
                'additional_location_information',
                '--linebreak--',
                'seated',
            ]),
        ],
        'pricing' => [
            'showitem' => implode(',', [
                'price_information',
                '--linebreak--',
                'free_of_charge',
                'pre_registration',
                'sold_out',
            ]),
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => implode(',', [
                '--palette--;;hidden_palette',
                'insider',
                '--palette--;General;general',
                '--palette--;Date;date',
                '--palette--;Location;location',
                '--div--;Content',
                'external_url',
                'short_description',
                'description',
                'gallery',
                '--div--;Pricing',
                'ticket_url',
                '--palette--;Pricing;pricing',
            ]),
        ],
    ],
];
