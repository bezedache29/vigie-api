<?php

namespace App\Services\Collectors;

use App\Models\Source;

class CollectorResolver
{
    public function resolve(Source $source): ?SourceCollector
    {
        return match ($source->type) {
            'rss' => new FetchRssSource,
            'youtube' => new FetchYoutubeSource,
            'reddit' => new FetchRedditSource,
            default => null,
        };
    }
}
