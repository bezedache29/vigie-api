<?php

namespace App\Jobs;

use App\Models\Source;
use App\Services\Collectors\CollectorResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchSource implements ShouldQueue
{
    use Queueable;

    public function __construct(public Source $source)
    {
    }

    public function handle(CollectorResolver $resolver): void
    {
        $collector = $resolver->resolve($this->source);

        if (! $collector) {
            return;
        }

        $items = $collector->fetch($this->source);

        $items->each(fn ($item) => SummarizeItem::dispatch($item));
    }
}
