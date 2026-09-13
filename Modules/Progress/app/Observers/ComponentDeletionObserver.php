<?php

namespace Modules\Progress\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Progress\Models\ComponentCompletion;

// ADR-015: operational counterpart to ADR-010 (which justified the missing
// DB-level FK). Deletes matching completions when a lesson, assignment or
// quiz disappears, so no orphan rows survive.
//
// Delete-path semantics (confirmed pre-Phase 7):
// - Hard delete (today's only path): deleted() purges — the component is gone.
// - forceDeleted(): purges as well — covers force-delete if SoftDeletes are
//   ever adopted; inert until then.
// - Soft-delete (trashed, if adopted later): completions are KEPT so a
//   restore resurrects progress intact. Do not "simplify" this later.
class ComponentDeletionObserver
{
    public function deleted(Model $model): void
    {
        if ($this->usesSoftDeletes($model) && ! $model->isForceDeleting()) {
            return;
        }

        $this->purgeFor($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->purgeFor($model);
    }

    protected function purgeFor(Model $model): void
    {
        ComponentCompletion::where('component_type', $this->typeFor($model))
            ->where('component_id', $model->getKey())
            ->delete();
    }

    protected function usesSoftDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    protected function typeFor(Model $model): string
    {
        return match (class_basename($model)) {
            'Lesson' => 'lesson',
            'Assignment' => 'assignment',
            'Quiz' => 'quiz',
            default => strtolower(class_basename($model)),
        };
    }
}
