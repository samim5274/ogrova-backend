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
}
