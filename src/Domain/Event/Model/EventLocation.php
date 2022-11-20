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

class EventLocation extends AbstractEntity
{
    private const COLORS = [
        'green',
        'blue',
        'yellow',
        'orange',
        'red',
    ];

    protected ?string $name = null;

    public static function getTableName(): string
    {
        return 'tx_event_locations';
    }

    public static function getRecordType(): string
    {
        return static::class;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getColor(): string
    {
        return self::COLORS[$this->getUid()] ?? 'green';
    }
}
