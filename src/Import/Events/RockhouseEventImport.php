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

use App\Domain\Enum\EventSourceEnum;

class RockhouseEventImport extends AbstractEventImport
{
    protected static function getSource(): string
    {
        return EventSourceEnum::ROCKHOUSE;
    }

    protected static function getEventStoragePid(): int
    {
        return 438;
    }

    protected static function getSleepDuration(): int
    {
        return 500;
    }

    protected function getOverviewUrl(int $numOverviewPagesRead): string
    {
        return '';
    }

    protected function extractEntriesFromOverviewContent(string $overviewContent): array
    {
        return [];
    }

    protected function extractEventUrlFromOverviewEntry(array $overviewEntry): string
    {
       return '';
    }

    protected function extractEventDataFromDetailPageContent(string $detailPageContent, array $overviewEntry): array
    {
        return [];
    }

    protected function extractStartTimestampFromEventData(array $eventData): int
    {
        return 0;
    }

    protected function convertEventDataToDatabaseRecord(array $eventData): array
    {
        return [];
    }

    protected function getLocationId(): int
    {
        return 0;
    }
}
