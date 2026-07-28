<?php

namespace App\Http\Controllers\SiteMap;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Response;
use XMLWriter;

class SitemapController extends Controller
{
    public function index()
    {
        $frontend = rtrim(config('app.frontend_url'), '/');

        $products = Product::query()
            ->where('status', 1)
            ->select('slug', 'updated_at')
            ->latest('updated_at')
            ->get();

        $categories = ProductCategory::query()
            ->where('status', 1)
            ->select('id', 'slug', 'updated_at')
            ->latest('updated_at')
            ->get();

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('urlset');
        $xml->writeAttribute(
            'xmlns',
            'http://www.sitemaps.org/schemas/sitemap/0.9'
        );

        // Home
        $xml->startElement('url');
        $xml->writeElement('loc', $frontend);
        $xml->writeElement('lastmod', now()->toAtomString());
        $xml->writeElement('changefreq', 'daily');
        $xml->writeElement('priority', '1.0');
        $xml->endElement();

        // Categories
        foreach ($categories as $category) {
            $xml->startElement('url');
            $xml->writeElement(
                'loc',
                "{$frontend}/category/{$category->slug}/{$category->id}"
            );
            $xml->writeElement(
                'lastmod',
                optional($category->updated_at)?->toAtomString() ?? now()->toAtomString()
            );
            $xml->writeElement('changefreq', 'weekly');
            $xml->writeElement('priority', '0.8');
            $xml->endElement();
        }

        // Products
        foreach ($products as $product) {
            $xml->startElement('url');
            $xml->writeElement(
                'loc',
                "{$frontend}/product-details/{$product->slug}"
            );
            $xml->writeElement(
                'lastmod',
                optional($product->updated_at)?->toAtomString() ?? now()->toAtomString()
            );
            $xml->writeElement('changefreq', 'weekly');
            $xml->writeElement('priority', '0.7');
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return response(
            $xml->outputMemory(),
            200,
            [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Cache-Control' => 'public, max-age=3600',
            ]
        );
    }
}
