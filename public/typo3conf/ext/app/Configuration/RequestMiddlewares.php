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

use App\Middleware\LanguageRedirectMiddleware;
use App\Middleware\StaticRouteResolver;
use App\Middleware\TrailingSlashRedirectionMiddleware;
use Bartacus\Bundle\BartacusBundle\Bootstrap\SymfonyBootstrap;
use Bartacus\Bundle\BartacusBundle\Config\ConfigLoader;

return (static function (Symfony\Component\DependencyInjection\ContainerInterface $container) {
    /** @var ConfigLoader $configLoader */
    $configLoader = $container->get(ConfigLoader::class);

    $middlewares = $configLoader->loadFromRequestMiddlewares();

    $middlewares['frontend']['app/trailing-slash-redirection'] = [
        'target' => TrailingSlashRedirectionMiddleware::class,
        'before' => [
            'typo3/cms-frontend/base-redirect-resolver',
        ],
    ];

    $middlewares['frontend']['app/language-redirect-middleware'] = [
        'target' => LanguageRedirectMiddleware::class,
        'before' => [
            'typo3/cms-frontend/base-redirect-resolver',
        ],
    ];

    $middlewares['frontend']['typo3/cms-frontend/static-route-resolver']['target'] = StaticRouteResolver::class;

    return $middlewares;
})(SymfonyBootstrap::getKernel()->getContainer());
