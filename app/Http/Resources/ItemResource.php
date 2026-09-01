<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
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
            'source_id' => $this->source_id,
            'title' => $this->title,
            'url' => $this->url,
            'raw_content' => $this->raw_content,
            'published_at' => $this->published_at,
            'status' => $this->status,
            'summary' => new SummaryResource($this->whenLoaded('summary')),
            'created_at' => $this->created_at,
        ];
    }
}
