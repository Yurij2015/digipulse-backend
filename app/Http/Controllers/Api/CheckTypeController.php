<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\CheckTypeResource;
use App\Models\CheckType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CheckTypeController extends Controller
{
    /**
     * Display a listing of available check types.
     */
    public function index(): AnonymousResourceCollection
    {
        return CheckTypeResource::collection(
            CheckType::where('is_active', true)->get()
        );
    }
}
