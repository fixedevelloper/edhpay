<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function get_terms_and_conditions()
    {
        return view('terms-and-conditions');
    }

    public function get_privacy_policy()
    {
        return view('privacy-policy');
    }

    public function get_about_us()
    {
        return view('about-us');
    }

}
