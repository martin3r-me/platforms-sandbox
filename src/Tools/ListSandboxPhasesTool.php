<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Sandbox\Enums\SandboxPhaseNumber;
use Platform\Sandbox\Models\SandboxPhase;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class ListSandboxPhasesTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.phases.GET'; }

    public function getDescription(): string
    {
        return 'GET /sandbox/phases - Listet die 8 Kotter-Phasen eines Sandbox-Projekts mit Status, Verantwortlichen und Notizen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'    => ['type' => 'integer'],
                'project_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH: Sandbox-Projekt-ID.'],
                'status'     => ['type' => 'string', 'description' => 'Optional: not_started | in_progress | completed | blocked.'],
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

            $projectId = $arguments['project_id'] ?? null;
            if (! $projectId) return ToolResult::error('VALIDATION_ERROR', 'project_id ist erforderlich.');

            $project = SandboxProject::find((int) $projectId);
            if (! $project || (int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('NOT_FOUND', 'Sandbox-Projekt nicht gefunden.');
            }

            $q = $project->phases()->withCount('actions')->orderBy('phase_number');
            if (! empty($arguments['status'])) {
                $q->where('status', (string) $arguments['status']);
            }

            $phases = $q->get()->map(fn (SandboxPhase $p) => [
                'id'           => $p->id,
                'uuid'         => $p->uuid,
                'phase_number' => $p->phase_number,
                'phase_label'  => $p->phase_number->label(),
                'phase_description' => $p->phase_number->description(),
                'status'       => $p->status,
                'responsible'  => $p->responsible,
                'notes'        => $p->notes,
                'evidence'     => $p->evidence,
                'actions_count' => $p->actions_count,
                'started_at'   => $p->started_at?->toIso8601String(),
                'completed_at' => $p->completed_at?->toIso8601String(),
            ])->values()->toArray();

            return ToolResult::success([
                'data'       => $phases,
                'project_id' => $project->id,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Phasen: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category'      => 'read',
            'tags'          => ['sandbox', 'phases', 'lookup'],
            'read_only'     => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level'    => 'safe',
            'idempotent'    => true,
        ];
    }
}
