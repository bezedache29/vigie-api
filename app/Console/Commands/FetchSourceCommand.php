<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Services\Collectors\CollectorResolver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vigie:fetch-source {source_id : ID de la source à collecter}')]
#[Description('Déclenche manuellement la collecte d\'une source')]
class FetchSourceCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CollectorResolver $resolver): int
    {
        $source = Source::find($this->argument('source_id'));

        if (! $source) {
            $this->error("Source [{$this->argument('source_id')}] introuvable.");

            return self::FAILURE;
        }

        $collector = $resolver->resolve($source);

        if (! $collector) {
            $this->error("Aucun collecteur disponible pour le type [{$source->type}].");

            return self::FAILURE;
        }

        $items = $collector->fetch($source);

        $this->info("{$items->count()} nouvel(aux) item(s) collecté(s) pour [{$source->name}].");

        return self::SUCCESS;
    }
}
