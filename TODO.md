# TODO

Roadmap de développement de Vigie API, découpée par paliers (voir `CLAUDE.md`).

## 1. Squelette

- [x] Init Laravel (`laravel new` ou `composer create-project`)
- [x] Installer des agents/commands pour les tests (PEST) a effectuer a chaque nouveau model/controller/feature et pour la review de code avant chaque push
- [x] Config `.env` (DB, etc.) + vérifier `.gitignore`
- [x] Migrations : `sources`, `items`, `summaries`, `digests`, `users` (+ préférences)
- [x] Modèles Eloquent + relations
- [x] Interface `SourceCollector` (contrat commun aux collecteurs)
- [x] `FetchRssSource` (premier collecteur RSS fonctionnel)
- [x] Commande artisan `vigie:fetch-source {source_id}`
- [x] Dédup des items (`external_id`)

## 2. Résumé IA

- [x] Intégration API OpenAI (client/service dédié)
- [x] Job `SummarizeItem` (prompt structuré → JSON `summary`/`tags`/`relevance_score`)
- [x] Troncature du `raw_content` avant envoi (limite tokens)
- [x] Passage des items `pending` → `summarized`
- [x] Tests avec mocks HTTP

## 3. Automatisation

- [x] Setup Redis + Horizon
- [x] Scheduler (jobs planifiés par type de source)
- [x] `FetchYoutubeSource` (YouTube Data API v3)
- [x] `FetchRedditSource` (API Reddit)
- [x] Gestion des rate limits par API externe

## 4. API pour le dashboard React

- [x] Setup Sanctum (Bearer token, pas de cookies SPA)
- [x] Routes API resource (`sources`, `items`, `summaries`, `digests`)
- [x] Form Requests pour validation
- [x] API Resources (`JsonResource`) pour les réponses
- [x] Endpoints préférences utilisateur (mots-clés, fréquence, sources activées)

## 5. Digest email

- [x] Mailable de digest
- [x] Planification d'envoi (scheduler)
- [x] Scoring/filtrage par `relevance_score` + préférences utilisateur

## 6. Optionnel

- [ ] `FetchWebSearchSource` (SerpAPI/Brave, 1x/jour)
- [ ] Déduplication sémantique (embeddings)
- [ ] Intégration X/Twitter
