<?php

namespace App\Http\Controllers\Ecommerce;

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

use App\Models\User;
use App\Models\ProductRating;
use App\Models\ProductRatingImage;
use App\Models\Product;

class RatingController extends Controller
{
    public function index()
    {
        try {

            $ratings = ProductRating::with([
                    'product',
                    'user',
                    'images'
                ])
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Ratings retrieved successfully.',
                'data'    => $ratings,
            ], 200);

        } catch (\Throwable $e) {

            \Log::error('Product Rating Fetch Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ratings.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Backend connected.',
        ], 422);

        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'],
            'rating'     => ['required', 'integer', 'between:1,5'],
            'title'      => ['nullable', 'string', 'max:255'],
            'review'     => ['nullable', 'string', 'max:5000'],

            'images'     => ['nullable', 'array', 'max:4'],
            'images.*'   => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $exists = ProductRating::where('product_id', $request->product_id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product.'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $product = Product::findOrFail($request->product_id);

            $rating = ProductRating::create([
                'product_id'          => $product->id,
                'user_id'             => Auth::id(),
                'rating'              => $request->rating,
                'title'               => $request->title,
                'review'              => $request->review,
                'verified_purchase'   => false,
                'is_approved'         => false,
                'is_featured'         => false,
                'helpful_count'       => 0,
                'unhelpful_count'     => 0,
            ]);

            if ($request->hasFile('images')) {
                $this->storeRatingImages($request->file('images'), $rating);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully.',
                'data'    => $rating->load('images'),
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    private function storeRatingImages(array $images, ProductRating $rating): void
    {
        $imageData = [];

        foreach ($images as $index => $image) {

            $path = $image->store('product-ratings', 'public');

            $imageData[] = [
                'product_rating_id' => $rating->id,
                'image'             => $path,
                'position'          => $index,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        if (!empty($imageData)) {
            ProductRatingImage::insert($imageData);
        }
    }
}
