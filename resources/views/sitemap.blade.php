<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<url>
    <loc>https://ogrova.mercuviax.com/</loc>
    <lastmod>{{ now()->toAtomString() }}</lastmod>
    <priority>1.0</priority>
</url>

@foreach($categories as $category)
<url>
    <loc>https://ogrova.mercuviax.com/category/{{ $category->slug }}/{{ $category->id }}</loc>
    <lastmod>{{ optional($category->updated_at)->toAtomString() }}</lastmod>
    <priority>0.8</priority>
</url>
@endforeach

@foreach($products as $product)
<url>
    <loc>https://ogrova.mercuviax.com/product-details/{{ $product->slug }}</loc>
    <lastmod>{{ optional($product->updated_at)->toAtomString() }}</lastmod>
    <priority>0.7</priority>
</url>
@endforeach

</urlset>
