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
use App\Domain\Event\Model\EventCategory;
use App\Domain\Event\Model\EventLocation;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

abstract class AbstractEventImport
{
    private const CREATION_USER_ID = 2;

    protected ConnectionPool $connectionPool;
    protected SlugHelper $slugHelper;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
        $this->slugHelper = new SlugHelper('', '', []);
    }

    public function execute(int $offsetInDays): void
    {
        $threshold = $this->getImportThreshold($offsetInDays);
        $offsetReached = false;
        $numOverviewPagesRead = 0;

        // fetch the location id used for all events during the import
        $locationId = $this->getLocationId();

        do {
            // get the next set of overview entries
            $eventDataList = $this->getOverviewEventDataList($numOverviewPagesRead);
            if (!$eventDataList) {
                // abort if no entries found on the overview page
                break;
            }

            // process each overview entry
            /** @var EventData $eventData */
            foreach ($eventDataList as $eventData) {
                // enrich overview data with content from the detail page
                $this->enrichEventDataFromDetailPage($eventData);

                // ignore this entry if mandatory data are missing
                if (!$eventData->getTitle() || !$eventData->getStartDatetime()) {
                    continue;
                }

                // abort if the offset in days is reached
                if ($eventData->getStartTimestamp() > $threshold) {
                    $offsetReached = true;
                    break;
                }

                // insert / update the event data
                $this->storeEventData($eventData, $locationId);
            }

            if (!static::isOverviewPaginated()) {
                break;
            }

            // increase the number of overview pages read and continue with the next page
            ++$numOverviewPagesRead;
        } while (!$offsetReached);
    }

    protected function getImportThreshold(int $offsetInDays): int
    {
        // sanitize the offset in days
        $offsetInDays = (int) abs($offsetInDays);
        if (0 === $offsetInDays) {
            $offsetInDays = 7;
        }

        return (new \DateTimeImmutable())->modify(sprintf('+ %d days', $offsetInDays))->getTimestamp();
    }

    /**
     * @return EventData[]
     */
    protected function getOverviewEventDataList(int $numOverviewPagesRead): array
    {
        // fetch the event overview content (includes pagination handling)
        $url = $this->getOverviewUrl($numOverviewPagesRead);
        $content = $this->fetchContentFromUrl($url);

        // abort if no content fetched from the overview url
        if ('' === $content) {
            return [];
        }

        // create a DOM element based on the entire html content
        $dom = new \DOMDocument();
        $dom->loadHTML($content);

        // extract all entries from the DOM
        return $this->extractEventDataFromOverviewContent($dom);
    }

    protected function enrichEventDataFromDetailPage(EventData $eventData): void
    {
        // fetch the event's detail page content
        $content = $this->fetchContentFromUrl($eventData->getUrl());

        // abort if no content fetched from the detail page url
        if ('' === $content) {
            return;
        }

        // create a DOM element based on the entire html content
        $dom = new \DOMDocument();
        $dom->loadHTML($content);

        // enrich all event data from its detail page content
        $this->enrichEventDataFromDetailPageContent($eventData, $dom);
    }

    protected function fetchContentFromUrl(string $url): string
    {
        if ('' === $url) {
            return '';
        }

        // sleep a bit to avoid too many request in a short time period
        $sleepDuration = static::getSleepDuration();
        if ($sleepDuration > 0) {
            usleep($sleepDuration);
        }

        // try by file_get_contents() first
        $response = file_get_contents($url);
        if (\is_string($response) && '' !== $response) {
            return $response;
        }

        // fallback try to fetch the content using curl
        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, \CURLOPT_HTTPGET, 1);
        curl_setopt($ch, \CURLOPT_HEADER, false);
        curl_setopt($ch, \CURLOPT_URL, $url);

        $response = (string) curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    protected function storeEventData(EventData $eventData, int $locationId): void
    {
        // try to fetch an existing record from the database
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(Event::getTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('*')
            ->from(Event::getTableName())
            ->where($queryBuilder->expr()->eq('source', $queryBuilder->quote(static::getSource())))
            ->andWhere($queryBuilder->expr()->eq('external_url', $queryBuilder->quote($eventData->getUrl())))
            ->orderBy('uid', QueryInterface::ORDER_DESCENDING)
            ->setMaxResults(1)
        ;

        $isExistingRecord = false;

        try {
            $databaseRecord = (array) $queryBuilder->execute()->fetchAssociative();
            $isExistingRecord = \is_array($databaseRecord) && \array_key_exists('uid', $databaseRecord) && $databaseRecord['uid'] > 0;
        } catch (\Exception) {
        } catch (Exception) {
        }

        if (!$isExistingRecord) {
            $databaseRecord = [];
        }

        // update some event information
        $databaseRecord['pid'] = static::getEventStoragePid();
        $databaseRecord['tstamp'] = time();
        $databaseRecord['deleted'] = 0;
        $databaseRecord['location'] = $locationId;
        $databaseRecord['title'] = (string) $eventData->getTitle();
        $databaseRecord['external_url'] = $eventData->getUrl();
        $databaseRecord['start_date'] = (int) $eventData->getStartTimestamp();
        $databaseRecord['end_date'] = (int) $eventData->getEndTimestamp();
        $databaseRecord['free_of_charge'] = $eventData->isFreeOfCharge() ? 1 : 0;
        $databaseRecord['seated'] = $eventData->getSeatedEnum();

        // some information will only be set if they are unset right now as content might have been changed/added by editors
        if (!\array_key_exists('description', $databaseRecord) && !$databaseRecord['description']) {
            $databaseRecord['description'] = (string) $eventData->getDescription();
        }

        if (!\array_key_exists('additional_location_information', $databaseRecord) && !$databaseRecord['additional_location_information']) {
            $databaseRecord['additional_location_information'] = (string) $eventData->getAdditionalLocationInformation();
        }

        // add all genres and insert them on-the-fly if new
        if ($eventData->getGenres()) {
            $categoryIds = [];

            foreach ($eventData->getGenres() as $genre) {
                $categoryIds[] = $this->getGenreId($genre);
            }

            $categoryIds = array_filter($categoryIds);
            if (\count($categoryIds) > 0) {
                $databaseRecord['categories'] = implode(',', $categoryIds);
            }
        }

        if (!$isExistingRecord) {
            $databaseRecord['crdate'] = $databaseRecord['tstamp'];
            $databaseRecord['cruser_id'] = self::CREATION_USER_ID;
            $databaseRecord['hidden'] = 0;
            $databaseRecord['sorting'] = 1;
            $databaseRecord['sys_language_uid'] = 0;
            $databaseRecord['l18n_parent'] = 0;
            $databaseRecord['slug'] = $this->slugHelper->sanitize($databaseRecord['title']);
            $databaseRecord['source'] = static::getSource();

            // insert the new event
            $connection = $this->connectionPool->getConnectionForTable(Event::getTableName());
            $connection->insert(Event::getTableName(), $databaseRecord);
        } else {
            // update the existing event
            $connection = $this->connectionPool->getConnectionForTable(Event::getTableName());
            $connection->update(Event::getTableName(), $databaseRecord, ['uid' => $databaseRecord['uid']]);
        }
    }

    protected function getGenreId(string $genre): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(EventCategory::getTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid')
            ->from(EventCategory::getTableName())
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->quote($genre)))
            ->andWhere($queryBuilder->expr()->eq('sys_language_uid', 0))
            ->orderBy('uid', QueryInterface::ORDER_DESCENDING)
            ->setMaxResults(1)
        ;

        try {
            $record = $queryBuilder->execute()->fetchAssociative();
            if (\is_array($record)) {
                return (int) $record['uid'];
            }
        } catch (\Exception) {
        } catch (Exception) {
        }

        try {
            $colorCode = sprintf('#%02x%02x%02x', random_int(0, 255), random_int(0, 255), random_int(0, 255));
        } catch (\Exception) {
            $colorCode = '#000000';
        }

        // define a new category
        $databaseRecord = [];
        $databaseRecord['title'] = $genre;
        $databaseRecord['color_code'] = $colorCode;
        $databaseRecord['pid'] = static::getCategoryStoragePid();
        $databaseRecord['tstamp'] = time();
        $databaseRecord['crdate'] = $databaseRecord['tstamp'];
        $databaseRecord['cruser_id'] = self::CREATION_USER_ID;
        $databaseRecord['deleted'] = 0;
        $databaseRecord['hidden'] = 0;
        $databaseRecord['sorting'] = 1;
        $databaseRecord['sys_language_uid'] = 0;
        $databaseRecord['l18n_parent'] = 0;

        // insert the new category
        $connection = $this->connectionPool->getConnectionForTable(EventCategory::getTableName());
        $connection->insert(EventCategory::getTableName(), $databaseRecord);

        return (int) $connection->lastInsertId(EventCategory::getTableName());
    }

    protected function getLocationId(): int
    {
        // try to find an existing location by name
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(EventLocation::getTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid')
            ->from(EventLocation::getTableName())
            ->where($queryBuilder->expr()->eq('name', $queryBuilder->quote(static::getLocationName())))
            ->andWhere($queryBuilder->expr()->eq('sys_language_uid', 0))
            ->orderBy('uid', QueryInterface::ORDER_DESCENDING)
            ->setMaxResults(1)
        ;

        try {
            $record = $queryBuilder->execute()->fetchAssociative();
            if (\is_array($record)) {
                return (int) $record['uid'];
            }
        } catch (\Exception) {
        } catch (Exception) {
        }

        // insert the location as it does not exist yet
        $databaseRecord = static::getLocationData();
        $databaseRecord['name'] = static::getLocationName();
        $databaseRecord['pid'] = static::getLocationStoragePid();
        $databaseRecord['tstamp'] = time();
        $databaseRecord['crdate'] = $databaseRecord['tstamp'];
        $databaseRecord['cruser_id'] = self::CREATION_USER_ID;
        $databaseRecord['deleted'] = 0;
        $databaseRecord['hidden'] = 0;
        $databaseRecord['sorting'] = 1;
        $databaseRecord['sys_language_uid'] = 0;
        $databaseRecord['l18n_parent'] = 0;

        // insert the new location
        $connection = $this->connectionPool->getConnectionForTable(EventLocation::getTableName());
        $connection->insert(EventLocation::getTableName(), $databaseRecord);

        return (int) $connection->lastInsertId(EventLocation::getTableName());
    }

    protected static function getSleepDuration(): int
    {
        return 500;
    }

    protected static function getLocationStoragePid(): int
    {
        return 433;
    }

    protected static function getCategoryStoragePid(): int
    {
        return 432;
    }

    protected static function isOverviewPaginated(): bool
    {
        return true;
    }

    abstract protected static function getSource(): string;

    abstract protected static function getEventStoragePid(): int;

    abstract protected static function getLocationName(): string;

    abstract protected static function getLocationData(): array;

    abstract protected function getOverviewUrl(int $numOverviewPagesRead): string;

    /**
     * @return EventData[]
     */
    abstract protected function extractEventDataFromOverviewContent(\DOMDocument $dom): array;

    abstract protected function enrichEventDataFromDetailPageContent(EventData $eventData, \DOMDocument $dom): void;
}
