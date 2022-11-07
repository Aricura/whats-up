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

namespace App\Error;

use Bartacus\Bundle\BartacusBundle\Middleware\PrepareContentElementRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Http\RequestHandler;
use TYPO3\CMS\Frontend\Middleware\ContentLengthResponseHeader;
use TYPO3\CMS\Frontend\Middleware\FrontendUserAuthenticator;
use TYPO3\CMS\Frontend\Middleware\OutputCompression;
use TYPO3\CMS\Frontend\Middleware\PageResolver;
use TYPO3\CMS\Frontend\Middleware\PrepareTypoScriptFrontendRendering;
use TYPO3\CMS\Frontend\Middleware\SiteResolver;
use TYPO3\CMS\Frontend\Middleware\TypoScriptFrontendInitialization;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

abstract class AbstractPageErrorHandler implements PageErrorHandlerInterface
{
    public const PAGE_ID_FIELD = 'errorPageId';

    protected static bool $isRequestLooping = false;
    protected int $statusCode;
    protected array $errorConfig;

    public function __construct(int $statusCode, array $errorConfig)
    {
        $this->statusCode = $statusCode;
        $this->errorConfig = $errorConfig;
    }

    /**
     * Handles all page errors and render a custom error page depending on the status code.
     */
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $this->clearBuffer();

        // prevent the request from loops if the specified error page returns a 404 status code.
        if ($this->isLooping()) {
            return $this->fallbackResponse($message);
        }

        // treat hidden or non released pages as 404 error
        $this->treatHiddenAsNotFound($reasons);

        // get the error page and language id for the current error code
        $errorPageId = $this->resolveErrorPageId($request);
        $errorLanguageId = $this->resolveErrorPageLanguageId($request);

        // fake a 404 Not Found if the AccessDenied page is already requested
        // (this occurs if already logged in and requesting the login page)
        $fakeErrorHandler = $this->fakeErrorHandler($reasons, $errorPageId);
        if ($fakeErrorHandler instanceof self) {
            return $fakeErrorHandler->handlePageError($request, $message, $reasons);
        }

        // generate the error page uri
        $errorPageUri = $this->getErrorPageUri($request, $errorPageId, $errorLanguageId);

        // manipulate the request URI to match the error page id
        if ($errorPageUri instanceof UriInterface) {
            $request = $request->withUri($errorPageUri);
        }

        // clear old request information which might be set by SiteMatcher and Bartacus content rendering
        $request = $this->cleanupRequest($request);

        // add the page arguments as route attribute
        $request = $request->withAttribute('routing', new PageArguments($errorPageId, '0', []));

        // set the new request for the TYPO3 context
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // handle the request as return its response as error page
        // the response was rendered with a 200 OK status code
        // but the error page expects status code related to the error handler instead
        return $this->handleRequest($request)->withStatus($this->statusCode);
    }

    /**
     * Treat hidden or non released pages as 404 error.
     */
    protected function treatHiddenAsNotFound(array $reasons): void
    {
        if (PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED === (string) $reasons['code']) {
            if ($reasons['hidden'] || $reasons['starttime'] || $reasons['endtime']) {
                $this->statusCode = \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND;
            }
        }
    }

    /**
     * Fakes the current error handler to pretend being any other error status code.
     */
    protected function fakeErrorHandler(array $reasons, int $errorPageId): ?self
    {
        // fake a 404 Not Found if the AccessDenied page is already requested
        // (this occurs if already logged in and requesting the login page)
        if (PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED === (string) $reasons['code']
            && \array_key_exists('code', $reasons) && 'access.page' === (string) $reasons['code']
            && \array_key_exists('fe_group', $reasons) && \is_array($reasons['fe_group'])
            && \array_key_exists($errorPageId, $reasons['fe_group'])) {
            // - this is an AccessDenied error handler
            // - the page is not accessible
            // - the page is not accessible because of a fe_group restriction
            // get the Not Found class for the same site
            $notFoundClassName = str_replace('AccessDenied', 'NotFound', static::class);

            if (class_exists($notFoundClassName)) {
                // reset the loop handler
                self::$isRequestLooping = false;

                // create the NotFound error handler for the same site as this one should be handled
                return new $notFoundClassName(\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND, $this->errorConfig);
            }
        }

        return null;
    }

    /**
     * Clears old request information which might be set by SiteMatcher and Bartacus content rendering.
     */
    protected function cleanupRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request
            ->withQueryParams([])
            ->withoutAttribute('routing')
            ->withoutAttribute('_controller')
            ->withoutAttribute('_locale')
            ->withoutAttribute('data')
            ;
    }

    /**
     * Returns the TypoScript frontend controller and ensures required properties are initialized.
     */
    protected function getErrorPageUri(ServerRequestInterface $request, int $errorPageId, int $errorPageLanguageId): ?UriInterface
    {
        $site = $request->getAttribute('site');

        return $site instanceof Site ? $site->getRouter()->generateUri($errorPageId, ['_language' => $errorPageLanguageId]) : null;
    }

    /**
     * Define the middleware stack which will be applied to the request to create the error response content.
     */
    protected function getMiddlewareStack(): array
    {
        return [
            'typo3/cms-frontend/output-compression' => OutputCompression::class,
            'typo3/cms-frontend/content-length-headers' => ContentLengthResponseHeader::class,
            'typo3/cms-frontend/prepare-tsfe-rendering' => PrepareTypoScriptFrontendRendering::class,
            'bartacus/prepare-content-element-renderer' => PrepareContentElementRenderer::class,
            'typo3/cms-frontend/page-resolver' => PageResolver::class,
            'typo3/cms-frontend/site' => SiteResolver::class,
            'typo3/cms-frontend/authentication' => FrontendUserAuthenticator::class,
            'typo3/cms-frontend/tsfe' => TypoScriptFrontendInitialization::class,
        ];
    }

    /**
     * Handle the request and pass it to the shorten middleware stack.
     */
    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        // get the middleware stack which will be applied to the request to create the error response content
        $requestHandler = GeneralUtility::makeInstance(RequestHandler::class);
        $dispatcher = new MiddlewareDispatcher($requestHandler, $this->getMiddlewareStack());

        // dispatch the middleware stack and return the response
        return $dispatcher->handle($request);
    }

    /**
     * Checks if the current request loops and calls the error handler again.
     */
    protected function isLooping(): bool
    {
        if (self::$isRequestLooping) {
            return true;
        }

        self::$isRequestLooping = true;

        return false;
    }

    /**
     * Returns a fallback response if the error handler loops or the error page could not be determined.
     */
    protected function fallbackResponse(string $message): Response
    {
        return new Response(
            fopen('data://text/plain,'.'<h1>404 Error Page Not Found</h1><p>Original message and status code: '.$message.' ('.$this->statusCode.')</p>', 'rb'),
            \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Clears the current output buffer.
     */
    protected function clearBuffer(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Resolves the TYPO3 page uid used to render the error.
     */
    protected function resolveErrorPageId(ServerRequestInterface $request): int
    {
        $errorPageId = \array_key_exists(self::PAGE_ID_FIELD, $this->errorConfig) ? (int) $this->errorConfig[self::PAGE_ID_FIELD] : 0;

        if ($errorPageId > 0) {
            return $errorPageId;
        }

        $site = $request->getAttribute('site');

        return $site instanceof Site ? $site->getRootPageId() : 1;
    }

    /**
     * Resolves the TYPO3 sys_language uid used to render the error.
     */
    protected function resolveErrorPageLanguageId(ServerRequestInterface $request): int
    {
        $siteLanguage = $request->getAttribute('language');

        if ($siteLanguage instanceof SiteLanguage) {
            return $siteLanguage->getLanguageId();
        }

        $site = $request->getAttribute('site');

        return $site instanceof Site ? $site->getDefaultLanguage()->getLanguageId() : 0;
    }
}
