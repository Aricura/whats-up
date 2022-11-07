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

namespace App\Config;

use App\Domain\CustomEntityInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtbaseConfigLoader
{
    private const CACHE_KEY = 'extbase-domain-config';

    /**
     * @throws NoSuchCacheException|\ReflectionException
     */
    public function getConfiguration(): array
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

        $cache = $cacheManager->getCache('extbase');
        $persistenceConfig = $cache->get(self::CACHE_KEY);

        if (!$persistenceConfig) {
            $persistenceConfig = $this->buildConfigArray();
        }

        return $persistenceConfig;
    }

    private function buildConfigArray(): array
    {
        $persistenceConfigArray = [];

        $srcPath = rtrim(Environment::getProjectPath(), '/').'/src';
        $modelDirectories = (new Finder())->directories()->in($srcPath)->name('Model');

        /** @var SplFileInfo $directoryInfo */
        foreach ($modelDirectories as $directoryInfo) {
            $phpFiles = (new Finder())->in($directoryInfo->getPath())->name('*.php');

            /** @var SplFileInfo $classFile */
            foreach ($phpFiles as $classFile) {
                /** @var CustomEntityInterface $className */
                $className = trim(str_replace([$srcPath, '/', '.php'], ['App', '\\', ''], $classFile->getRealPath()));
                $classReflection = new \ReflectionClass($className);

                if ($classReflection->implementsInterface(CustomEntityInterface::class)) {
                    $persistenceConfigArray[(string) $className] = [
                        'tableName' => $className::getTableName(),
                        'recordType' => $className::getRecordType(),
                    ];
                }
            }
        }

        return $persistenceConfigArray;
    }
}
