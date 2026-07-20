<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Http\Requests\StoreProductRequest;
use App\Models\User;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Stock;
use App\Models\ProductVariant;
use App\Models\ProductImage;

class ProductController extends Controller
{
    public function index(){
        try{
            $products = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                'images:id,product_id,image_path,is_primary'
            ])->get();

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully.',
                'data' => $products
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Products can not fetched.',
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




    public function storeBrand(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name'
        ]);

        try {

            $brand = Brand::create([
                'name' => trim($request->name),
                'slug' => Str::slug($request->name),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully.',
                'data' => $brand
            ], 201);

        } catch (\Exception $e) {

            Log::error('Brand Store Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    public function deleteBrand($id)
    {
        try {

            $brand = Brand::findOrFail($id);
            $brand->delete();

            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Brand not found.'
            ], 404);

        } catch (\Exception $e) {

            Log::error('Brand Delete Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    public function editBrand(Request $request, $id)
    {
        $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('brands', 'name')->ignore($id)
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            $brand = Brand::findOrFail($id);

            $brand->update([
                'name'      => trim($request->name),
                'slug'      => Str::slug($request->name),
                'is_active' => $request->is_active,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully.',
                'data'    => $brand->fresh(),
            ]);

        } catch (ModelNotFoundException $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
            ], 404);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Brand Update Error', [
                'brand_id' => $id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update brand.',
            ], 500);
        }
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {

            $name = trim($validated['name']);
            $slug = Str::slug($name);

            $category = ProductCategory::create([
                // Basic
                'name' => $name,
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'image' => $validated['image'] ?? null,

                // SEO
                'meta_title' => Str::limit($name, 60, ''),
                'meta_description' => Str::limit(
                    strip_tags($validated['description'] ?? "{$name} products at the best price."),
                    160,
                    ''
                ),
                'meta_keywords' => strtolower($name),

                'og_title' => $name,
                'og_description' => Str::limit(
                    strip_tags($validated['description'] ?? "{$name} products at the best price."),
                    200,
                    ''
                ),
                'og_image' => $validated['image'] ?? null,

                // Canonical
                'canonical_url' => url("/category/{$slug}"),

                // Robots
                'robots' => 'index,follow',
                'indexable' => true,

                // Status
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Getting after ID Canonical URL Update
            $category->update([
                'canonical_url' => url("/category/{$category->slug}/{$category->id}")
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {

            Log::error('Category Store Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    public function deleteCategory($id)
    {
        $category = ProductCategory::findOrFail($id);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.'
        ]);
    }

    public function editCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:product_categories,name,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = ProductCategory::findOrFail($id);
            $category->update([
                'name'      => trim($request->name),
                'slug'      => Str::slug($request->name),
                'is_active' => $request->is_active ?? true,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Category Update Error', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating category.'
            ], 500);
        }
    }

    public function storeSubCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'is_active' => 'boolean'
        ]);

        try {

            $name = trim($request->name);

            // Generate Slug
            $slug = Str::slug($name);

            if (ProductSubCategory::where('slug', $slug)->exists()) {
                $slug .= '-' . time();
            }


            // SEO Auto Generate (Ogrova)
            $metaTitle = $name . ' - Buy Online in Bangladesh | Ogrova';


            $metaDescription =
                "Shop {$name} at Ogrova Bangladesh. Find quality products at the best price with fast delivery and trusted online shopping experience.";


            $metaKeywords =
                strtolower($name) .
                ', buy ' .
                strtolower($name) .
                ', online shopping Bangladesh, Ogrova';


            // Open Graph
            $ogTitle = $name . ' | Ogrova';


            $ogDescription =
                "Explore {$name} products on Ogrova. Shop smart with affordable prices, authentic products and reliable delivery across Bangladesh.";


            // Canonical URL
            $canonicalUrl = url('/subcategory/' . $slug);


            $sub = ProductSubCategory::create([

                'name' => $name,
                'slug' => $slug,
                'category_id' => $request->category_id,
                'is_active' => $request->is_active ?? true,


                // SEO
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'meta_keywords' => $metaKeywords,


                // Open Graph
                'og_title' => $ogTitle,
                'og_description' => $ogDescription,
                'og_image' => null,


                // SEO Control
                'canonical_url' => $canonicalUrl,
                'robots' => 'index,follow',
                'indexable' => true,

            ]);


            return response()->json([
                'success' => true,
                'message' => 'Sub Category created successfully.',
                'data' => $sub
            ], 201);


        } catch (\Exception $e) {

            Log::error('SubCategory Store Error', [
                'error' => $e->getMessage()
            ]);


            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    public function deleteSubCategory($id)
    {
        $subCategory = ProductSubCategory::findOrFail($id);

        $subCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub-Category deleted successfully.'
        ]);
    }

    public function editSubCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $subCategory = ProductSubCategory::findOrFail($id);

            $subCategory->update([
                'name'       => trim($request->name),
                'slug'       => Str::slug($request->name),
                'category_id'=> $request->category_id,
                'is_active'  => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sub Category updated successfully.',
                'data' => $subCategory
            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Sub Category not found.'
            ], 404);

        } catch (\Exception $e) {

            \Log::error('SubCategory Update Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error occurred'
            ], 500);
        }
    }


        // try {
        //     DB::beginTransaction();

        //     $product = new Product();

        //     $product->name = $data['name'];
        //     $product->sku = $data['sku'];

        //     $product->brand_id = $data['brand'];
        //     $product->category_id = $data['category'];
        //     $product->subcategory_id = $data['subcategory'];

        //     $product->price = $data['price'];
        //     $product->discount = $data['discount'] ?? 0;
        //     $product->stock_quantity = $data['stock_quantity'];
        //     $product->min_stock = $data['min_stock'] ?? 0;

        //     $product->summary = $data['summary'] ?? null;
        //     $product->description = $data['description'] ?? null;
        //     $product->slug = $data['slug'] ?? null;

        //     $product->meta_title = $data['title'] ?? null;
        //     $product->meta_keywords = $data['keywords'] ?? null;
        //     $product->meta_description = $data['meta_description'] ?? null;

        //     $product->is_featured = $data['is_featured'] ?? false;
        //     $product->is_on_sale = $data['is_on_sale'] ?? false;
        //     $product->is_active = $data['is_active'] ?? true;

        //     $product->save();

        //     // save product variants if provided
        //     if ($request->has('variants') && is_array($request->variants)) {
        //         foreach ($request->variants as $variant) {
        //             ProductVariant::create([
        //                 'product_id' => $product->id,
        //                 'color' => $variant['color'] ?? null,
        //                 'size' => $variant['size'] ?? null,
        //                 'price' => $variant['price'] ?? 0,
        //                 'stock' => $variant['stock'] ?? 0,
        //             ]);
        //         }
        //     }

        //     // save product images and get their paths
        //     $images = $this->storeProductImages($request, $product->id, $user);
        //     $product->images = $images;

        //     DB::commit();

        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Product created successfully.',
        //         'data' => $product
        //     ], 201);
        // } catch (\Throwable $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'success' => false,
        //         'message' => "Product can not created. Error: " . $e->getMessage(),
        //     ], 500);
        // }


    public function store(StoreProductRequest $request){

        $user = auth('sanctum')->user();
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($request, $data) {

                // SEO Auto Generate
                // Meta title, description and keyword auto generate

                $brand           = Brand::find($data['brand']);
                $category        = ProductCategory::find($data['category']);
                $subcategory     = ProductSubCategory::find($data['subcategory']);

                $brandName       = $brand?->name;
                $categoryName    = $category?->name;
                $subcategoryName = $subcategory?->name;

                $productName     = trim($data['name']);

                $metaTitle = !empty($data['title']) ? $data['title'] :
                                Str::limit( "{$productName} | {$brandName} | Buy Online in Bangladesh | Ogrova", 60, '' );

                $metaDescription = !empty($data['meta_description']) ? $data['meta_description'] :
                                        Str::limit( "Buy {$productName} by {$brandName} at the best price in Bangladesh from Ogrova. 100% genuine {$categoryName}, fast delivery, cash on delivery, secure online payment and nationwide shipping.",
                                        160, '' );

                $metaKeywords = !empty($data['keywords'])
                    ? $data['keywords']
                    : implode(', ', array_unique(array_filter([
                        $productName,
                        "{$productName} Bangladesh",
                        "Buy {$productName}",
                        $brandName,
                        "{$brandName} Bangladesh",
                        $categoryName,
                        $subcategoryName,
                        "Best Price Bangladesh",
                        "Online Shopping Bangladesh",
                        "Original Product",
                        "Cash On Delivery",
                        "Fast Delivery",
                        "Ogrova",
                    ])));


                $product = Product::create([
                    'name'             => $data['name'],
                    'sku'              => $data['sku'],
                    'brand_id'         => $data['brand'],
                    'category_id'      => $data['category'],
                    'subcategory_id'   => $data['subcategory'],
                    'purchase_price'   => $data['purchase_price'],
                    'price'            => $data['price'],
                    'discount'         => $data['discount'] ?? 0,
                    'stock_quantity'   => $data['stock_quantity'],
                    'min_stock'        => $data['min_stock'] ?? 0,
                    'summary'          => $data['summary'] ?? null,
                    'description'      => $data['description'] ?? null,

                    'slug'             => $data['slug'] ?? null,

                    'meta_title'       => $metaTitle,
                    'meta_keywords'    => $metaKeywords,
                    'meta_description' => $metaDescription,

                    'is_featured'      => $data['is_featured'] ?? false,
                    'is_on_sale'       => $data['is_on_sale'] ?? false,
                    'is_active'        => $data['is_active'] ?? true,
                    'point'            => $data['point'] ?? 0,
                ]);

                if ($request->has('variants')) {
                    foreach ($request->variants as $variant) {
                        $product->variants()->create([
                            'color'          => $variant['color'] ?? null,
                            'size'           => $variant['size'] ?? null,
                            'price'          => $variant['price'] ?? 0,
                            'discount'       => $variant['discount'] ?? 0,
                            'stock_quantity' => $variant['stock'] ?? 0,
                        ]);
                    }
                }

                if ($request->hasFile('images')) {
                    $this->storeProductImages($request->file('images'), $product);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully.',
                    'data'    => $product->load(['variants', 'images'])
                ], 201);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), "Failed to create product.",
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function storeProductImages(array $images, Product $product): void
    {
        $imageData = [];

        foreach ($images as $index => $image) {
            $path = $image->store('products', 'public');

            $imageData[] = [
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $index === 0,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($imageData)) {
            ProductImage::insert($imageData);
        }
    }

    public function show($slug){
        try {
            $product = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                'variants:id,product_id,color,size,price,stock_quantity,discount',
                'images:id,product_id,image_path,is_primary'
            ])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->where('is_active', 1)
            ->where('approval_status', 1)
            ->where('slug', $slug)->firstOrFail();

            // Related Products
            $limit = 15;
            $excludedIds = [$product->id];

            // 1. Same Subcategory (Random)
            $categoryProducts = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                'variants:id,product_id,color,size,price,stock_quantity,discount',
                'images:id,product_id,image_path,is_primary'
            ])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->whereNotIn('id', $excludedIds)
            ->where('is_active', 1)
            ->where('approval_status', 1)
            ->where('subcategory_id', $product->subcategory_id)
            ->inRandomOrder()
            ->take($limit)
            ->get();

            $excludedIds = array_merge($excludedIds, $categoryProducts->pluck('id')->toArray());

            // 2. Same Category (Random)
            if ($categoryProducts->count() < $limit) {

                $remaining = $limit - $categoryProducts->count();

                $extraProducts = Product::with([
                        'category:id,name',
                        'subcategory:id,name',
                        'brand:id,name',
                        'variants:id,product_id,color,size,price,stock_quantity,discount',
                        'images:id,product_id,image_path,is_primary'
                    ])
                    ->withAvg('ratings', 'rating')
                    ->withCount('ratings')
                    ->where('is_active', 1)
                    ->where('approval_status', 1)
                    ->whereNotIn('id', $excludedIds)
                    ->where('category_id', $product->category_id)
                    ->inRandomOrder()
                    ->take($remaining)
                    ->get();

                $categoryProducts = $categoryProducts->concat($extraProducts);

                $excludedIds = array_merge($excludedIds, $extraProducts->pluck('id')->toArray());
            }

            // 3. Other Category (Fill Remaining)
            if ($categoryProducts->count() < $limit) {

                $remaining = $limit - $categoryProducts->count();

                $otherProducts = Product::with([
                        'category:id,name',
                        'subcategory:id,name',
                        'brand:id,name',
                        'variants:id,product_id,color,size,price,stock_quantity,discount',
                        'images:id,product_id,image_path,is_primary'
                    ])
                    ->withAvg('ratings', 'rating')
                    ->withCount('ratings')
                    ->where('is_active', 1)
                    ->where('approval_status', 1)
                    ->whereNotIn('id', $excludedIds)
                    ->orderByDesc('total_click')
                    ->take($remaining)
                    ->get();

                $categoryProducts = $categoryProducts->concat($otherProducts);
            }

            // Transform images (VERY IMPORTANT for live server)
            $product->images->transform(function ($image) {
                $image->url = asset('storage/' . $image->image_path);
                return $image;
            });

            $categoryProducts->each(function ($relatedProduct) {
                $relatedProduct->images->transform(function ($image) {
                    $image->url = asset('storage/' . $image->image_path);
                    return $image;
                });
            });

            $product->increment('total_click');

            return response()->json([
                'success' => true,
                'message' => 'Product fetched successfully.',
                'data' => $product,
                'category_products' => $categoryProducts
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);

        } catch (\Throwable $e) {

            // log error for production debugging
            Log::error('Product show API error', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching product.',
            ], 500);
        }
    }

    public function edit(Request $request, $id){

        try {
            $product = Product::with('images')->findOrFail($id);

            return DB::transaction(function () use ($request, $product) {

                $product->update($request->only([
                    'name', 'sku', 'price', 'discount', 'stock_quantity',
                    'min_stock', 'summary', 'description', 'meta_title',
                    'meta_keywords', 'meta_description', 'point'
                ]));

                if ($request->has('brand')) $product->brand_id = $request->brand;
                if ($request->has('category')) $product->category_id = $request->category;
                if ($request->has('subcategory')) $product->subcategory_id = $request->subcategory;

                $product->is_featured = $request->boolean('is_featured', $product->is_featured);
                $product->is_on_sale = $request->boolean('is_on_sale', $product->is_on_sale);
                $product->is_active = $request->boolean('is_active', $product->is_active);

                if ($request->has('variants')) {

                    $product->variants()->delete();

                    $variants = [];
                    foreach ($request->variants as $variant) {
                        $variants[] = [
                            'product_id'        => $product->id,
                            'color'             => $variant['color'] ?? null,
                            'size'              => $variant['size'] ?? null,
                            'price'             => $variant['price'] ?? 0,
                            'discount'          => $variant['discount'] ?? 0,
                            'stock_quantity'    => $variant['stock_quantity'] ?? 0,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    }

                    if (!empty($variants)) {
                        ProductVariant::insert($variants);
                    }
                }

                if ($request->hasFile('images')) {
                    $this->updateProductImages($product, $request->file('images'));
                }

                if ($product->isDirty()) {
                    $product->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully.',
                    'data' => $product->load('images')
                ], 200);
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    private function updateProductImages(Product $product, array $newImages): void
    {
        foreach ($product->images as $oldImage) {
            Storage::disk('public')->delete($oldImage->image_path);
            $oldImage->delete();
        }

        $imageData = [];
        foreach ($newImages as $index => $image) {
            $path = $image->store('products', 'public');
            $imageData[] = [
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $index === 0,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($imageData)) {
            ProductImage::insert($imageData);
        }
    }

    public function delete($id){
        DB::beginTransaction();

        try {
            $product = Product::with(['images', 'variants'])->findOrFail($id);

            // Delete images from storage + DB
            foreach ($product->images as $img) {
                if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
                $img->delete(); // DB
            }

            // Delete variants
            foreach ($product->variants as $variant) {
                $variant->delete();
            }

            // Delete product
            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Delete failed. ' . $e->getMessage()
            ], 500);
        }
    }

    public function reportSale()
    {
        try{
            $products = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                'images:id,product_id,image_path,is_primary'
            ])->latest()->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully.',
                'data' => $products
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Products can not fetched.',
            ], 500);
        }
    }
}
