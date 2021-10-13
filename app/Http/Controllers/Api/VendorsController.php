<?php

namespace App\Http\Controllers\Api;

use App\Vendor;
use App\Http\Controllers\Controller;

class VendorsController extends Controller
{
    public function index()
    {
        return response()->json(Vendor::orderBy('sort', 'ASC')->get());
    }
}
