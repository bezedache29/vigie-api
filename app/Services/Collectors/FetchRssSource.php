<?php

namespace App\Services\Collectors;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class FetchRssSource extends AbstractSourceCollector
{
    public function fetch(Source $source): Collection
    {
        $response = Http::get($source->url_or_query);
        $response->throw();

        $xml = simplexml_load_string($response->body());

        if ($xml === false || ! isset($xml->channel->item)) {
            throw new RuntimeException("Flux RSS invalide pour la source [{$source->id}].");
        }

        $attributesList = collect(iterator_to_array($xml->channel->item, false))
            ->map(fn (SimpleXMLElement $entry) => $this->toAttributes($entry));

        return $this->persistNewItems($source, $attributesList);
    }

    private function toAttributes(SimpleXMLElement $entry): array
    {
        $link = (string) $entry->link;
        $guid = (string) $entry->guid ?: $link;
        $pubDate = (string) $entry->pubDate;

        return [
            'external_id' => $guid,
            'title' => (string) $entry->title ?: null,
            'url' => $link ?: null,
            'raw_content' => (string) $entry->description ?: null,
            'published_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : null,
        ];
    }
}
