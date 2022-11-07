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
use TYPO3\CMS\Core\Cache\Backend\RedisBackend;

// Redis
(static function (): void {
    $groups = array_filter(array_map('trim', explode(',', (string) getenv('TYPO3_CACHE_DISABLE'))));

    $caches = [
        'pages',
        'pagesection',
        'hash',
        'extbase',
    ];

    /** @noinspection NullPointerExceptionInspection */
    /** @var Config $config */
    $config = SymfonyBootstrap::getKernel()->getContainer()->get(Config::class);

    if ($config->inRuntime() && $config->hasRelationship('rediscache')) {
        $credentials = $config->credentials('rediscache');

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $cache => $configuration) {
            if (
                in_array($cache, $caches, true) &&
                !(array_key_exists('groups', $configuration) && array_intersect($configuration['groups'], $groups))
            ) {
                $database = array_search($cache, $caches, true) + 3;

                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] = RedisBackend::class;
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'] = [
                    'database' => $database,
                    'hostname' => $credentials['host'],
                    'port' => $credentials['port'],
                ] + (array) $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'];
            }
        }
    }
})();
