<?php

namespace App\Providers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        foreach (['created', 'updated', 'deleted'] as $action) {
            Event::listen("eloquent.{$action}: *", function (string $event, array $models) use ($action): void {
                $model = $models[0] ?? null;

                if (! $model instanceof Model || $model instanceof AuditLog || ! auth()->check()) {
                    return;
                }

                if (! Schema::hasTable('audit_logs')) {
                    return;
                }

                $hidden = ['password', 'remember_token'];
                $newValues = $action === 'deleted' ? null : $model->getChanges();
                $oldValues = $action === 'created' ? null : ($action === 'deleted' ? $model->getAttributes() : $model->getPrevious());

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => $action,
                    'auditable_type' => $model::class,
                    'auditable_id' => $model->getKey(),
                    'old_values' => $oldValues ? collect($oldValues)->except($hidden)->all() : null,
                    'new_values' => $newValues ? collect($newValues)->except($hidden)->all() : null,
                    'ip_address' => request()->ip(),
                ]);
            });
        }
    }
}
