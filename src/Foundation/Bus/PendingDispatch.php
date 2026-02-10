<?php

namespace EvolutionCMS\eAi\Foundation\Bus;

class PendingDispatch
{
    protected $job;
    protected bool $dispatched = false;

    public function __construct($job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function onConnection($connection): self
    {
        if (method_exists($this->job, 'onConnection')) {
            $this->job->onConnection($connection);
        }

        return $this;
    }

    public function onQueue($queue): self
    {
        if (method_exists($this->job, 'onQueue')) {
            $this->job->onQueue($queue);
        }

        return $this;
    }

    public function delay($delay): self
    {
        return $this;
    }

    public function chain($chain): self
    {
        return $this;
    }

    public function afterCommit(): self
    {
        return $this;
    }

    public function beforeCommit(): self
    {
        return $this;
    }

    public function dispatchSync()
    {
        $this->dispatched = true;

        if (method_exists($this->job, 'handle')) {
            return $this->job->handle();
        }

        return null;
    }

    public function dispatch(): void
    {
        if ($this->dispatched) {
            return;
        }

        $this->dispatched = true;

        if ($this->shouldUseSTask()) {
            $this->dispatchToSTask();
            return;
        }

        $this->dispatchSync();
    }

    protected function shouldUseSTask(): bool
    {
        $driver = 'stask';
        if (function_exists('config')) {
            $driver = (string)config('cms.settings.eAi.queue_driver', 'stask');
        }

        return $driver === 'stask' && class_exists(\Seiger\sTask\Facades\sTask::class);
    }

    protected function dispatchToSTask(): void
    {
        if (!class_exists(\Seiger\sTask\Facades\sTask::class)) {
            $this->dispatchSync();
            return;
        }

        $ids = function_exists('eAi_resolve_ids') ? eAi_resolve_ids() : [];
        $actorUserId = (int)($ids['actor_user_id'] ?? 1);
        $conversationUserId = (int)($ids['conversation_user_id'] ?? 1);
        $initiatedByUserId = $ids['initiated_by_user_id'] ?? null;
        $context = (string)($ids['context'] ?? 'cli');

        $payload = [
            'job_class' => is_object($this->job) ? get_class($this->job) : null,
            'job_payload' => base64_encode(serialize($this->job)),
            'actor_user_id' => $actorUserId,
            'conversation_user_id' => $conversationUserId,
            'initiated_by_user_id' => $initiatedByUserId,
            'context' => $context,
            'attempts' => 0,
            'max_attempts' => 1,
        ];

        try {
            \Seiger\sTask\Facades\sTask::create(
                identifier: 'eai',
                action: 'dispatch',
                data: $payload,
                priority: 'normal',
                userId: $actorUserId
            );
        } catch (\Throwable $e) {
            if (function_exists('eAi_log')) {
                eAi_log('eAi: sTask dispatch failed: ' . $e->getMessage(), 2);
            }
            $this->dispatchSync();
        }
    }

    public function __destruct()
    {
        $this->dispatch();
    }
}
