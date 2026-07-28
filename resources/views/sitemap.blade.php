<?='<?xml version="1.0" encoding="UTF-8"?>'?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<url>
    <loc>{{ url('/') }}</loc>
    <lastmod>{{ now()->toAtomString() }}</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
</url>


{{-- Categories --}}
@foreach($categories as $category)

<url>
    <loc>{{ url('/category/' . $category->slug . '/' . $category->id) }}</loc>
    <lastmod>{{ optional($category->updated_at)->toAtomString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
</url>

@endforeach



{{-- Products --}}
@foreach($products as $product)

<url>
    <loc>{{ url('/product-details/' . $product->slug) }}</loc>
    <lastmod>{{ optional($product->updated_at)->toAtomString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
</url>

@endforeach


</urlset>
