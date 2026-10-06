<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            "title" => ["required", "string", "max:255",""]
        ]);
        return $request->all();
    }
}
