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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

(static function (): void {
    $path = static function (string $name) {
        return sprintf('%s/var/log/%s_%s.log', Environment::getProjectPath(), $name, date('Ymd'));
    };

    // Default
    unset($GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::WARNING]);

    if (Environment::getContext()->isProduction()) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::INFO][FileWriter::class]['logFile'] = $path('typo3');
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::DEBUG][FileWriter::class]['logFile'] = $path('typo3');
    }

    // Deprecations
    unset($GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration'][LogLevel::NOTICE][FileWriter::class]['logFileInfix']);
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration'][LogLevel::NOTICE][FileWriter::class]['logFile'] = $path('typo3-deprecations');

    // Application
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['App']['writerConfiguration'][LogLevel::INFO][FileWriter::class]['logFile'] = $path('app');

    // Solr
    if (isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['ApacheSolrForTypo3'])) {
        unset($GLOBALS['TYPO3_CONF_VARS']['LOG']['ApacheSolrForTypo3']['Solr']['writerConfiguration'][LogLevel::ERROR][FileWriter::class]['logFileInfix']);
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['ApacheSolrForTypo3']['Solr']['writerConfiguration'][LogLevel::DEBUG][FileWriter::class]['logFile'] = $path('solr');
    }
})();
