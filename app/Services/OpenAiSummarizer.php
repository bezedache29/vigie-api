<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiSummarizer
{
    private const MAX_CONTENT_LENGTH = 8000;

    /**
     * @return array{summary_text: string, tags: array<int, string>, relevance_score: int}
     */
    public function summarize(Item $item): array
    {
        $response = Http::withToken(config('llm.openai.api_key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('llm.openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $this->userMessage($item)],
                ],
            ]);

        $response->throw();

        $content = $response->json('choices.0.message.content')
            ?? throw new RuntimeException('OpenAI : contenu vide');

        $decoded = json_decode($content, true);

        if (
            ! is_array($decoded)
            || empty($decoded['summary'])
            || ! is_string($decoded['summary'])
            || ! isset($decoded['relevance_score'])
            || ! is_numeric($decoded['relevance_score'])
        ) {
            throw new RuntimeException("Réponse OpenAI invalide : {$content}");
        }

        return [
            'summary_text' => $decoded['summary'],
            'tags' => array_values((array) ($decoded['tags'] ?? [])),
            'relevance_score' => max(0, min(100, (int) $decoded['relevance_score'])),
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Tu es un assistant de veille technologique. Tu résumes un contenu (article, vidéo, post) pour un développeur qui veut suivre l'actualité tech sans perdre de temps.

Réponds UNIQUEMENT en JSON valide, sans markdown, avec ce schéma exact :
{
  "summary": "résumé en 2-4 phrases, en français, factuel",
  "tags": ["tag1", "tag2", ...],
  "relevance_score": <entier de 0 à 100>
}

Le relevance_score évalue la pertinence du contenu pour un développeur généraliste suivant l'actu tech (nouveauté, impact, qualité de la source). 0 = bruit / hors-sujet, 100 = incontournable.
PROMPT;
    }

    private function userMessage(Item $item): string
    {
        $content = mb_substr((string) $item->raw_content, 0, self::MAX_CONTENT_LENGTH);

        return "Titre : {$item->title}\nURL : {$item->url}\nContenu :\n{$content}";
    }
}
