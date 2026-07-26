<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Sandbox\Models\SandboxAction;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class CreateSandboxActionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.actions.POST'; }

    public function getDescription(): string
    {
        return 'POST /sandbox/actions - Erstellt eine Massnahme für ein Sandbox-Projekt, optional einer Phase zugeordnet.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'     => ['type' => 'integer'],
                'project_id'  => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'title'       => ['type' => 'string', 'description' => 'ERFORDERLICH.'],
                'description' => ['type' => 'string'],
                'status'      => ['type' => 'string', 'description' => 'open | in_progress | done | cancelled. Default: open.'],
                'due_date'    => ['type' => 'string', 'description' => 'Optional: YYYY-MM-DD.'],
                'responsible' => ['type' => 'string'],
                'phase_id'    => ['type' => 'integer', 'description' => 'Optional: Zuordnung zu einer Phase.'],
                'metadata'    => ['type' => 'object'],
            ],
            'required' => ['project_id', 'title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $project = SandboxProject::find((int) ($arguments['project_id'] ?? 0));
            if (! $project || (int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('NOT_FOUND', 'Sandbox-Projekt nicht gefunden.');
            }

            $title = trim((string) ($arguments['title'] ?? ''));
            if ($title === '') return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');

            $action = SandboxAction::create([
                'team_id'            => $rootTeamId,
                'user_id'            => $context->user?->id,
                'sandbox_project_id'  => $project->id,
                'sandbox_phase_id'    => ! empty($arguments['phase_id']) ? (int) $arguments['phase_id'] : null,
                'title'              => $title,
                'description'        => ($arguments['description'] ?? null) ?: null,
                'status'             => $arguments['status'] ?? 'open',
                'due_date'           => ($arguments['due_date'] ?? null) ?: null,
                'responsible'        => ($arguments['responsible'] ?? null) ?: null,
                'metadata'           => $arguments['metadata'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $action->id, 'uuid' => $action->uuid,
                'title' => $action->title, 'message' => 'Massnahme erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['sandbox', 'actions', 'create'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
