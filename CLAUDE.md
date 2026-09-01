# CLAUDE.md

Ce fichier donne le contexte du projet à Claude Code pour travailler efficacement dessus.

## Vue d'ensemble

**Vigie API** est le backend Laravel d'un agent de veille technologique automatisé. Il collecte du contenu depuis plusieurs sources (RSS, YouTube, Reddit, recherche web), le résume via l'API OpenAI, et l'expose via une API REST à un frontend React séparé (`vigie-app`). Il envoie aussi des digests par email.

Nom complet du produit : **Vigie** — "ton agent IA qui surveille le web, YouTube et Reddit pour te livrer l'essentiel de la tech, résumé et trié."

## Stack

- **Framework** : Laravel (dernière version stable)
- **Auth** : Laravel Sanctum, scaffoldé via `laravel/breeze --api`
- **Queues** : Redis + Laravel Horizon
- **DB** : PostgreSQL (ou MySQL — à confirmer selon l'environnement)
- **Frontend** : projet React séparé (Vite), pas dans ce repo — consomme cette API en REST
- **IA** : API OpenAI (`gpt-4o-mini` par défaut, voir `config/llm.php`) pour les résumés

## Architecture

Flux général :

```
Sources → Collecteurs (jobs planifiés) → items (DB brute) → dédup
  → Job de résumé (API OpenAI) → summaries (DB enrichie)
  → API REST (dashboard React) + Mailable (digest email)
```

### Modèles principaux

- `Source` — une source à surveiller (`type`: rss/youtube/twitter/reddit/web_search, `url_or_query`, `config` json, `is_active`)
- `Item` — un contenu brut collecté depuis une source (`external_id` pour dédup, `raw_content`, `status`: pending/summarized/ignored/error)
- `Summary` — le résumé IA d'un item (`summary_text`, `tags` json, `relevance_score` 0-100, `model_used`)
- `Digest` — historique des envois (`item_ids`, `channel`: email/dashboard, `sent_at`)
- `User` / préférences — mots-clés suivis, fréquence de digest, sources activées

### Collecteurs

Chaque type de source implémente l'interface commune `SourceCollector` (`fetch(Source): Collection<Item>`), résolue par type via `CollectorResolver`. Elles héritent toutes de `AbstractSourceCollector` qui factorise la dédup + création des items (`persistNewItems`) :

- `FetchRssSource` — parsing RSS classique
- `FetchYoutubeSource` — YouTube Data API v3, via `playlistItems` sur la playlist "uploads" de la chaîne (1 unité de quota/appel, `url_or_query` = ID de chaîne au format `UC...`, validé au format sinon exception) plutôt que `search` (100 unités/appel) — voir Points d'attention
- `FetchRedditSource` — API Reddit officielle (OAuth2 `client_credentials`, `url_or_query` = nom du subreddit), token mis en cache ~55 min
- `FetchWebSearchSource` — API de recherche web (SerpAPI/Brave), moins fréquent (1x/jour) — pas encore implémenté (palier 6)
- Twitter/X volontairement pas encore implémenté (API officielle coûteuse) — à ajouter plus tard si besoin

Planification : le job `FetchSource` (queue Redis) exécute un collecteur pour une source et dispatch un `SummarizeItem` par nouvel item. La commande `vigie:dispatch-fetch-jobs` dispatch un `FetchSource` par source active ayant un collecteur, appelée 2x/jour à 8h et 18h (Europe/Paris) par le scheduler (`routes/console.php`, `withoutOverlapping()`) — pas de polling continu, cadence volontairement calée sur une lecture matin/soir plutôt que du temps réel.

### Résumé IA

Job `SummarizeItem` : prend un item `pending`, appelle l'API OpenAI (`app/Services/OpenAiSummarizer.php`, mode `response_format: json_object`) avec un prompt structuré demandant un JSON strict (`summary`, `tags`, `relevance_score`), stocke le résultat dans `Summary`, passe l'item en `summarized` (ou `error` si l'appel échoue). Traité en asynchrone via les Queues.

## Conventions de code

- Suivre les conventions Laravel standards (PSR-12, resource controllers, form requests pour la validation)
- Les collecteurs de sources vivent dans `app/Services/Collectors/`
- Les jobs de fond (collecte, résumé) dans `app/Jobs/`
- Utiliser les Form Requests pour toute validation d'entrée API
- Réponses API via API Resources (`JsonResource`) pour un format cohérent
- Tests : Pest — chaque nouveau model/controller/feature doit avoir des tests, et chaque collecteur / le pipeline de résumé doivent être testés avec des mocks HTTP (`Http::fake()`)
- Avant chaque push : la suite Pest doit passer (hook `pre-push`, voir ci-dessous) et une revue de code doit être faite (skill `/code-review`)

## Commandes utiles

```bash
# Lancer le serveur de dev
php artisan serve

# Lancer les workers de queue
php artisan horizon

# Lancer le scheduler en local (pour tester les jobs planifiés)
php artisan schedule:work

# Tests
php artisan test

# Tester un collecteur manuellement (synchrone, sans passer par la queue)
php artisan vigie:fetch-source {source_id}

# Dispatcher la collecte de toutes les sources actives (ce que fait le scheduler)
php artisan vigie:dispatch-fetch-jobs

# Migrations
php artisan migrate
```

## Hooks git

Un hook `pre-push` (`.githooks/pre-push`) fait échouer le push si la suite Pest ne passe pas. Il est versionné dans `.githooks/` (les hooks natifs `.git/hooks/` ne le sont pas) et doit être activé une fois par clone :

```bash
git config core.hooksPath .githooks
```

## Auth

- Sanctum en mode API (pas Blade, pas Inertia)
- Le frontend React est un client séparé : vérifier si l'auth se fait en cookies SPA (same-domain, `SANCTUM_STATEFUL_DOMAINS`) ou en tokens Bearer (domaines différents) selon l'environnement de déploiement

## Points d'attention

- Respecter les rate limits de chaque API externe (YouTube, Reddit, OpenAI) pour éviter les blocages : cadence de collecte 2x/jour (`withoutOverlapping`), endpoint YouTube économe en quota (`playlistItems` plutôt que `search`), token Reddit mis en cache
- Tronquer le `raw_content` avant envoi à l'API OpenAI si trop long (limite de tokens, voir `OpenAiSummarizer::MAX_CONTENT_LENGTH`)
- Le `relevance_score` sert à filtrer le bruit dans les digests — ne pas l'ignorer côté frontend/mailable
- Ne jamais committer de clés API (`.env` uniquement, vérifier `.gitignore`)

## Roadmap (paliers de développement)

1. Squelette : migrations + 1 collecteur RSS fonctionnel
2. Résumé IA : intégration API OpenAI + job de résumé
3. Automatisation : Scheduler + Queues/Horizon + collecteurs YouTube/Reddit
4. API complète pour le dashboard React (Sanctum)
5. Digest email (Mailable + planification + scoring par préférences utilisateur)
6. Optionnel : recherche web générique, déduplication sémantique (embeddings), X/Twitter
