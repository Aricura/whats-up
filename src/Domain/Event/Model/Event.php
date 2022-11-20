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

namespace App\Domain\Event\Model;

use App\Domain\AbstractEntity;
use DateTimeImmutable;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Event extends AbstractEntity
{
    protected ?string $title = null;
    protected ?string $source = null;
    protected ?int $startDate = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\App\Domain\Event\Model\EventLocation>
     */
    protected ?ObjectStorage $location = null;

    public static function getTableName(): string
    {
        return 'tx_events';
    }

    public static function getRecordType(): string
    {
        return static::class;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getStartDate(): ?int
    {
        return $this->startDate;
    }

    public function getStartTime(): string
    {
        if (!$this->getStartDate()) {
            return '';
        }

        $date = new DateTimeImmutable();
        $date->setTimestamp($this->getStartDate());

        return $date->format('H:i');
    }

    public function getLocation(): ?EventLocation
    {
        return $this->location?->offsetGet(0);
    }
}
