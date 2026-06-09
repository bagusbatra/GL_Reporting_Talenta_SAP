<?php

namespace App\Http\Controllers;

class HelpController extends Controller
{
    /**
     * GET /help - tampilkan halaman dokumentasi sistem
     */
    public function index()
    {
        return view('help.index');
    }
}