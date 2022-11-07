<?php

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

use Bartacus\Bundle\BartacusBundle\Bootstrap\SymfonyBootstrap;
use Bartacus\Bundle\BartacusBundle\Typo3\SymfonyServiceForMakeInstanceLoader;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || exit();

(static function (Symfony\Component\DependencyInjection\ContainerInterface $container): void {
    // fixes issues in the maintenance/install tools
    /* @noinspection NullPointerExceptionInspection */
    $container->get(SymfonyServiceForMakeInstanceLoader::class)->load();

    // define TypoScript as content rendering template
    $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'app/Configuration/TypoScript/';

    // inject PageTS configuration
    ExtensionManagementUtility::addPageTSConfig('@import \'EXT:app/Configuration/TsConfig/Page/index.tsconfig\'');

    // add default RTE configuration
    if (file_exists(ExtensionManagementUtility::extPath('app', 'Configuration/RTE/Presets/Default.yaml'))) {
        $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:app/Configuration/RTE/Presets/Default.yaml';
    }
})(SymfonyBootstrap::getKernel()->getContainer());
