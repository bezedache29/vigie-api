<?php

namespace App\Http\Controllers;

use App\Http\Resources\SummaryResource;
use App\Models\Summary;

class SummaryController extends Controller
{
    public function index()
    {
        $summaries = Summary::latest()->paginate();

        return SummaryResource::collection($summaries);
    }

    public function show(Summary $summary)
    {
        return new SummaryResource($summary);
    }
}
