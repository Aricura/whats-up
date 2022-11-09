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

use App\Domain\Enum\CountryEnum;
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

    protected static function getLocationName(): string
    {
        return 'Rockhouse Salzburg';
    }

    protected static function getLocationData(): array
    {
        return [
            'country_code' => CountryEnum::AUSTRIA,
            'postal_code' => '5020',
            'city' => 'Salzburg',
            'street' => 'Schallmooser Hauptstraße 46',
            'additional_information' => '',
            'phone_number' => '+43-662 884914',
            'email_address' => 'service@rockhouse.at',
            'website' => 'https://www.rockhouse.at/',
            'facebook_url' => '',
            'instagram_account' => '',
        ];
    }

    protected function getOverviewUrl(int $numOverviewPagesRead): string
    {
        $now = new \DateTimeImmutable();

        if ($numOverviewPagesRead > 0) {
            $now = $now->modify(sprintf('+%d months', $numOverviewPagesRead));
        }

        return sprintf('https://www.rockhouse.at/Veranstaltungen/%s/%s', $now->format('Y'), $now->format('m'));
    }

    /**
     * @return EventData[]
     */
    protected function extractEventDataFromOverviewContent(\DOMDocument $dom): array
    {
        // find all events by their class name
        /** @var \DOMNodeList $events */
        $events = (new \DOMXPath($dom))->query("//*[contains(@class, 'event')]");
        if (!$events || 0 === $events->count()) {
            return [];
        }

        $entries = [];

        // iterate through all table rows
        /** @var \DOMElement $eventDiv */
        foreach ($events as $eventDiv) {
            if (!$eventDiv || !$eventDiv->hasChildNodes()) {
                continue;
            }

            $entries[] = $this->processOverviewItem($eventDiv);
        }

        return $entries;
    }

    protected function enrichEventDataFromDetailPageContent(EventData $eventData, \DOMDocument $dom): void
    {
    }

    private function processOverviewItem(\DOMElement $eventDiv): EventData
    {
        return new EventData();
    }
}
