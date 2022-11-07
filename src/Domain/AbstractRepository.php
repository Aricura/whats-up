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

namespace App\Domain;

use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;

abstract class AbstractRepository extends Repository
{
    public function __construct(ObjectManagerInterface $objectManager, PersistenceManager $persistenceManager)
    {
        // Retain objectType before the parent __construct force sets the objectType
        $objectType = $this->objectType;

        parent::__construct($objectManager);
        $this->persistenceManager = $persistenceManager;

        $querySettings = $objectManager->get(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);

        // Override the objectType force set by the parent __construct
        if (!$objectType) {
            $objectType = preg_replace(
                ['/\\\\Repository\\\\/', '/Repository$/'],
                ['\\Model\\', ''],
                $this->getRepositoryClassName()
            );
        }

        $this->objectType = $objectType;
    }
}
