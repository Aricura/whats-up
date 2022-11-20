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

namespace App\Domain\Event\Repository;

use App\Domain\AbstractRepository;
use App\Domain\Event\Model\Event;
use DateTimeImmutable;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use function sprintf;

class EventRepository extends AbstractRepository
{
    /**
     * @return Event[]
     */
    public function getUpcomingEvents(): array
    {
        $query = $this->createQuery();
        $matching = [];

        try {
            // reset the time so we fetch all events from this day
            $startDay = new DateTimeImmutable();
            $startDay->setTime(0, 0);
            $matching[] = $query->greaterThanOrEqual('start_date', $startDay->getTimestamp());
        } catch (InvalidQueryException) {
        }

        $query->matching($query->logicalAnd($matching));

        $query->setOrderings([
            'start_date' => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute()->toArray();
    }

    /**
     * @return Event[]
     */
    public function getNextEvents(DateTimeImmutable $startDay, int $weeks): array
    {
        $query = $this->createQuery();
        $matching = [];

        try {
            // reset the time so we fetch all events from this day
            $startDay->setTime(0, 0);
            $matching[] = $query->greaterThanOrEqual('start_date', $startDay->getTimestamp());
        } catch (InvalidQueryException) {
        }

        try {
            // add an offset for the end
            $endDay = $startDay->modify(sprintf('+%d weeks', abs($weeks)));
            $matching[] = $query->lessThanOrEqual('start_date', $endDay->getTimestamp());
        } catch (InvalidQueryException) {
        }

        $query->matching($query->logicalAnd($matching));

        $query->setOrderings([
            'start_date' => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute()->toArray();
    }
}
