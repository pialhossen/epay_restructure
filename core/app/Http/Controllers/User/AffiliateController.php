<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Referral;

class AffiliateController extends Controller
{
    public function affiliate()
    {
        $pageTitle = 'Affiliation';
        $user = auth()->user()->load('allReferrals');
        $maxLevel = Referral::max('level');

        return view('Template::user.affiliate.index', compact('pageTitle', 'user', 'maxLevel'));
    }
}
