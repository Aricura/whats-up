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

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cs_seo'] = [
    'inPageModule' => '0',
    'evaluationDoktypes' => '1',
    'evaluators' => 'Title,Description,H1,H2,Images,Keyword',
    'maxH2' => '6',
    'minTitle' => '40',
    'maxTitle' => '57',
    'maxNavTitle' => '50',
    'minDescription' => '140',
    'maxDescription' => '156',
    'cropDescription' => '0',
    'modFileColumns' => 'title,description',
    'useAdditionalCanonicalizedUrlParametersOnly' => '0',
];
