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
            $overviewEntries = $this->getOverviewEntries($numOverviewPagesRead);
            if (!$overviewEntries) {
                // abort if no entries found on the overview page
                break;
            }

            // process each overview entry
            foreach ($overviewEntries as $index => $overviewEntry) {
                // get all event data from the overview entry
                $eventData = $this->getEventDataFromOverviewEntry($overviewEntry);
                if (!$eventData) {
                    continue;
                }

                // extract the event's start day/time
                $eventStartTimestamp = $this->extractStartTimestampFromEventData($eventData);

                // abort if the offset in days is reached
                if ($eventStartTimestamp > $threshold) {
                    $offsetReached = true;
                    break;
                }

                // convert the event data to the database record
                $databaseRecord = $this->convertEventDataToDatabaseRecord($eventData);

                // insert / update the event data
                $this->storeEventRecord($databaseRecord, $locationId);
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

    protected function getOverviewEntries(int $numOverviewPagesRead): array
    {
        // fetch the event overview content (includes pagination handling)
        $overviewUrl = $this->getOverviewUrl($numOverviewPagesRead);
        $overviewContent = $this->fetchContentFromUrl($overviewUrl);

        // extract all entries from the overview content
        return $this->extractEntriesFromOverviewContent($overviewContent);
    }

    protected function getEventDataFromOverviewEntry(array $overviewEntry): array
    {
        // fetch the event's detail page content
        $detailPageUrl = $this->extractEventUrlFromOverviewEntry($overviewEntry);
        $detailPageContent = $this->fetchContentFromUrl($detailPageUrl);

        // extract all event data from its detail page content
        return $this->extractEventDataFromDetailPageContent($detailPageContent, $overviewEntry);
    }

    protected function fetchContentFromUrl(string $url): string
    {
        if (!$url) {
            return '';
        }

        // sleep a bit to avoid too many request in a short time period
        $sleepDuration = static::getSleepDuration();
        if ($sleepDuration > 0) {
            usleep($sleepDuration);
        }

        return (string) file_get_contents($url);
    }

    protected function fetchExistingEventByDetailPageUrl(string $detailPageUrl): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(Event::getTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('*')
            ->from(Event::getTableName())
            ->where($queryBuilder->expr()->eq('source', $queryBuilder->quote(static::getSource())))
            ->andWhere($queryBuilder->expr()->eq('external_url', $queryBuilder->quote($detailPageUrl)))
            ->orderBy('uid', QueryInterface::ORDER_DESCENDING)
            ->setMaxResults(1)
        ;

        try {
            $record = $queryBuilder->execute()->fetchAssociative();
        } catch (\Exception) {
            return [];
        } catch (Exception) {
            return [];
        }

        return \is_array($record) ? $record : [];
    }

    protected function storeEventRecord(array $databaseRecord, int $locationId): void
    {
        // abort if the event has no title as we cannot generate its slug
        if (!\array_key_exists('title', $databaseRecord) || !$databaseRecord['title']) {
            return;
        }

        $isExistingRecord = \array_key_exists('uid', $databaseRecord) && $databaseRecord['uid'] > 0;

        $databaseRecord['pid'] = static::getEventStoragePid();
        $databaseRecord['tstamp'] = time();
        $databaseRecord['deleted'] = 0;
        $databaseRecord['location'] = $locationId;

        if (!$isExistingRecord) {
            $databaseRecord['crdate'] = $databaseRecord['tstamp'];
            $databaseRecord['cruser_id'] = self::CREATION_USER_ID;
            $databaseRecord['hidden'] = 0;
            $databaseRecord['sorting'] = 1;
            $databaseRecord['sys_language_uid'] = 0;
            $databaseRecord['l18n_parent'] = 0;
            $databaseRecord['slug'] = $this->slugHelper->sanitize((string) $databaseRecord['title']);
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

    protected function fetchExistingLocationByName(string $name): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(EventLocation::getTableName());
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('*')
            ->from(EventLocation::getTableName())
            ->where($queryBuilder->expr()->eq('name', $queryBuilder->quote($name)))
            ->andWhere($queryBuilder->expr()->eq('sys_language_uid', 0))
            ->orderBy('uid', QueryInterface::ORDER_DESCENDING)
            ->setMaxResults(1)
        ;

        try {
            $record = $queryBuilder->execute()->fetchAssociative();
        } catch (\Exception) {
            return [];
        } catch (Exception) {
            return [];
        }

        return \is_array($record) ? $record : [];
    }

    protected function insertNewLocation(array $databaseRecord): int
    {
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

    protected static function getLocationStoragePid(): int
    {
        return 433;
    }

    abstract protected static function getSource(): string;

    abstract protected static function getEventStoragePid(): int;

    abstract protected static function getSleepDuration(): int;

    abstract protected function getOverviewUrl(int $numOverviewPagesRead): string;

    abstract protected function extractEntriesFromOverviewContent(string $overviewContent): array;

    abstract protected function extractEventUrlFromOverviewEntry(array $overviewEntry): string;

    abstract protected function extractEventDataFromDetailPageContent(string $detailPageContent, array $overviewEntry): array;

    abstract protected function extractStartTimestampFromEventData(array $eventData): int;

    abstract protected function convertEventDataToDatabaseRecord(array $eventData): array;

    abstract protected function getLocationId(): int;
}
