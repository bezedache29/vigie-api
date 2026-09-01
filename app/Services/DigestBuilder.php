<?php

namespace App\Services;

use App\Models\Digest;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DigestBuilder
{
    public function isDue(User $user): bool
    {
        $lastDigest = $this->lastDigest($user);

        if (! $lastDigest) {
            return true;
        }

        return match ($this->frequency($user)) {
            'weekly' => $lastDigest->sent_at->lt(now()->subWeek()),
            default => ! $lastDigest->sent_at->isToday(),
        };
    }

    /**
     * Items pertinents pour le digest de cet utilisateur depuis son dernier
     * envoi : résumés, au-dessus du seuil de bruit, sur ses sources
     * activées (toutes si aucune sélection) et matchant ses mots-clés
     * (tous si aucun mot-clé), triés par pertinence décroissante.
     *
     * @return Collection<int, Item>
     */
    public function eligibleItems(User $user): Collection
    {
        $lastDigest = $this->lastDigest($user);
        $since = $lastDigest?->sent_at ?? $this->defaultLookback($user);

        $query = Item::query()
            ->with('summary')
            ->where('status', 'summarized')
            ->where('created_at', '>=', $since)
            ->whereHas(
                'summary',
                fn ($q) => $q->where('relevance_score', '>=', config('vigie.digest.min_relevance_score')),
            );

        $sourceIds = $user->sources()->pluck('sources.id');
        if ($sourceIds->isNotEmpty()) {
            $query->whereIn('source_id', $sourceIds);
        }

        $items = $query->get();

        $keywords = collect($user->preference?->keywords ?? []);
        if ($keywords->isNotEmpty()) {
            $items = $items->filter(fn (Item $item) => $this->matchesKeywords($item, $keywords));
        }

        return $items->sortByDesc(fn (Item $item) => $item->summary->relevance_score)->values();
    }

    private function lastDigest(User $user): ?Digest
    {
        return Digest::where('user_id', $user->id)
            ->where('channel', 'email')
            ->latest('sent_at')
            ->first();
    }

    private function frequency(User $user): string
    {
        return $user->preference?->digest_frequency ?? 'daily';
    }

    private function defaultLookback(User $user): Carbon
    {
        return match ($this->frequency($user)) {
            'weekly' => now()->subWeek(),
            default => now()->subDay(),
        };
    }

    private function matchesKeywords(Item $item, Collection $keywords): bool
    {
        $haystack = Str::lower(implode(' ', [
            $item->title,
            $item->summary?->summary_text,
            implode(' ', $item->summary?->tags ?? []),
        ]));

        return $keywords->contains(fn (string $keyword) => str_contains($haystack, Str::lower($keyword)));
    }
}
