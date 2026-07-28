<?php

namespace App\Http\Controllers\SiteMap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $products = Product::where('status', 1)
            ->select('id','slug','updated_at')
            ->latest('updated_at')
            ->get();

        $categories = ProductCategory::where('status', 1)
            ->select('id','slug','updated_at')
            ->latest('updated_at')
            ->get();

        return response()
            ->view('sitemap', compact('products', 'categories'))
            ->header('Content-Type', 'application/xml');
    }
}
