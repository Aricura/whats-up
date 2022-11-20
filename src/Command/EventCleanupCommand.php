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

namespace App\Command;

use App\Import\Events\EventCleanupService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventCleanupCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('app:event-cleanup');
        $this->setDescription('Cleans up all outdated events.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cleaner = $this->getService(EventCleanupService::class);

        if ($cleaner instanceof EventCleanupService) {
            $cleaner->cleanup();
        }

        return 0;
    }
}
