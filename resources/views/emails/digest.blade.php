<x-mail::message>
# Ta veille tech

{{ $items->count() }} article(s) sélectionné(s) pour toi.

@foreach ($items as $item)
### {{ $item->title }}

{{ $item->summary->summary_text }}

**Pertinence :** {{ $item->summary->relevance_score }}/100
@if ($item->summary->tags)
**Tags :** {{ implode(', ', $item->summary->tags) }}
@endif

@if ($item->url)
<x-mail::button :url="$item->url">
Lire l'article
</x-mail::button>
@endif

---
@endforeach

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
