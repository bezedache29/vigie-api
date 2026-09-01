<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SummaryResource extends JsonResource
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
            'item_id' => $this->item_id,
            'summary_text' => $this->summary_text,
            'tags' => $this->tags,
            'relevance_score' => $this->relevance_score,
            'model_used' => $this->model_used,
            'created_at' => $this->created_at,
        ];
    }
}
