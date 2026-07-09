<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrontendController extends Controller
{
    /**
     * Show the application homepage/landing page.
     */
    public function home()
    {
        return view('frontend.home');
    }

    /**
     * Show the pricing page.
     */
    public function pricing()
    {
        return view('frontend.pricing');
    }

    /**
     * Show the about us page.
     */
    public function about()
    {
        return view('frontend.about');
    }

    /**
     * Show the contact us page.
     */
    public function contact()
    {
        return view('frontend.contact');
    }
}
