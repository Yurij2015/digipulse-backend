<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Sites\StoreSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SiteController extends Controller
{
    /**
     * Display a listing of the user's sites.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SiteResource::collection(
            $request->user()->sites()->latest()->get()
        );
    }

    /**
     * Store a newly created site in storage.
     */
    public function store(StoreSiteRequest $request): SiteResource
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $site = $request->user()->sites()->create($request->safe()->except('checks'));

            if ($request->has('checks')) {
                foreach ($request->checks as $check) {
                    $site->configurations()->create([
                        'check_type_id' => $check['check_type_id'],
                        'params' => $check['params'] ?? null,
                    ]);
                }
            }

            return new SiteResource($site->load('configurations.checkType'));
        });
    }
}
