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

class SzeneEventImport extends AbstractEventImport
{
    protected static function getSource(): string
    {
        return EventSourceEnum::SZENE;
    }

    protected static function getEventStoragePid(): int
    {
        return 442;
    }

    protected static function isOverviewPaginated(): bool
    {
        return false;
    }

    protected static function getLocationName(): string
    {
        return 'SZENE Salzburg';
    }

    protected static function getLocationData(): array
    {
        return [
            'country_code' => CountryEnum::AUSTRIA,
            'postal_code' => '5020',
            'city' => 'Salzburg',
            'street' => 'Anton-Neumayr-Platz 2',
            'additional_information' => '',
            'phone_number' => '+43 662 843448',
            'email_address' => 'info@szene-salzburg.net',
            'website' => 'https://www.szene-salzburg.net/',
            'facebook_url' => '',
            'instagram_account' => '',
        ];
    }

    protected function getOverviewUrl(int $numOverviewPagesRead): string
    {
        return 'https://www.szene-salzburg.net/programm';
    }

    /**
     * @return EventData[]
     */
    protected function extractEventDataFromOverviewContent(\DOMDocument $dom): array
    {
        $domPath = new \DOMXPath($dom);

        // find all events by their class name
        /** @var \DOMNodeList $articles */
        $articles = $domPath->query("//*[contains(@class, 'article-list-item')]");
        if (!$articles || 0 === $articles->count()) {
            return [];
        }

        // extract all event lists as we only need to fetch them once
        $listDates = $domPath->query("//*[contains(@class, 'list-date')]");
        $listTitles = $domPath->query("//*[contains(@class, 'list-title')]");
        $eventTimes = $domPath->query("//*[contains(@class, 'event-time')]",);
        $eventLocations = $domPath->query("//*[contains(@class, 'event-loc')]",);
        $eventGenres = $domPath->query("//*[contains(@class, 'event-genre')]",);

        $entries = [];

        // iterate through all articles
        /** @var \DOMElement $article */
        foreach ($articles as $index => $article) {
            if (!$article || !$article->hasChildNodes()) {
                continue;
            }

            $links = $article->getElementsByTagName('a');
            $linkItem = $links->item(0);

            // extract the matching items for this article by their index
            $dateItem = $listDates->item($index);
            $titleItem = $listTitles->item($index);
            $timeItem = $eventTimes->item($index);
            $locationItem = $eventLocations->item($index);
            $genreItem = $eventGenres->item($index);

            $entries[] = $this->processOverviewItem($linkItem, $dateItem, $titleItem, $timeItem, $locationItem, $genreItem);
        }

        return $entries;
    }

    protected function enrichEventDataFromDetailPageContent(EventData $eventData, \DOMDocument $dom): void
    {
    }

    private function processOverviewItem(
        ?\DOMElement $linkItem,
        ?\DOMElement $dateItem,
        ?\DOMElement $titleItem,
        ?\DOMElement $timeItem,
        ?\DOMElement $locationItem,
        ?\DOMElement $genreItem
    ): EventData {
        $eventData = new EventData();
        $eventData->setUrl($linkItem ? trim((string) $linkItem->attributes[1]->value) : null);

        $day = null;
        $month = null;
        $year = (int) date('Y');

        // extract the date
        if ($dateItem) {
            $dateStr = trim(str_replace(' ', '', strip_tags((string) $dateItem->textContent)));
            $dateInformation = explode('.', $dateStr);

            if (\is_array($dateInformation) && \count($dateInformation) >= 2) {
                $day = (int) $dateInformation[1];
                $month = (int) $dateInformation[2];

                if (\count($dateInformation) >= 4 && (int) $dateInformation[3] > 0) {
                    $year = (int) $dateInformation[3];
                    if ($year < 100) {
                        $year += 2000;
                    }
                }
            }
        }

        // extract the title
        if ($titleItem) {
            $eventName = trim(strip_tags((string) $titleItem->textContent));

            if ($titleItem->nextSibling) {
                $subtitle = trim(strip_tags((string) $titleItem->nextSibling->textContent));

                if ('' !== $subtitle) {
                    $eventName .= ' - '.$subtitle;
                }
            }

            $eventData->setTitle($eventName);
        }

        // extract the start and end time
        if ($year && $month && $day && $timeItem) {
            $eventTimeStr = trim(str_replace(' ', '', strip_tags((string) $timeItem->textContent)));

            if (mb_strlen($eventTimeStr) > 5) {
                [$startTime, $endTime] = explode('–', $eventTimeStr);
            } else {
                $startTime = $eventTimeStr;
                $endTime = null;
            }

            [$hour, $minute] = explode(':', $startTime);
            $datetime = (new \DateTimeImmutable())->setDate($year, $month, $day)->setTime((int) $hour, (int) $minute);
            $eventData->setStartDatetime($datetime);

            if ($endTime) {
                [$hour, $minute] = explode(':', $endTime);
                $datetime = (new \DateTimeImmutable())->setDate($year, $month, $day)->setTime((int) $hour, (int) $minute);
                $eventData->setEndDatetime($datetime);
            }
        }

        // extract the additional event location information
        if ($locationItem) {
            $additionalLocationInformation = trim(strip_tags((string) $locationItem->textContent));
            $eventData->setAdditionalLocationInformation($additionalLocationInformation);
        }

        // extract the genre
        if ($genreItem) {
            $genre = trim(strip_tags((string) $genreItem->textContent));
            $eventData->setGenres([$genre]);
        }

        return $eventData;
    }
}
