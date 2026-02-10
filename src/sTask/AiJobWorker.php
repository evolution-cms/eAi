<?php

namespace EvolutionCMS\eAi\sTask;

use Seiger\sTask\Models\sTaskModel;
use Seiger\sTask\Workers\BaseWorker;

class AiJobWorker extends BaseWorker
{
    public function identifier(): string
    {
        return 'eai';
    }

    public function scope(): string
    {
        return 'system';
    }

    public function icon(): string
    {
        return '<i class="fa fa-robot"></i>';
    }

    public function title(): string
    {
        return 'eAi Jobs';
    }

    public function description(): string
    {
        return 'Dispatches eAi queued jobs via sTask';
    }

    public function taskDispatch(sTaskModel $task, array $options = []): void
    {
        try {
            $payload = $options['job_payload'] ?? '';
            $decoded = base64_decode((string)$payload, true);
            $job = $decoded ? @unserialize($decoded) : null;

            if (is_object($job) && method_exists($job, 'handle')) {
                $job->handle();
                $this->markFinished($task, 'ok', 'Job completed');
                return;
            }

            $this->markFailed($task, 'Invalid job payload');
        } catch (\Throwable $e) {
            $this->markFailed($task, $e->getMessage());
        }
    }
}
