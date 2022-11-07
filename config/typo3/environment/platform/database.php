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

use Bartacus\Bundle\BartacusBundle\Bootstrap\SymfonyBootstrap;
use Platformsh\ConfigReader\Config;

(static function (): void {
    /** @noinspection NullPointerExceptionInspection */
    /** @var Config $config */
    $config = SymfonyBootstrap::getKernel()->getContainer()->get(Config::class);

    if ($config->inRuntime() && $config->hasRelationship('database')) {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = array_merge(
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'],
            $config->formattedCredentials('database', 'typo3_mysql')
        );

        // ensure that the sql mode is set to NON strict, this should normally set in the LocalConfiguration.php
        if (empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'])) {
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'] = 'SET SESSION sql_mode = \'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION\'';
        }
    }
})();
