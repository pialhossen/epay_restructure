<?php

namespace App\Http\Controllers;

use App\Models\CustomerReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = CustomerReview::where('status', 1)->latest()->get();
        $average = CustomerReview::where('status', 1)->avg('rating');
        $count = CustomerReview::where('status', 1)->count();

        return view('reviews.index', compact('reviews', 'average', 'count'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:60',
            'email' => 'required|string|max:60',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = CustomerReview::where('email', $request->email)->first();
        if(! $review){
            $review = new CustomerReview();

            $review->user_id = auth()->id() ?? null;
            $review->name = $request->name;
            $review->email = $request->email;
            $review->rating = $request->rating;
            $review->content = $request->content;
        } else{
            $review->rating = $request->rating;
            $review->content = $request->content;
            $review->status = 0;
        }
        $review->save();

        // $review = CustomerReview::create([
        //     'user_id' => auth()->id() ?? null,
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'rating' => $request->rating,
        //     'content' => $request->content,
        // ]);

        $average = CustomerReview::where('status', 1)->avg('rating');
        $count = CustomerReview::where('status', 1)->count();

        return response()->json(
            [
                'message' => 'Review submitted successfully!',
                // 'review' => $review,
                'review' => '',
                'average' => $average,
                'count' => $count,
            ]
        );
    }

    public function getAuthUserReview()
    {
        $email = auth()->check() ? auth()->user()->email : '';
        $review = CustomerReview::where('email', $email)->firstOrFail();
        return response()->json($review);
    }
}
