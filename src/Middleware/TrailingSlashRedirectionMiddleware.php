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
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TYPO3\CMS\Core\Http\RedirectResponse;

class TrailingSlashRedirectionMiddleware implements MiddlewareInterface
{
    private const EXCLUDED_PATHS = [
        '/__webpack_hmr',
    ];

    private const ALLOWED_EXTENSIONS = [
        'txt',
        'xml',
    ];

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $httpFoundationFactory = new HttpFoundationFactory();
        $symfonyRequest = $httpFoundationFactory->createRequest($request);

        $trailingSlashRedirect = $this->getTrailingSlashRedirect($symfonyRequest);

        if ($trailingSlashRedirect) {
            return new RedirectResponse($trailingSlashRedirect, Response::HTTP_MOVED_PERMANENTLY, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=60, s-maxage=60',
            ]);
        }

        // invoke inner middleware services and eventually the TYPO3 kernel
        return $handler->handle($request);
    }

    private function getTrailingSlashRedirect(Request $request): ?string
    {
        $pathInfo = $request->getPathInfo();
        $queryInfo = $request->getQueryString();
        $pathInfoParts = pathinfo($pathInfo);

        $hasTrailingSlash = '/' === mb_substr($pathInfo, mb_strlen($pathInfo) - 1, 1);
        if (!$hasTrailingSlash && !\in_array($pathInfoParts['extension'] ?? '', self::ALLOWED_EXTENSIONS, true)) {
            if (\in_array($pathInfo, self::EXCLUDED_PATHS, true)) {
                return null;
            }

            return $request->getSchemeAndHttpHost().$pathInfo.'/'.($queryInfo ? '?'.$queryInfo : '');
        }

        return null;
    }
}
