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

use App\Domain\Enum\EventSourceEnum;
use App\Import\Events\AbstractEventImport;
use App\Import\Events\JazzitEventImport;
use App\Import\Events\RockhouseEventImport;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventImportCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('app:event-import');
        $this->setDescription('Imports events from the specified source.');

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'Source name to import events from its website (jazzit, rockhouse, ...).'
        );

        $this->addArgument(
            'offset_in_days',
            InputArgument::REQUIRED,
            'How many days into the future should be fetched (e.g. 7)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // read inputs and sanitize them
        $source = mb_strtolower(trim((string) $input->getArgument('source')));
        $offsetInDaysStr = mb_strtolower(trim((string) $input->getArgument('offset_in_days')));

        // extract the numeric offset value from the input
        $offsetInDays = \is_numeric($offsetInDaysStr) ? (int) $offsetInDaysStr : 0;

        // get importer by source name
        switch ($source) {
            case EventSourceEnum::JAZZIT:
                $importer = $this->getService(JazzitEventImport::class);
                break;
            case EventSourceEnum::ROCKHOUSE:
                $importer = $this->getService(RockhouseEventImport::class);
                break;
            default:
                return $this->throwError(sprintf('Invalid source. Use one of: %s',
                    implode(' | ', array_filter(EventSourceEnum::getValues()))
                ), $output);
        }

        if ($importer instanceof AbstractEventImport) {
            try {
                // execute import task
                $importer->execute($offsetInDays);
            } catch (\Exception $exception) {
                return $this->throwError($exception->getMessage(), $output);
            }
        }

        return 0;
    }
}
