<?php

namespace Platform\Sandbox\Organization;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;

class SandboxEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['sandbox_project'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'sandbox_project' => [
                'label' => 'Sandbox-Projekte',
                'singular' => 'Sandbox-Projekt',
                'icon' => 'arrow-path',
                'route' => 'sandbox.projects.show',
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        $query->withCount('phases')
            ->withCount(['phases as phases_completed_count' => fn ($q) => $q->where('status', 'completed')])
            ->withCount(['phases as phases_blocked_count' => fn ($q) => $q->where('status', 'blocked')])
            ->withCount('actions')
            ->withCount(['actions as actions_open_count' => fn ($q) => $q->where('status', 'open')->orWhere('status', 'in_progress')])
            ->withCount(['actions as actions_done_count' => fn ($q) => $q->where('status', 'done')]);
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [
            'code' => $model->code,
            'status' => $model->status?->value ?? null,
            'progress' => $model->progress ?? 0,
            'target_date' => $model->plannedEnd()?->format('d.m.Y'),
            'phases_count' => $model->phases_count ?? 0,
            'actions_count' => $model->actions_count ?? 0,
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'code' => ['type' => 'text', 'label' => 'Code'],
            'status' => ['type' => 'badge', 'label' => 'Status'],
            'progress' => ['type' => 'percentage', 'label' => 'Fortschritt'],
            'target_date' => ['type' => 'text', 'label' => 'Zieldatum'],
            'phases_count' => ['type' => 'number', 'label' => 'Phasen'],
            'actions_count' => ['type' => 'number', 'label' => 'Massnahmen'],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        // Project statuses
        $projects = DB::table('sandbox_projects')
            ->whereIn('id', $allIds)
            ->whereNull('deleted_at')
            ->select('id', 'status')
            ->get()
            ->keyBy('id');

        // Phase stats per project
        $phaseStats = DB::table('sandbox_phases')
            ->whereIn('sandbox_project_id', $allIds)
            ->select(
                'sandbox_project_id',
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked"),
            )
            ->groupBy('sandbox_project_id')
            ->get()
            ->keyBy('sandbox_project_id');

        // Action stats per project
        $actionStats = DB::table('sandbox_actions')
            ->whereIn('sandbox_project_id', $allIds)
            ->whereNull('deleted_at')
            ->select(
                'sandbox_project_id',
                DB::raw("SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as actions_open"),
                DB::raw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as actions_done"),
            )
            ->groupBy('sandbox_project_id')
            ->get()
            ->keyBy('sandbox_project_id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = 0;
            $active = 0;
            $progressSum = 0;
            $progressCount = 0;
            $actionsOpen = 0;
            $actionsDone = 0;
            $phasesBlocked = 0;

            foreach ($ids as $id) {
                $project = $projects[$id] ?? null;
                if (! $project) {
                    continue;
                }

                $total++;
                if ($project->status === 'active') {
                    $active++;
                }

                $phases = $phaseStats[$id] ?? null;
                if ($phases && $phases->total > 0) {
                    $progressSum += $phases->completed / $phases->total;
                    $progressCount++;
                    $phasesBlocked += (int) $phases->blocked;
                }

                $actions = $actionStats[$id] ?? null;
                if ($actions) {
                    $actionsOpen += (int) $actions->actions_open;
                    $actionsDone += (int) $actions->actions_done;
                }
            }

            $result[$entityId] = [
                'sandbox_projects_total' => $total,
                'sandbox_projects_active' => $active,
                'sandbox_progress_avg' => $progressCount > 0
                    ? round($progressSum / $progressCount, 2)
                    : 0,
                'sandbox_actions_open' => $actionsOpen,
                'sandbox_actions_done' => $actionsDone,
                'sandbox_phases_blocked' => $phasesBlocked,
            ];
        }

        return $result;
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }

    public function metricDefinitions(): array
    {
        return [
            'sandbox_projects_total' => [
                'label' => 'Sandbox-Projekte (gesamt)',
                'group' => 'sandbox',
                'direction' => 'neutral',
                'unit' => 'count',
                'dimension' => 'complexity',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sandbox_projects_active' => [
                'label' => 'Sandbox-Projekte (aktiv)',
                'group' => 'sandbox',
                'direction' => 'neutral',
                'unit' => 'count',
                'pair' => 'sandbox_projects_total',
                'dimension' => 'energy',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sandbox_progress_avg' => [
                'label' => 'Ø Sandbox-Fortschritt',
                'group' => 'sandbox',
                'direction' => 'up',
                'unit' => 'ratio',
                'dimension' => 'throughput',
                'type' => 'modulator',
                'aggregation_mode' => 'rolled_up',
                'roll_up_function' => 'avg',
            ],
            'sandbox_actions_open' => [
                'label' => 'Sandbox-Massnahmen (offen)',
                'group' => 'sandbox',
                'direction' => 'down',
                'unit' => 'count',
                'dimension' => 'energy',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'sandbox_actions_done' => [
                'label' => 'Sandbox-Massnahmen (erledigt)',
                'group' => 'sandbox',
                'direction' => 'up',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'sandbox_phases_blocked' => [
                'label' => 'Sandbox-Phasen (blockiert)',
                'group' => 'sandbox',
                'direction' => 'down',
                'unit' => 'count',
                'dimension' => 'quality',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
        ];
    }
}
