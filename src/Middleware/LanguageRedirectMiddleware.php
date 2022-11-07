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

use Negotiation\AcceptLanguage;
use Negotiation\Exception\Exception;
use Negotiation\LanguageNegotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Response;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LanguageRedirectMiddleware implements MiddlewareInterface
{
    /**
     * Redirects to the base language prefix which fits the browser language best, if no language is
     * specified in the requested path.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // site information aren't available so lets proceed with the default TYPO3 handling
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $handler->handle($request);
        }

        // check if the request already contains language information
        $currentSiteLanguage = $request->getAttribute('language');
        if ($currentSiteLanguage instanceof SiteLanguage) {
            return $handler->handle($request);
        }

        // perform language redirects only for GET requests (ignore POST, HEAD, ...)
        if ('GET' !== mb_strtoupper($request->getMethod())) {
            return $handler->handle($request);
        }

        // use a permanent redirect if there is only one possible language
        // ... do not use a permanent redirect if there is currently one language enabled but already more languages defined
        if (1 === \count($site->getLanguages()) && 1 === \count($site->getAllLanguages())) {
            return $this->performRedirect($request, $site->getDefaultLanguage()->getBase()->getPath(), Response::HTTP_MOVED_PERMANENTLY);
        }

        // get the first 'accept-language' received in the request header
        $acceptLanguage = $request->getHeader('accept-language');
        $acceptLanguage = \is_array($acceptLanguage) ? array_values($acceptLanguage)[0] : (string) $acceptLanguage;

        if ('' !== $acceptLanguage) {
            $availableLanguages = [];

            // get all enabled languages and their url base segment
            /** @var SiteLanguage $language */
            foreach ($site->getLanguages() as $language) {
                $availableLanguages[$language->getHreflang()] = $language->getBase()->getPath();
            }

            if (\count($availableLanguages) >= 2) {
                // detect the bast language enabled which matches the request's accept-language
                try {
                    /** @var AcceptLanguage $bestLanguage */
                    $bestLanguage = (new LanguageNegotiator())->getBest($acceptLanguage, array_keys($availableLanguages));
                } catch (Exception) {
                    $bestLanguage = null;
                }

                if ($bestLanguage && $availableLanguages[$bestLanguage->getValue()]) {
                    // use the language which matches the request's accept-language best
                    $localizedPathSegment = $availableLanguages[$bestLanguage->getValue()];
                } else {
                    // fallback if non of the available languages matches the request's accept-language
                    $localizedPathSegment = $site->getDefaultLanguage()->getBase()->getPath();
                }
            } else {
                // fallback to the first site language as this site has only one language enabled
                $localizedPathSegment = $site->getDefaultLanguage()->getBase()->getPath();
            }
        } else {
            // fallback to the first site language if the request has no 'accept-language' (e.g. bots)
            $localizedPathSegment = $site->getDefaultLanguage()->getBase()->getPath();
        }

        // avoid performing a redirect if the localized path segment is empty
        if ('' === $localizedPathSegment || '/' === $localizedPathSegment) {
            return $handler->handle($request);
        }

        // use temporary redirect if root page
        if ('/' === $request->getUri()->getPath()) {
            return $this->performRedirect($request, $localizedPathSegment, Response::HTTP_TEMPORARY_REDIRECT);
        }

        // otherwise use permanent redirect
        return $this->performRedirect($request, $localizedPathSegment, Response::HTTP_MOVED_PERMANENTLY);
    }

    protected function performRedirect(ServerRequestInterface $request, string $localizedPathSegment, int $redirectStatusCode): RedirectResponse
    {
        // prepend the localized path segment to the current uri and force trailing slash if missing to avoid redirect-chains
        $path = rtrim($localizedPathSegment.$request->getUri()->getPath(), '/').'/';

        // keep the request query parameters
        if ('' !== $request->getUri()->getQuery()) {
            $path .= '?'.$request->getUri()->getQuery();
        }

        // redirect to the new uri containing the resolved language path segment
        $redirectResponse = new RedirectResponse($path, $redirectStatusCode);
        $redirectResponse->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=60, s-maxage=60');
        $redirectResponse->withHeader('X-Redirect-By', 'LanguageRedirectMiddleware');

        return $redirectResponse;
    }
}
