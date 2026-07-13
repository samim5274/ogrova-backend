<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\Brand;
use App\Models\Product;

class SearchController extends Controller
{
    public function suggestions(Request $request)
    {
        $q = trim($request->q);

        if (strlen($q) < 2) {
            return response()->json([
                'products'   => [],
                'categories' => [],
                'brands'     => [],
            ]);
        }

        $products = Product::query()->with(['images:id,product_id,image_path'])
            ->select(
                'id',
                'name',
                'slug',
                'price',
                'discount_price'
            )
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%");
            })
            ->limit(6)
            ->get();

        $categories = ProductCategory::query()
            ->select('id', 'name', 'slug')
            ->where('is_active', true)
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get();

        $brands = Brand::query()
            ->select('id', 'name', 'slug')
            ->where('is_active', true)
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get();

        return response()->json([
            'products'   => $products,
            'categories' => $categories,
            // 'brands'     => $brands,
        ]);
    }

    public function search(Request $request)
    {
        $q = trim($request->q);

        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->paginate(20);

        return response()->json($products);
    }
}
