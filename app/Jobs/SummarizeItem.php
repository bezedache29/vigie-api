<?php

namespace App\Jobs;

use App\Models\Item;
use App\Models\Summary;
use App\Services\OpenAiSummarizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SummarizeItem implements ShouldQueue
{
    use Queueable;

    public function __construct(public Item $item)
    {
    }

    public function handle(OpenAiSummarizer $summarizer): void
    {
        try {
            $result = $summarizer->summarize($this->item);
        } catch (Throwable $e) {
            $this->item->update(['status' => 'error']);
            throw $e;
        }

        Summary::updateOrCreate(
            ['item_id' => $this->item->id],
            [...$result, 'model_used' => config('llm.openai.model')],
        );

        $this->item->update(['status' => 'summarized']);
    }
}
