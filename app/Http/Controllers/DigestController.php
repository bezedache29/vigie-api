<?php

namespace App\Http\Controllers;

use App\Http\Resources\DigestResource;
use App\Models\Digest;

class DigestController extends Controller
{
    public function index()
    {
        $digests = Digest::latest()->paginate();

        return DigestResource::collection($digests);
    }

    public function show(Digest $digest)
    {
        return new DigestResource($digest);
    }
}
