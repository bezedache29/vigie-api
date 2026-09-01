<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Http\Resources\UserPreferenceResource;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $preference = $request->user()->preference()->firstOrCreate([]);

        return $this->respond($preference);
    }

    public function update(UpdateUserPreferenceRequest $request)
    {
        $user = $request->user();
        $preference = $user->preference()->firstOrCreate([]);

        $preference->update($request->safe()->only(['keywords', 'digest_frequency']));

        if ($request->has('source_ids')) {
            $user->sources()->sync($request->validated('source_ids'));
        }

        return $this->respond($preference);
    }

    private function respond($preference)
    {
        // Toujours 200 : /preferences est un singleton créé paresseusement,
        // sa création implicite n'est pas la sémantique "201 Created" du client.
        return (new UserPreferenceResource($preference->load('user.sources')))
            ->response()
            ->setStatusCode(200);
    }
}
