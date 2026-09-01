<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSourceRequest;
use App\Http\Requests\UpdateSourceRequest;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\Response;

class SourceController extends Controller
{
    public function index()
    {
        return SourceResource::collection(Source::latest()->paginate());
    }

    public function store(StoreSourceRequest $request)
    {
        $source = Source::create($request->validated());

        return (new SourceResource($source))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Source $source)
    {
        return new SourceResource($source);
    }

    public function update(UpdateSourceRequest $request, Source $source)
    {
        $source->update($request->validated());

        return new SourceResource($source);
    }

    public function destroy(Source $source)
    {
        $source->delete();

        return response()->noContent();
    }
}
