<?php

namespace App\Services\Collectors;

use App\Models\Item;
use App\Models\Source;
use Illuminate\Support\Collection;

abstract class AbstractSourceCollector implements SourceCollector
{
    /**
     * Persist the attributes that don't already exist (by external_id) as
     * new items for the given source, and return the ones created.
     *
     * @param  Collection<int, array>  $attributesList
     * @return Collection<int, Item>
     */
    protected function persistNewItems(Source $source, Collection $attributesList): Collection
    {
        return $attributesList
            ->reject(fn (array $attributes) => Item::query()
                ->where('source_id', $source->id)
                ->where('external_id', $attributes['external_id'])
                ->exists())
            ->map(fn (array $attributes) => Item::create([
                'source_id' => $source->id,
                ...$attributes,
            ]))
            ->values();
    }
}
