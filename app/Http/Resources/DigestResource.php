<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_ids' => $this->item_ids,
            'channel' => $this->channel,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
        ];
    }
}
