<p align="center"><img src="/art/logo.svg" alt="Laravel AI SDK Package Logo"></p>

<p align="center">
<a href="https://packagist.org/packages/evolution-cms/eai"><img src="https://img.shields.io/packagist/dt/evolution-cms/eai" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/evolution-cms/eai"><img src="https://img.shields.io/packagist/v/evolution-cms/eai" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/evolution-cms/eai"><img src="https://img.shields.io/packagist/l/evolution-cms/eai" alt="License"></a>
</p>

## Вступ

eAi — це інтеграційний пакет для Evolution CMS, який додає Laravel AI SDK у Evo‑стилі: publish конфігів, shims для відсутніх Illuminate\\Foundation класів і черги через sTask.

Всередині використовується офіційний Laravel AI SDK — уніфікований API для AI‑провайдерів (OpenAI, Anthropic, Gemini тощо), агентів, зображень, аудіо, embeddings і structured output.

## Інтеграція з Evolution CMS

### Вимоги
- Evolution CMS 3.5.2+
- PHP 8.4+
- Composer 2.2+

Опційно:
- sTask для асинхронних задач (поки `dev-main` із основного репозиторію)

### Встановлення в Evolution CMS
З директорії `core`:

```bash
cd core
php artisan package:installrequire evolution-cms/eai "*"
```

### Publish конфігів і stubs
```bash
php artisan vendor:publish --provider="EvolutionCMS\\eAi\\eAiServiceProvider" --tag=eai-config
php artisan vendor:publish --provider="EvolutionCMS\\eAi\\eAiServiceProvider" --tag=eai-ai-config
php artisan vendor:publish --provider="EvolutionCMS\\eAi\\eAiServiceProvider" --tag=eai-stubs
```

### Міграції
```bash
php artisan migrate
```

### Конфігурація
Основний конфіг:
- `core/custom/config/ai.php`

Налаштування інтеграції Evo:
- `core/custom/config/cms/settings/eAi.php`

Ключі середовища (приклади):
- `OPENAI_API_KEY`
- `ANTHROPIC_API_KEY`
- `GEMINI_API_KEY`

AI service account (опційно):
```
ai_actor_mode: service
ai_actor_email: ai@your-host
ai_actor_autocreate: true
ai_actor_block_login: true
ai_actor_role: AI
ai_actor_role_autocreate: true
```

AI actor — звичайний manager user з роллю **AI** (роль створюється автоматично). Username за замовчуванням дорівнює назві ролі (`AI`). Якщо вже існує користувач з роллю **AI**, він буде використаний; інакше eAi створить нового. Це повторює підхід sApi до API‑користувачів: стандартні користувачі + ролі/permissions, без додаткових колонок. Роль **AI** за замовчуванням read‑only; щоб AI могла зберігати/публікувати, підніміть роль до Publisher або видайте конкретні дозволи вручну.

Якщо потрібен доступ до інтерфейсів пакетів, видайте ролі **AI** permissions `stask` (Access sTask Interface) та/або `sapi` (Access sApi Interface). У sApi ці права згруповані в `sPackages`.

### MVP Smoke Test
1. Встановіть пакет і опублікуйте конфіги.
2. Додайте ключ провайдера (наприклад `OPENAI_API_KEY`) у `.env` або `core/custom/config/ai.php`.
3. Запустіть вбудований smoke‑тест:

```bash
php artisan ai:test
```

4. Або виконайте мінімальний виклик у `boot()` будь‑якого ServiceProvider (або тимчасового route/controller):

```php
use App\Ai\Agents\SupportAgent;

$agent = new SupportAgent();
$response = $agent->prompt('Hello from Evo');

echo $response->text;
```

### Використання
Базовий приклад:

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

### Черги
sTask — primary backend. `sync` — лише fallback для середовищ без sTask або для локальних smoke‑тестів. eAi не повторює Laravel Queue API, а лише забезпечує сумісність викликів SDK.

Щоб явно увімкнути sTask (default), у `core/custom/config/cms/settings/eAi.php`:

```php
'queue_driver' => 'stask',
```

Якщо sTask не встановлено, eAi переходить у `sync` та логгує warning.

### Навіщо потрібні shims
Evolution CMS не включає `illuminate/foundation`. Laravel AI SDK використовує `Illuminate\Foundation\Queue\Queueable` і `Illuminate\Foundation\Bus\PendingDispatch`, тому eAi додає мінімальні shim‑класи через `class_alias`, щоб уникнути `Class not found`.

У Evo також підміняємо `Laravel\\Ai\\AiServiceProvider` shim‑провайдером, щоб не викликати Laravel publishing/migrations, які очікують `$app->config` як Repository. Публікацію й міграції робить eAi нативно.
Shim‑alias підвантажуються через Composer `autoload.files`, щоб вони спрацювали до реєстрації провайдерів.

### Важливі правила безпеки
- Write‑дії виконуються тільки через manager ACL/ролі.
- Якщо контекст не дає визначити користувача, `conversation_user_id` fallback = `1` (admin). Це не дає додаткових прав, бо права визначає `actor_user_id` і його роль.
- Якщо AI actor увімкнений, `actor_user_id` завжди AI manager user (роль **AI**), незалежно від fallback.

## Документація

Документацію Laravel AI SDK дивіться на сайті Laravel.

## Ліцензія

MIT
