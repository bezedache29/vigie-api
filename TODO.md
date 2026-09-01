# TODO

Roadmap de développement de Vigie API, découpée par paliers (voir `CLAUDE.md`).

## 1. Squelette
- [ ] Init Laravel (`laravel new` ou `composer create-project`)
- [ ] Config `.env` (DB, etc.) + vérifier `.gitignore`
- [ ] Migrations : `sources`, `items`, `summaries`, `digests`, `users` (+ préférences)
- [ ] Modèles Eloquent + relations
- [ ] Interface `SourceCollector` (contrat commun aux collecteurs)
- [ ] `FetchRssSource` (premier collecteur RSS fonctionnel)
- [ ] Commande artisan `vigie:fetch-source {source_id}`
- [ ] Dédup des items (`external_id`)

## 2. Résumé IA
- [ ] Intégration API Claude (client/service dédié)
- [ ] Job `SummarizeItem` (prompt structuré → JSON `summary`/`tags`/`relevance_score`)
- [ ] Troncature du `raw_content` avant envoi (limite tokens)
- [ ] Passage des items `pending` → `summarized`
- [ ] Tests avec mocks HTTP

## 3. Automatisation
- [ ] Setup Redis + Horizon
- [ ] Scheduler (jobs planifiés par type de source)
- [ ] `FetchYoutubeSource` (YouTube Data API v3)
- [ ] `FetchRedditSource` (API Reddit)
- [ ] Gestion des rate limits par API externe

## 4. API pour le dashboard React
- [ ] Setup Sanctum (mode API, décider cookies SPA vs Bearer selon déploiement)
- [ ] Routes API resource (`sources`, `items`, `summaries`, `digests`)
- [ ] Form Requests pour validation
- [ ] API Resources (`JsonResource`) pour les réponses
- [ ] Endpoints préférences utilisateur (mots-clés, fréquence, sources activées)

## 5. Digest email
- [ ] Mailable de digest
- [ ] Planification d'envoi (scheduler)
- [ ] Scoring/filtrage par `relevance_score` + préférences utilisateur

## 6. Optionnel
- [ ] `FetchWebSearchSource` (SerpAPI/Brave, 1x/jour)
- [ ] Déduplication sémantique (embeddings)
- [ ] Intégration X/Twitter
