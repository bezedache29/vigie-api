<?php

namespace App\Services\Collectors;

use App\Models\Item;
use App\Models\Source;
use Illuminate\Support\Collection;

interface SourceCollector
{
    /**
     * Fetch new content from the given source, persist it as items
     * (skipping duplicates already known via external_id) and return
     * the newly created items.
     *
     * @return Collection<int, Item>
     */
    public function fetch(Source $source): Collection;
}
