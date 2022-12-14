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

namespace App\Domain\Enum;

use App\Domain\AbstractBaseEnum;

class EventSourceEnum extends AbstractBaseEnum
{
    public const MANUALLY = '';
    public const JAZZIT = 'jazzit';
    public const ROCKHOUSE = 'rockhouse';
    public const SZENE = 'szene';

    public static function getTcaItems(): array
    {
        $items = [];

        foreach (self::getValues() as $source) {
            $label = self::MANUALLY === $source
                ? '-- inserted manually --'
                : sprintf('Imported from %s website', $source)
            ;

            $items[] = [$label, $source];
        }

        return $items;
    }
}
