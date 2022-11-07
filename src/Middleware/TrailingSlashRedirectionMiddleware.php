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

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Response;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\Entity\Site;

class TrailingSlashRedirectionMiddleware implements MiddlewareInterface
{
    private const CHARACTER = '/';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler, ): ResponseInterface
    {
        if ($request->getAttribute('site') instanceof Site) {
            $path = $request->getUri()->getPath();
            $info = pathinfo($path);

            if (!isset($info['extension']) && !str_ends_with($path, static::CHARACTER)) {
                return new RedirectResponse(
                    $request->getUri()->withPath($path.static::CHARACTER),
                    Response::HTTP_MOVED_PERMANENTLY,
                    [
                        'x-redirect-by' => 'app/trailing-slash-redirection',
                    ],
                );
            }
        }

        return $handler->handle($request);
    }
}
