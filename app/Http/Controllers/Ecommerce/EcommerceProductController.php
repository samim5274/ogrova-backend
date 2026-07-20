<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Http\Requests\StoreProductRequest;
use App\Models\User;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazila;
use App\Models\PoliceStation;
use App\Models\ProductVariant;
use App\Models\ProductImage;

class EcommerceProductController extends Controller
{
    public function index(){
        try{
            $categories = ProductCategory::where('is_active', 1)
                ->select('id', 'name', 'slug')
                ->get();

            $data = $categories->map(function ($category) {
                $products = Product::with([
                    'category:id,name,slug',
                    'subcategory:id,name',
                    'brand:id,name',
                    'images:id,product_id,image_path,is_primary'
                ])
                ->withAvg('ratings', 'rating')
                ->withCount('ratings')
                ->where('category_id', $category->id)
                ->where('is_active', 1)
                ->where('approval_status', 1)
                ->inRandomOrder()
                ->take(10)
                ->get();

                // Image URL
                $products->each(function ($product) {
                    $product->images->transform(function ($image) {
                        $image->url = asset('storage/' . $image->image_path);
                        return $image;
                    });
                });

                return [
                    'category' => $category,
                    'products' => $products,
                ];
            });

            // $products = Product::with([
            //         'category:id,name,slug',
            //         'subcategory:id,name',
            //         'brand:id,name',
            //         'images:id,product_id,image_path,is_primary'
            //     ])
            //     ->withAvg('ratings', 'rating')
            //     ->withCount('ratings')
            //     ->where('is_active', 1)
            //     ->where('approval_status', 1)
            //     ->inRandomOrder()
            //     ->get()
            //     ->groupBy('category_id')
            //     ->map(function ($items) {
            //         return $items->take(10);
            //     });

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Products fetched successfully.',
            //     'data' => $products
            // ], 200);

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully.',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Products can not fetched.',
            ], 500);
        }
    }

    public function getDivision(){
        try{
            $division = Division::all();
            return response()->json([
                'success' => true,
                'data' => $division
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Division can not fetched.',
            ], 500);
        }
    }

    public function getDistrict(Request $request){
        try{
            $district = District::where('division_id', $request->division_id)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $district,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'District can not fetched.',
            ], 500);
        }
    }

    public function getUpazila(Request $request){
        try{
            $upazila = Upazila::where('district_id', $request->district_id)
                ->orderBy('name')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $upazila,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upazila can not fetched.',
            ], 500);
        }
    }

    public function getPoliceStation(Request $request){
        try{
            $policeStation = PoliceStation::where('upazila_id', $request->upazila_id)
                ->orderBy('name')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $policeStation,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'PoliceStation can not fetched.',
            ], 500);
        }
    }

    public function getCategory(){
        try{
            $productCategories = ProductCategory::all();
            return response()->json([
                'success' => true,
                'data' => $productCategories
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product categories can not fetched.',
            ], 500);
        }
    }

    public function getSubCategory(){
        try{
            $productSubCategories = ProductSubCategory::with('category:id,name')->get();
            return response()->json([
                'success' => true,
                'data' => $productSubCategories
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product sub categories can not fetched.',
            ], 500);
        }
    }

    public function getBrand(){
        try{
            $productBrands = Brand::all();
            return response()->json([
                'success' => true,
                'data' => $productBrands
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brands can not fetched.',
            ], 500);
        }
    }

    public function show($slug){
        try {
            $product = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                'variants',
                'images'
            ])->where('slug', $slug)->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Product fetched successfully.',
                'data' => $product
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Product can not fetched. Error: " . $e->getMessage(),
            ], 500);
        }
    }

    public function getCategoryProducts($id) {
        try {

            $category = ProductCategory::select(
                'id',
                'name',
                'slug',
                'image',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'canonical_url',
                'robots',
                'indexable'
            )->findOrFail($id);

            $products = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                'variants',
                'images'
            ])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->where([
                'category_id' => $id,
                'is_active' => 1,
                'approval_status' => 1,
            ])
            ->inRandomOrder()
            ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Category products fetched successfully.',
                'category' => $category,
                'products' => $products,
            ], 200);

        } catch (\Throwable $e) {
             Log::error('Category product fetch failed.', [
                'category_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching category products.',
            ], 500);
        }
    }

}
