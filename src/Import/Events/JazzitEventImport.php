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

    protected static function getLocationName(): string
    {
        return 'Jazzit Musik Club';
    }

    protected static function getLocationData(): array
    {
        return [
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
        ];
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

    /**
     * @return EventData[]
     */
    protected function extractEventDataFromOverviewContent(\DOMDocument $dom): array
    {
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

            $entries[] = $this->processOverviewItem($row);
        }

        return $entries;
    }

    protected function enrichEventDataFromDetailPageContent(EventData $eventData, \DOMDocument $dom): void
    {
        // find the <div> containing the event description
        /** @var \DOMElement $table */
        $div = (new \DOMXPath($dom))->query("//*[contains(@class, 'event_desc')]")->item(0);
        if (!$div) {
            return;
        }

        $eventData->setDescription(trim(str_replace("\t", '', strip_tags((string) $div->textContent))));
    }

    private function processOverviewItem(\DOMElement $row): EventData
    {
        $eventData = new EventData();

        // extract the date, time and room information from the first inner <td>
        $columns = $row->getElementsByTagName('td');
        if ($columns->count() > 0) {
            // get the content without HTMl, newlines, tabs or spaces
            $columnContent = (string) str_replace(["\t", "\n", ' '], '', strip_tags((string) $columns->item(0)->textContent));
            // split the content into the date information and room
            [$dateInformation, $room] = explode('|', $columnContent);
            $eventData->setAdditionalLocationInformation($room);

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

                    $eventData->setStartDatetime($datetime);
                }
            }
        }

        // extract the title and detail page url from the first inner <a>
        $links = $row->getElementsByTagName('a');
        if ($links->count() > 0) {
            $linkContent = str_replace("\t", '', strip_tags((string) $links->item(0)->textContent));
            [$title] = explode("\n", $linkContent);

            $eventData->setTitle($title);
            $eventData->setUrl('https://www.jazzit.at'.$links->item(0)->attributes['href']->value);
        }

        // extract the pricing and seated information from the first immer <img> source file name
        $images = $row->getElementsByTagName('img');
        if ($images->count() > 0) {
            $pricingImageSrc = mb_strtolower((string) $images->item(0)->attributes['src']->value);

            if (str_contains($pricingImageSrc, 'frei')) {
                $eventData->setFreeOfCharge(true);
            } elseif (str_contains($pricingImageSrc, 'kosten')) {
                $eventData->setFreeOfCharge(false);
            }

            if (str_contains($pricingImageSrc, 'steh')) {
                $eventData->setSeatedEnum(SeatedEnum::STANDING);
            } elseif (str_contains($pricingImageSrc, 'teilbestuhlt')) {
                $eventData->setSeatedEnum(SeatedEnum::PARTIALLY_SEATED);
            } elseif (str_contains($pricingImageSrc, 'stuhl')) {
                $eventData->setSeatedEnum(SeatedEnum::SEATED);
            }


        }

        return $eventData;
    }
}
