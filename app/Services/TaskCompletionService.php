<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskCompletionService
{
    /**
     * Mark a task as completed (or revert to pending).
     */
    public function toggle(Task $task, User $user): Task
    {
        return DB::transaction(function () use ($task, $user) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);

            if ($locked->status === TaskStatus::Completed) {
                $locked->update([
                    'status' => TaskStatus::Pending,
                    'completed_at' => null,
                    'completed_by' => null,
                ]);
            } else {
                if ($locked->status->isFinal()) {
                    throw new \DomainException("La tarea «{$locked->title}» no se puede marcar como completada.");
                }
                $locked->update([
                    'status' => TaskStatus::Completed,
                    'completed_at' => now(),
                    'completed_by' => $user->id,
                ]);
            }

            return $locked->fresh();
        });
    }
}
