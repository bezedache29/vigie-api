<?php

namespace App\Services\Collectors;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * `Source::url_or_query` doit contenir le nom du subreddit à surveiller
 * (ex: "programming", avec ou sans le préfixe "r/").
 */
class FetchRedditSource extends AbstractSourceCollector
{
    public function fetch(Source $source): Collection
    {
        $subreddit = str_starts_with($source->url_or_query, 'r/')
            ? substr($source->url_or_query, 2)
            : $source->url_or_query;

        $response = Http::withToken($this->accessToken())
            ->withHeaders(['User-Agent' => config('services.reddit.user_agent')])
            ->get("https://oauth.reddit.com/r/{$subreddit}/new", ['limit' => 25]);

        $response->throw();

        $attributesList = collect($response->json('data.children', []))
            ->map(fn (array $entry) => $this->toAttributes($entry['data']));

        return $this->persistNewItems($source, $attributesList);
    }

    private function toAttributes(array $post): array
    {
        return [
            'external_id' => $post['name'],
            'title' => $post['title'] ?? null,
            'url' => isset($post['permalink']) ? "https://reddit.com{$post['permalink']}" : null,
            'raw_content' => $post['selftext'] ?? null,
            'published_at' => isset($post['created_utc'])
                ? date('Y-m-d H:i:s', (int) $post['created_utc'])
                : null,
        ];
    }

    private function accessToken(): string
    {
        return Cache::remember('reddit_access_token', 3300, function () {
            $response = Http::asForm()
                ->withHeaders(['User-Agent' => config('services.reddit.user_agent')])
                ->withBasicAuth(
                    config('services.reddit.client_id'),
                    config('services.reddit.client_secret'),
                )
                ->post('https://www.reddit.com/api/v1/access_token', [
                    'grant_type' => 'client_credentials',
                ]);

            $response->throw();

            return $response->json('access_token');
        });
    }
}
