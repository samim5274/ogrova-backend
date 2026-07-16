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
    public function index(Request $request)
    {
        try {

            $query = ProductRating::with(['product', 'user', 'images'])
                ->where('is_approved', true)
                ->latest();

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            $ratings = $query->get();

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
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

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

        $validated = $validator->validated();

        DB::beginTransaction();

        try {

            $alreadyReviewed = ProductRating::where('product_id', $validated['product_id'])
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->exists();

            if ($alreadyReviewed) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted a review for this product.',
                ], 422);
            }

            $rating = ProductRating::create([
                'product_id'        => $validated['product_id'],
                'user_id'           => Auth::id(),
                'rating'            => $validated['rating'],
                'title'             => $validated['title'] ?? null,
                'review'            => $validated['review'] ?? null,
                'verified_purchase' => true,
                'is_approved'       => true,
                'is_featured'       => false,
                'helpful_count'     => 0,
                'unhelpful_count'   => 0,
            ]);

            if ($request->hasFile('images')) {
                $this->storeRatingImages(
                    $request->file('images'),
                    $rating
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your review has been submitted successfully.',
                'data'    => $rating->load(['images', 'user']),
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Product rating creation failed.', [
                'user_id'    => Auth::id(),
                'product_id' => $validated['product_id'] ?? null,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit your review at this moment. Please try again later.',
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
