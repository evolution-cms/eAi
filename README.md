<p align="center"><img src="/art/logo.svg" alt="Laravel AI SDK Package Logo"></p>

<p align="center">
<a href="https://packagist.org/packages/evolution-cms/eai"><img src="https://img.shields.io/packagist/dt/evolution-cms/eai" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/evolution-cms/eai"><img src="https://img.shields.io/packagist/v/evolution-cms/eai" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/evolution-cms/eai"><img src="https://img.shields.io/packagist/l/evolution-cms/eai" alt="License"></a>
</p>

## Introduction

eAi is the Evolution CMS integration package for the Laravel AI SDK. It provides an Evo‑native wrapper with config publishing, shims for missing Illuminate\Foundation classes, and an sTask‑first queue bridge.

Under the hood it uses the official Laravel AI SDK, which provides a unified API for AI providers (OpenAI, Anthropic, Gemini, etc.) to build agents, generate images/audio, embeddings, and structured output.

## Evolution CMS Integration

### Requirements
- Evolution CMS 3.5.2+
- PHP 8.4+
- Composer 2.2+

Optional:
- sTask for async tasks (currently `dev-main` from the main repo)

### Install in Evolution CMS
From your Evo `core` directory:

```bash
cd core
php artisan package:installrequire evolution-cms/eai "*"
```

### Publish config and stubs
```bash
php artisan vendor:publish --provider="EvolutionCMS\\eAi\\eAiServiceProvider" --tag=eai-config
php artisan vendor:publish --provider="EvolutionCMS\\eAi\\eAiServiceProvider" --tag=eai-ai-config
php artisan vendor:publish --provider="EvolutionCMS\\eAi\\eAiServiceProvider" --tag=eai-stubs
```

### Migrate
```bash
php artisan migrate
```

### Configuration
Main config:
- `core/custom/config/ai.php`

Evo integration settings:
- `core/custom/config/cms/settings/eAi.php`

Environment keys (examples):
- `OPENAI_API_KEY`
- `ANTHROPIC_API_KEY`
- `GEMINI_API_KEY`

AI service account (optional):
```
ai_actor_mode: service
ai_actor_email: ai@your-host
ai_actor_autocreate: true
ai_actor_block_login: true
ai_actor_role: AI
ai_actor_role_autocreate: true
```

AI actor is a normal manager user assigned to the **AI** role (auto‑created). Username defaults to the role name (`AI`). If a user with role **AI** already exists, it will be used; otherwise eAi creates one. This mirrors the sApi “API user” pattern: standard users + roles/permissions, no extra columns. The **AI** role is read‑only by default; to allow saving/publishing, elevate the role manually (e.g. Publisher) or grant specific rights.

If you need access to package interfaces, grant permissions `stask` (Access sTask Interface) and/or `sapi` (Access sApi Interface) to the **AI** role. In sApi these permissions are grouped under `sPackages`.

### MVP Smoke Test
1. Install the package and publish configs.
2. Set a provider API key (for example `OPENAI_API_KEY`) in `.env` or `core/custom/config/ai.php`.
3. Run the built-in smoke test:

```bash
php artisan ai:test
```

If you use local Ollama, run:

```bash
php artisan ai:test --provider=ollama
```

4. Or run a minimal call from any ServiceProvider `boot()` (or a temporary route/controller):

```php
use App\Ai\Agents\SupportAgent;

$agent = new SupportAgent();
$response = $agent->prompt('Hello from Evo');

echo $response->text;
```

### Usage
Basic example:

```php
use App\Ai\Agents\SupportAgent;

$agent = new SupportAgent();
$response = $agent->prompt('Hello from Evo');

echo $response->text;
```

Embeddings:

```php
use Laravel\Ai\Embeddings;

$embeddings = Embeddings::for(['Hello'])->generate();
```

Agents:

```php
use App\Ai\Agents\SupportAgent;

$agent = new SupportAgent();
$response = $agent->prompt('Summarize this page');
```

### Queues
sTask is the primary backend. `sync` is a fallback for environments without sTask or for local smoke tests. eAi does not implement Laravel Queue; it only provides SDK‑compatible dispatching.

To force sTask (default), set in `core/custom/config/cms/settings/eAi.php`:

```php
'queue_driver' => 'stask',
```

If sTask is not installed, eAi falls back to `sync` and logs a warning.

### Why shims exist
Evolution CMS does not include `illuminate/foundation`. Laravel AI SDK references `Illuminate\Foundation\Queue\Queueable` and `Illuminate\Foundation\Bus\PendingDispatch`, so eAi provides minimal shim classes via `class_alias` to avoid `Class not found` errors.

In Evo we also replace `Laravel\\Ai\\AiServiceProvider` with a shim provider to avoid Laravel publishing/migration hooks that rely on `$app->config` being a repository. eAi handles publishing and migrations natively.
The shim aliases are loaded via Composer `autoload.files` to ensure they run before provider registration.

### Security rules
- Write actions are only allowed via manager ACL/roles.
- If user context is missing, `conversation_user_id` falls back to `1` (admin). This does not grant extra rights because rights are defined by `actor_user_id`.
- If AI actor is enabled, `actor_user_id` is always the AI manager user (role **AI**), regardless of fallback.

## Documentation

Laravel AI SDK documentation is available on the Laravel website.

## License

MIT
