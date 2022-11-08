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
use App\Domain\Enum\SeatedEnum;

class JazzitEventImport extends AbstractEventImport
{
    protected static function getSource(): string
    {
        return EventSourceEnum::JAZZIT;
    }

    protected static function getEventStoragePid(): int
    {
        return 434;
    }

    protected static function getSleepDuration(): int
    {
        return 500;
    }

    protected function getOverviewUrl(int $numOverviewPagesRead): string
    {
        $url = 'https://www.jazzit.at/site/programm';

        // apply the pagination if we already read some pages
        if ($numOverviewPagesRead > 0) {
            $url .= '?start='.($numOverviewPagesRead * 25);
        }

        return $url;
    }

    protected function extractEntriesFromOverviewContent(string $overviewContent): array
    {
        // abort if no content fetched from the overview url
        if ('' === $overviewContent) {
            return [];
        }

        // create a DOM element based on the entire html content
        $dom = new \DOMDocument();
        $dom->loadHTML($overviewContent);

        // find the <table> containing all events as inner rows
        /** @var \DOMElement $table */
        $table = (new \DOMXPath($dom))->query("//*[contains(@class, 'eventtable')]")->item(0);
        if (!$table) {
            return [];
        }

        // get all <tr> elements inside the table
        $tableRows = $table->getElementsByTagName('tr');
        if (!$tableRows || 0 === $tableRows->count()) {
            return [];
        }

        $entries = [];

        // iterate through all table rows
        /** @var \DOMElement $row */
        foreach ($tableRows as $row) {
            if (!$row || !$row->hasChildNodes()) {
                continue;
            }

            $datetime = null;
            $room = '';
            $title = '';
            $detailPageLink = '';
            $isFreeOfCharge = null;
            $seatedEnum = SeatedEnum::UNKNOWN;

            // extract the date, time and room information from the first inner <td>
            $columns = $row->getElementsByTagName('td');
            if ($columns->count() > 0) {
                // get the content without HTMl, newlines, tabs or spaces
                $columnContent = (string) str_replace(["\t", "\n", ' '], '', strip_tags((string) $columns->item(0)->textContent));
                // split the content into the date information and room
                [$dateInformation, $room] = explode('|', $columnContent);

                if ($dateInformation && str_contains($dateInformation, ',')) {
                    // remove the day name from the string as its in german
                    [, $dateStr] = explode(',', $dateInformation);
                    if ($dateStr) {
                        // get the date and time information
                        [$day, $month, $year] = explode('.', substr($dateStr, 0, 10));
                        [$hour, $minute] = explode('.', substr($dateStr, 10, 5));

                        // convert the to an object
                        $datetime = (new \DateTimeImmutable())
                            ->setDate((int) $year, (int) $month, (int) $day)
                            ->setTime((int) $hour, (int) $minute)
                        ;
                    }
                }
            }

            // extract the title and detail page url from the first inner <a>
            $links = $row->getElementsByTagName('a');
            if ($links->count() > 0) {
                $linkContent = str_replace("\t", '', strip_tags((string) $links->item(0)->textContent));
                [$title] = explode("\n", $linkContent);
                $detailPageLink = 'https://www.jazzit.at'.$links->item(0)->attributes['href']->value;
            }

            // extract the pricing and seated information from the first immer <img> source file name
            $images = $row->getElementsByTagName('img');
            if ($images->count() > 0) {
                $pricingImageSrc = mb_strtolower((string) $images->item(0)->attributes['src']->value);

                if (str_contains($pricingImageSrc, 'frei')) {
                    $isFreeOfCharge = true;
                } elseif (str_contains($pricingImageSrc, 'kosten')) {
                    $isFreeOfCharge = false;
                }

                if (str_contains($pricingImageSrc, 'steh')) {
                    $seatedEnum = SeatedEnum::STANDING;
                } elseif (str_contains($pricingImageSrc, 'teilbestuhlt')) {
                    $seatedEnum = SeatedEnum::PARTIALLY_SEATED;
                } elseif (str_contains($pricingImageSrc, 'stuhl')) {
                    $seatedEnum = SeatedEnum::SEATED;
                }
            }

            // ignore this entry if mandatory data are missing
            if (!$title || !$datetime || !$detailPageLink) {
                continue;
            }

            // add a record representing all data extracted from the overview
            $entries[] = [
                'datetime' => $datetime,
                'timestamp' => $datetime ? $datetime->getTimestamp() : null,
                'room' => trim($room),
                'title' => trim($title),
                'url' => trim($detailPageLink),
                'isFreeOfCharge' => $isFreeOfCharge,
                'seatedEnum' => $seatedEnum,
            ];
        }

        return $entries;
    }

    protected function extractEventUrlFromOverviewEntry(array $overviewEntry): string
    {
        return (string) $overviewEntry['url'];
    }

    protected function extractEventDataFromDetailPageContent(string $detailPageContent, array $overviewEntry): array
    {
        // enrich all new fields to the overview entry as it will be returned
        $overviewEntry['description'] = null;

        // abort if no content fetched from the detail page url
        if ('' === $detailPageContent) {
            return $overviewEntry;
        }

        // create a DOM element based on the entire html content
        $dom = new \DOMDocument();
        $dom->loadHTML($detailPageContent);

        // find the <div> containing the event description
        /** @var \DOMElement $table */
        $div = (new \DOMXPath($dom))->query("//*[contains(@class, 'event_desc')]")->item(0);
        if (!$div) {
            return $overviewEntry;
        }

        // add the event's description to the result
        $overviewEntry['description'] = trim(str_replace("\t", '', strip_tags((string) $div->textContent)));

        return $overviewEntry;
    }

    protected function extractStartTimestampFromEventData(array $eventData): int
    {
        return (int) $eventData['timestamp'];
    }

    protected function convertEventDataToDatabaseRecord(array $eventData): array
    {
        /** @var \DateTimeImmutable $datetime */
        $datetime = $eventData['datetime'];

        // try to fetch an existing record from the database
        $databaseRecord = $this->fetchExistingEventByDetailPageUrl((string) $eventData['url']);

        // update some event information
        $databaseRecord['title'] = (string) $eventData['title'];
        $databaseRecord['external_url'] = (string) $eventData['url'];
        $databaseRecord['start_date'] = $datetime->getTimestamp();
        $databaseRecord['end_date'] = $datetime->modify('+1 day')->setTime(4, 0)->getTimestamp();
        $databaseRecord['free_of_charge'] = ((bool) $eventData['isFreeOfCharge']) ? 1 : 0;
        $databaseRecord['seated'] = (int) $eventData['seatedEnum'];

        // some information will only be set if they are unset right now as content might have been changed/added by editors
        if (!\array_key_exists('description', $databaseRecord) && !$databaseRecord['description']) {
            $databaseRecord['description'] = (string) $eventData['description'];
        }

        if (!\array_key_exists('additional_location_information', $databaseRecord) && !$databaseRecord['additional_location_information']) {
            $databaseRecord['additional_location_information'] = (string) $eventData['room'];
        }

        return $databaseRecord;
    }

    protected function getLocationId(): int
    {
        $name = 'Jazzit Musik Club';

        // try to find an existing location by name
        $location = $this->fetchExistingLocationByName($name);

        // return the id of the existing location
        if (\array_key_exists('uid', $location) && $location['uid'] > 0) {
            return (int) $location['uid'];
        }

        // insert the location as it does not exist yet
        return $this->insertNewLocation([
            'name' => $name,
            'country_code' => CountryEnum::AUSTRIA,
            'postal_code' => '5020',
            'city' => 'Salzburg',
            'street' => 'Elisabethstraße 11',
            'additional_information' => '',
            'phone_number' => '+43 662 883264',
            'email_address' => 'club@jazzit.a',
            'website' => 'https://www.jazzit.at/',
            'facebook_url' => '',
            'instagram_account' => '',
        ]);
    }
}
