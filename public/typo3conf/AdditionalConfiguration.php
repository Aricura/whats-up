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
use Bartacus\Bundle\BartacusBundle\Config\ConfigLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;

(static function (Kernel $kernel): void {
    // get a collection of all paths to look for TYPO3 configuration files
    $configPaths = [];

    // fetch default TYPO3 configuration files
    $basePath = $kernel->getProjectDir().'/config/typo3';
    $configPaths[] = $basePath;

    // fetch Symfony context based TYPO3 configuration files
    if ($kernel->getEnvironment()) {
        $configPaths[] = $basePath.'/context/'.$kernel->getEnvironment();
    }

    // fetch environment based TYPO3 configuration files
    if (getenv('PLATFORM_APPLICATION')) {
        // fetch default platform.sh configuration files
        $configPaths[] = $basePath.'/environment/platform';

        // fetch files based on the current platform.sh branch name
        if (getenv('PLATFORM_BRANCH')) {
            $configPaths[] = $basePath.'/environment/platform/'.getenv('PLATFORM_BRANCH');
        }

        // fetch lando configuration files
        if ('lando' === getenv('PLATFORM_ENVIRONMENT')) {
            $configPaths[] = $basePath.'/environment/local';
        }
    }

    // fetch all files from all config paths and require them
    foreach ($configPaths as $path) {
        if (is_dir($path)) {
            $finder = (new Finder())
                ->files()
                ->ignoreDotFiles(true)
                ->depth(0)
                ->sortByName()
                ->in($path)
                ->name('*.php')
            ;

            foreach ($finder as $file) {
                require_once $file->getPathname();
            }
        }
    }

    // load additional configuration from other extensions
    /** @var ConfigLoader $loader */
    $loader = $kernel->getContainer()->get(ConfigLoader::class);
    $loader->loadFromAdditionalConfiguration();
})(SymfonyBootstrap::getKernel());
