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

namespace App\Import\Events;

use App\Domain\Event\Model\Event;
use TYPO3\CMS\Core\Database\ConnectionPool;
use function time;

class EventCleanupService
{
    private ConnectionPool $connectionPool;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    public function cleanup(): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(Event::getTableName());
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->update(Event::getTableName())
            ->set('deleted', 1)
            ->where($queryBuilder->expr()->lt('start_date', time()))
        ;

        try {
            $queryBuilder->execute();
        } catch (\Exception) {
        }
    }
}
