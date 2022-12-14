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

namespace App\EventSubscriber;

use App\Config\ExtbaseConfigLoader;
use Bartacus\Bundle\BartacusBundle\ConfigEvents;
use Bartacus\Bundle\BartacusBundle\Event\RequestExtbasePersistenceClassesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;

final class ExtbaseConfigLoaderSubscriber implements EventSubscriberInterface
{
    private ExtbaseConfigLoader $extbaseConfigLoader;

    public function __construct(ExtbaseConfigLoader $extbaseConfigLoader)
    {
        $this->extbaseConfigLoader = $extbaseConfigLoader;
    }

    /**
     * @throws NoSuchCacheException|\ReflectionException
     */
    public function configureExtbase(RequestExtbasePersistenceClassesEvent $event): void
    {
        $event->addExtbasePersistenceClasses($this->extbaseConfigLoader->getConfiguration());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::REQUEST_EXTBASE_PERSISTENCE_CLASSES => [['configureExtbase', 4]],
        ];
    }
}
