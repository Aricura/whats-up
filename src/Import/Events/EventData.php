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

use App\Domain\Enum\SeatedEnum;

class EventData
{
    private ?\DateTimeImmutable $startDatetime = null;
    private ?\DateTimeImmutable $endDatetime = null;
    private ?string $title = null;
    private ?string $url = null;
    private ?bool $freeOfCharge = null;
    private int $seatedEnum = SeatedEnum::UNKNOWN;
    private ?string $description = null;
    private ?string $additionalLocationInformation = null;

    public function getStartDatetime(): ?\DateTimeImmutable
    {
        return $this->startDatetime;
    }

    public function setStartDatetime(\DateTimeImmutable $datetime): void
    {
        $this->startDatetime = $datetime;
    }

    public function getStartTimestamp(): ?int
    {
        return $this->getStartDatetime() ? $this->getStartDatetime()->getTimestamp() : null;
    }

    public function getEndDatetime(): ?\DateTimeImmutable
    {
        if (!$this->endDatetime && $this->getStartDatetime()) {
            $this->endDatetime = $this->getStartDatetime()->modify('+1 day')->setTime(4, 0);
        }

        return $this->endDatetime;
    }

    public function setEndDatetime(\DateTimeImmutable $datetime): void
    {
        $this->endDatetime = $datetime;
    }

    public function getEndTimestamp(): ?int
    {
        return $this->getEndDatetime() ? $this->getEndDatetime()->getTimestamp() : null;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title ? trim($title) : null;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url ? trim($url) : null;
    }

    public function isFreeOfCharge(): ?bool
    {
        return $this->freeOfCharge;
    }

    public function setFreeOfCharge(?bool $freeOfCharge): void
    {
        $this->freeOfCharge = $freeOfCharge;
    }

    public function getSeatedEnum(): int
    {
        return $this->seatedEnum;
    }

    public function setSeatedEnum(int $seatedEnum): void
    {
        $this->seatedEnum = $seatedEnum;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description ? trim($description) : null;
    }

    public function getAdditionalLocationInformation(): ?string
    {
        return $this->additionalLocationInformation;
    }

    public function setAdditionalLocationInformation(?string $additionalLocationInformation): void
    {
        $this->additionalLocationInformation = $additionalLocationInformation ? trim($additionalLocationInformation) : null;
    }
}
