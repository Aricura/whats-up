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
use Bartacus\Bundle\PlatformshBundle\Route\RouteResolver;
use Platformsh\ConfigReader\Config;

// Domains
(static function (): void {
    /** @noinspection NullPointerExceptionInspection */
    /** @var Config $config */
    $config = SymfonyBootstrap::getKernel()->getContainer()->get(Config::class);

    if ($config->inRuntime()) {
        /** @noinspection NullPointerExceptionInspection */
        /** @var RouteResolver $routeResolver */
        $routeResolver = SymfonyBootstrap::getKernel()->getContainer()->get(RouteResolver::class);

        // mapping of site configs to platform.sh routes
        // add new sites here too, and the local domain in .env
        // convention for environment variable is TYPO3_BASE_DOMAIN_{SITENAME}
        $siteNames = [
            'main' => 'https://www.{default}/',
        ];

        foreach ($siteNames as $siteName => $originalUrl) {
            $route = $routeResolver->resolveRoute($originalUrl);
            Platformsh\FlexBridge\setEnvVar('TYPO3_BASE_DOMAIN_'.mb_strtoupper($siteName), (string) $route->resolvedUrl());
        }
    }
})();
