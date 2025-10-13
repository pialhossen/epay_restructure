<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    private $user;
    public function __construct()
    {
        $this->user = auth()->guard('admin')->user();
        $this->check_permission("View - Customer Reviews");
    }
    public function index()
    {
        $pageTitle = 'Customer Reviws';
        $reviews = CustomerReview::latest()->paginate(15);

        return view('admin.review.index', compact('pageTitle', 'reviews'));
    }

    public function toggleStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:customer_reviews,id',
            'status' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = CustomerReview::findOrFail($request->id);
        $review->status = $request->status == 1 ? 0 : 1;
        $review->save();

        return response()->json(['success' => true, 'review_status' => $review->status], 200);
    }
}
