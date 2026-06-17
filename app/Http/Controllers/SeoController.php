<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Retourne la sitemap XML avec les URLs publiques statiques.
     */
    public function sitemap(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $now     = now()->toAtomString();

        $pages = [
            ['loc' => $baseUrl . '/',                    'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $baseUrl . '/menu',                'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => $baseUrl . '/fidelite',            'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . '/fidelite/mes-points', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . '/contact',             'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as $page) {
            $xml .= "  <url>\n"
                  . '    <loc>' . htmlspecialchars($page['loc'], ENT_XML1) . "</loc>\n"
                  . "    <lastmod>{$now}</lastmod>\n"
                  . "    <changefreq>{$page['changefreq']}</changefreq>\n"
                  . "    <priority>{$page['priority']}</priority>\n"
                  . "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Retourne robots.txt avec la déclaration de sitemap dynamique.
     */
    public function robots(): Response
    {
        $sitemapUrl = rtrim(config('app.url'), '/') . '/sitemap.xml';

        $content = "User-agent: *\n"
                 . "Disallow: /espace-employe/\n"
                 . "Disallow: /login\n"
                 . "Disallow: /reset-employe/\n"
                 . "Disallow: /fidelite/reinitialiser-pin/\n"
                 . "Allow: /\n\n"
                 . "Sitemap: {$sitemapUrl}\n";

        return response($content, 200, [
            'Content-Type'  => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
