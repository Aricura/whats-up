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

use Bartacus\Bundle\BartacusBundle\Bootstrap\SymfonyBootstrap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Scheduler\FailedExecutionException;

abstract class AbstractCommand extends Command
{
    protected function getService(string $identifier)
    {
        /* @noinspection NullPointerExceptionInspection */
        return SymfonyBootstrap::getKernel()->getContainer()->get($identifier);
    }

    protected function throwError(string $message, OutputInterface $output): int
    {
        // Web execution (TYPO3 Backend)
        if ($output instanceof NullOutput) {
            throw new FailedExecutionException($message);
        }

        // CLI execution
        $output->writeln($message);

        return 255;
    }
}
