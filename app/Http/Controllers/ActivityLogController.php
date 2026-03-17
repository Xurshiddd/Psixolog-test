<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = Activity::query()
            ->with('causer')
            ->latest()
            ->paginate(20)
            ->through(function (Activity $activity): array {
                return [
                    'id' => $activity->id,
                    'log_name' => $activity->log_name,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'subject_type' => class_basename((string) $activity->subject_type),
                    'subject_id' => $activity->subject_id,
                    'causer_name' => $activity->causer?->name ?? 'System',
                    'causer_id' => $activity->causer_id,
                    'created_at' => optional($activity->created_at)?->toDateTimeString(),
                    'properties' => $activity->properties,
                ];
            });

        return Inertia::render('Admin/ActivityLogs', [
            'logs' => $logs,
        ]);
    }
}
