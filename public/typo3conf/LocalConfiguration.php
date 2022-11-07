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

return [
    'BE' => [
        'explicitADmode' => 'explicitAllow',
        'installToolPassword' => '$argon2i$v=19$m=65536,t=16,p=2$S3NBaXRDUVliQ2djLm4zWA$EqqnobGZ8aMf/Xw4SDut6LoYaKkw7k606kxUBT6sy78',
    ],
    'DB' => [
        'Connections' => [
            'Default' => [
                'charset' => 'utf8mb4',
                'dbname' => 'main',
                'driver' => 'mysqli',
                'host' => 'database.internal',
                'initCommands' => 'SET SESSION sql_mode = \'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION\'',
                'password' => '',
                'port' => 3306,
                'tableoptions' => [
                    'charset' => 'utf8mb4',
                    'collate' => 'utf8mb4_unicode_ci',
                ],
                'user' => 'user',
            ],
        ],
    ],
    'EXTCONF' => [
        'helhum-typo3-console' => [
            'initialUpgradeDone' => '11.5',
        ],
    ],
    'EXTENSIONS' => [
        'extensionmanager' => [
            'automaticInstallation' => '1',
            'offlineMode' => '0',
        ],
        'scheduler' => [
            'maxLifetime' => '1440',
            'showSampleTasks' => '1',
        ],
    ],
    'GFX' => [
        'processor' => 'GraphicsMagick',
    ],
    'MAIL' => [
        'transport_sendmail_command' => '/opt/mailhog/mhsendmail',
        'transport_smtp_encrypt' => '',
        'transport_smtp_server' => '',
    ],
    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'imagesizes' => [
                    'options' => [
                        'compression' => true,
                    ],
                ],
                'pages' => [
                    'options' => [
                        'compression' => true,
                    ],
                ],
                'rootline' => [
                    'options' => [
                        'compression' => true,
                    ],
                ],
            ],
        ],
        'devIPmask' => '',
        'displayErrors' => 0,
        'encryptionKey' => '66ef69f1959bd85d6cc9298062b0fb071b3d169c4c19f1637c3f4562f4c258adcd66bb243458cd503c74a0d90bf35f50',
        'errorHandlerErrors' => 30464,
        'exceptionalErrors' => 4096,
        'features' => [
            'felogin.extbase' => true,
            'fluidBasedPageModule' => true,
            'rearrangedRedirectMiddlewares' => true,
            'unifiedPageTranslationHandling' => true,
        ],
        'sitename' => "What's Up",
        'systemMaintainers' => [
            1,
        ],
    ],
];
