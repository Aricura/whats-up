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

namespace App\Controller;

use App\Domain\Event\Model\Event;
use App\Domain\Event\Repository\EventRepository;
use Bartacus\Bundle\BartacusBundle\Annotation\ContentElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use function sprintf;
use function str_replace;
use function trim;

class CalendarController extends AbstractController
{
    private EventRepository $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * @ContentElement("app_calendar")
     */
    public function __invoke(array $data): Response
    {
        $events = $this->eventRepository->getUpcomingEvents();
        $eventJs = '';

        /** @var Event $event */
        foreach ($events as $event) {
            $eventJs .= sprintf(
                "{ eventName: '%s - %s', calendar: '%s', color: '%s', datetime: '%d' },",
                str_replace(['{', '}', "'"], '', $event->getTitle()),
                $event->getStartTime(),
                $event->getLocation()?->getName(),
                $event->getLocation()?->getColor(),
                $event->getStartDate(),
            );
        }

        return $this->render('content/calendar/calendar.html.twig', [
            'eventJs' => '['.trim($eventJs, ',').']',
        ]);
    }
}
