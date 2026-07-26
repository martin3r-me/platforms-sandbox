<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Sandbox\Enums\SandboxPhaseNumber;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class GetSandboxBoardTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.board.GET'; }

    public function getDescription(): string
    {
        return 'GET /sandbox/board - Board-View: Alle 8 Kotter-Phasen mit Status, Actions und Details in einem Aufruf.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'    => ['type' => 'integer'],
                'project_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
            ],
            'required' => ['project_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $project = SandboxProject::with(['phases.actions', 'phases.logs'])
                ->find((int) ($arguments['project_id'] ?? 0));
            if (! $project || (int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('NOT_FOUND', 'Sandbox-Projekt nicht gefunden.');
            }

            $board = $project->phases->sortBy('phase_number')->map(function ($phase) {
                return [
                    'phase_id'     => $phase->id,
                    'phase_number' => $phase->phase_number->value,
                    'label'        => $phase->phase_number->label(),
                    'short_label'  => $phase->phase_number->shortLabel(),
                    'description'  => $phase->phase_number->description(),
                    'status'       => $phase->status->value ?? $phase->status,
                    'status_label' => $phase->status->label(),
                    'responsible'  => $phase->responsible,
                    'notes'        => $phase->notes,
                    'evidence'     => $phase->evidence,
                    'started_at'   => $phase->started_at?->toIso8601String(),
                    'completed_at' => $phase->completed_at?->toIso8601String(),
                    'actions'      => $phase->actions->map(fn ($a) => [
                        'id'          => $a->id,
                        'title'       => $a->title,
                        'status'      => $a->status->value ?? $a->status,
                        'responsible' => $a->responsible,
                        'due_date'    => $a->due_date?->toDateString(),
                    ])->values()->toArray(),
                    'log_count'    => $phase->logs->count(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'project_id'   => $project->id,
                'project_name' => $project->name,
                'status'       => $project->status,
                'progress'     => $project->progress,
                'board'        => $board,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read', 'tags' => ['sandbox', 'board', 'analytics'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'safe', 'idempotent' => true,
        ];
    }
}
