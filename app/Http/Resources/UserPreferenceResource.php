<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'keywords' => $this->keywords,
            'digest_frequency' => $this->digest_frequency,
            'active_source_ids' => $this->user->sources->pluck('id'),
        ];
    }
}
