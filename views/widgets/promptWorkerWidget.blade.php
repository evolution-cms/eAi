@php $task = Seiger\sTask\Models\sTaskModel::byIdentifier($identifier ?? '')->incomplete()->orderByDesc('updated_at')->first(); @endphp

<div id="{{$identifier ?? ''}}Widget">
    <div style="padding: 0.875rem 1rem;">
        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="{{$identifier ?? ''}}Prompt" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                Prompt:
            </label>
            <textarea
                id="{{$identifier ?? ''}}Prompt"
                class="form-control"
                rows="4"
                placeholder="Write a short prompt..."
                style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"
            ></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="{{$identifier ?? ''}}Provider" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                Provider (optional):
            </label>
            <input
                type="text"
                id="{{$identifier ?? ''}}Provider"
                class="form-control"
                placeholder="openai"
                style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"
            >
        </div>

        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="{{$identifier ?? ''}}Model" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                Model (optional):
            </label>
            <input
                type="text"
                id="{{$identifier ?? ''}}Model"
                class="form-control"
                placeholder="gpt-4o-mini"
                style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"
            >
        </div>

        <span id="{{$identifier ?? ''}}Run" class="btn btn-primary">
            <i class="fas fa-play" style="font-size: 0.75rem;"></i>
            Run
        </span>
    </div>
</div>

<div id="{{$identifier ?? ''}}Progress" class="widget-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
    <span class="widget-progress__bar"></span>
    <span class="widget-progress__cap"></span>
    <span class="widget-progress__meta">
        <b class="widget-progress__pct">0%</b>
        <i class="widget-progress__eta">—</i>
    </span>
</div>

<div id="{{$identifier ?? ''}}Log" class="widget-log" aria-live="polite">
    <div class="line-info">{{$description ?? ''}}</div>
    @if($task && (int)$task->id > 0)
        <div class="line-info">⏳ Task is running...</div>
    @else
        <div class="line-info">💡 Click button above to start the task</div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($task && (int)$task->id > 0)
        let root = document.getElementById('{{$identifier ?? ''}}Log');
        widgetClearLog(root);
        widgetLogLine(root, '_Task is running..._');
        widgetWatcher(root, "{{route('sTask.task.progress', ['id' => $task->id])}}", '{{$identifier ?? ''}}');
        @endif

        document.getElementById('{{$identifier ?? ''}}Run')?.addEventListener('click', async function() {
            let root = document.getElementById('{{$identifier ?? ''}}Log');
            let promptInput = document.getElementById('{{$identifier ?? ''}}Prompt');
            let providerInput = document.getElementById('{{$identifier ?? ''}}Provider');
            let modelInput = document.getElementById('{{$identifier ?? ''}}Model');

            let prompt = promptInput?.value?.trim() || '';
            let provider = providerInput?.value?.trim() || '';
            let model = modelInput?.value?.trim() || '';

            if (!prompt) {
                widgetLogLine(root, '**Prompt is required.**', 'error');
                return;
            }

            widgetClearLog(root);
            widgetLogLine(root, '**Starting task...** _Please wait_');

            disableButtons('{{$identifier ?? ''}}', null, '{{$identifier ?? ''}}Run');

            let options = { prompt: prompt };
            if (provider) options.provider = provider;
            if (model) options.model = model;

            let result = await callApi("{{route('sTask.worker.task.run', ['identifier' => $identifier ?? '', 'action' => 'prompt'])}}", {
                options: options
            });

            if (result.success == true) {
                widgetProgressBar('{{$identifier ?? ''}}', 0);
                widgetWatcher(root, "{{route('sTask.task.progress', ['id' => '__ID__'])}}".replace('__ID__', result?.id||0), '{{$identifier ?? ''}}');
            } else {
                widgetLogLine(root, '**Error starting task.** _' + (result?.message || '') + '_', 'error');
                enableButtons('{{$identifier ?? ''}}');
            }
        });
    });
</script>
