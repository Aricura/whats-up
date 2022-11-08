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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Core\Environment;

class FileCleanupCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('app:file-cleanup');
        $this->setDescription('Removes any files matching a specific pattern from a specific directory after x days.');

        $this->addArgument(
            'directory',
            InputArgument::REQUIRED,
            'File dir relative to the projects root (e.g. "/var/log/" or "/var/transient").'
        );

        $this->addArgument(
            'pattern',
            InputArgument::REQUIRED,
            'File name pattern inside the directory (e.g. "typo3_*.log" or "solr_*.log" or "*.jpg").'
        );

        $this->addArgument(
            'threshold',
            InputArgument::REQUIRED,
            'Threshold in days to delete log files older than the specified value.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = trim((string) $input->getArgument('directory'));
        $pattern = trim((string) $input->getArgument('pattern'));
        $thresholdInDays = abs((int) trim((string) $input->getArgument('threshold')));

        // abort if threshold is unset
        if (0 === $thresholdInDays) {
            return $this->throwError('Threshold needs to be greater than zero!', $output);
        }

        // abort if directory or pattern is unset
        if ('' === $directory || '' === $pattern) {
            return $this->throwError('Please specify a directory path and a name pattern!', $output);
        }

        // get the canonicalized absolute pathname
        $path = realpath(Environment::getProjectPath().'/'.trim($directory, '/'));

        // abort if path does not exist or if project path or if outside project path (traversal path)
        if (!$path || $path === Environment::getProjectPath() || !str_starts_with($path, Environment::getProjectPath())) {
            return $this->throwError('Traversal directory paths are not permitted!', $output);
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($path)
            ->name($pattern)
            ->date('< now - '.$thresholdInDays.' days')
        ;

        foreach ($finder->getIterator() as $file) {
            unlink($file->getRealPath());
        }

        return 0;
    }
}
