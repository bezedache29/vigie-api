<?php

namespace App\Services\Collectors;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * `Source::url_or_query` doit contenir l'ID de la chaîne YouTube à surveiller
 * (ex: "UC_x5XG1OV2P6uZZ5FSM9Ttw").
 *
 * Utilise l'endpoint `playlistItems` (1 unité de quota par appel) plutôt que
 * `search` (100 unités par appel) pour rester dans le quota gratuit de
 * l'API (10 000 unités/jour) même avec un polling fréquent. La playlist des
 * uploads d'une chaîne a toujours l'ID de la chaîne avec le préfixe "UC"
 * remplacé par "UU".
 */
class FetchYoutubeSource extends AbstractSourceCollector
{
    public function fetch(Source $source): Collection
    {
        if (! preg_match('/^UC[\w-]{22}$/', $source->url_or_query)) {
            throw new RuntimeException(
                "Source [{$source->id}] : url_or_query doit être un ID de chaîne YouTube ".
                "au format \"UC...\" (ex: UC_x5XG1OV2P6uZZ5FSM9Ttw), reçu [{$source->url_or_query}]."
            );
        }

        $uploadsPlaylistId = 'UU'.substr($source->url_or_query, 2);

        $response = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', [
            'key' => config('services.youtube.api_key'),
            'playlistId' => $uploadsPlaylistId,
            'part' => 'snippet',
            'maxResults' => 25,
        ]);

        $response->throw();

        $attributesList = collect($response->json('items', []))
            ->filter(fn (array $entry) => ! empty($entry['snippet']['resourceId']['videoId']))
            ->map(fn (array $entry) => $this->toAttributes($entry));

        return $this->persistNewItems($source, $attributesList);
    }

    private function toAttributes(array $entry): array
    {
        $snippet = $entry['snippet'];
        $videoId = $snippet['resourceId']['videoId'];

        return [
            'external_id' => $videoId,
            'title' => $snippet['title'] ?? null,
            'url' => "https://www.youtube.com/watch?v={$videoId}",
            'raw_content' => $snippet['description'] ?? null,
            'published_at' => isset($snippet['publishedAt'])
                ? date('Y-m-d H:i:s', strtotime($snippet['publishedAt']))
                : null,
        ];
    }
}
