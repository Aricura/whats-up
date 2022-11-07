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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Site\Entity\Site;

class StaticRouteResolver extends \TYPO3\CMS\Frontend\Middleware\StaticRouteResolver
{
    /**
     * Collection of all bad user agents which are listed in the /robots.txt file
     * and should be disallowed to access any content.
     */
    private const BAD_USER_AGENTS = [
        'IsraBot',
        'UbiCrawler',
        'DOC',
        'Zao',
        'sitecheck.internetseer.com',
        'Zealbot',
        'MSIECrawler',
        'SiteSnagger',
        'WebStripper',
        'WebCopier',
        'Fetch',
        'Offline Explorer',
        'Teleport',
        'TeleportPro',
        'WebZIP',
        'linko',
        'HTTrack',
        'Microsoft.URL.Control',
        'Xenu',
        'larbin',
        'libwww',
        'ZyBORG',
        'Download Ninja',
        'wget',
        'grub-client',
        'k2spider',
        'NPBot',
        'WebReaper',
    ];

    /**
     * Resolves and returns the content and content-type depending on the specified StaticRoute type in the sites
     * config.
     *
     * @throws InvalidRouteArgumentsException
     */
    protected function resolveByType(ServerRequestInterface $request, Site $site, string $type, array $routeConfig): array
    {
        // resolve custom types
        if ('robotsTxt' === $type) {
            $content = $this->resolveRobotsTxt($site, $routeConfig);
            $contentType = 'text/plain; charset=utf-8';

            return [$content, $contentType];
        }

        // resolve default types
        return parent::resolveByType($request, $site, $type, $routeConfig);
    }

    /**
     * Resolves and returns the content used for all /robots.txt requests.
     */
    private function resolveRobotsTxt(Site $site, array $routeConfig): string
    {
        $content = [];

        // add the absolute path to all sitemap.xml files
        foreach ($site->getLanguages() as $siteLanguage) {
            $uri = $siteLanguage->getBase();
            $absolutePath = sprintf('%s://%s%s', $uri->getScheme(), $uri->getHost(), $uri->getPath());
            $absolutePath = rtrim($absolutePath, '/').'/sitemap.xml';

            $content[] = 'Sitemap: '.$absolutePath;
        }

        $content[] = "\n";

        // default user agent disallow / allow
        $content[] = 'User-agent: *';
        $content[] = 'Disallow: /typo3/';
        $content[] = 'Disallow: /typo3conf/';
        $content[] = 'Allow: /';
        $content[] = 'Allow: /typo3conf/ext/';
        $content[] = 'Allow: /typo3temp/';
        $content[] = 'Allow: /typo3/sysext/frontend/Resources/Public/*';
        $content[] = "\n";

        // disallow the access for all bad user agents
        foreach (self::BAD_USER_AGENTS as $badUserAgent) {
            $content[] = 'User-agent: '.$badUserAgent;
            $content[] = 'Disallow: /';
        }

        // append additional config
        $additionalContent = trim((string) $routeConfig['content']);
        if ('' !== $additionalContent) {
            $content[] = "\n";
            $content[] = $additionalContent;
        }

        return implode("\n", $content);
    }
}
