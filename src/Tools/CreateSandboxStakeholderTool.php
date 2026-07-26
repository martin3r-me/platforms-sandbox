<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Models\SandboxStakeholder;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class CreateSandboxStakeholderTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.stakeholders.POST'; }

    public function getDescription(): string
    {
        return 'POST /sandbox/stakeholders - Erstellt einen Stakeholder für ein Sandbox-Projekt.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'         => ['type' => 'integer'],
                'project_id'      => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'name'            => ['type' => 'string', 'description' => 'ERFORDERLICH.'],
                'role'            => ['type' => 'string'],
                'influence_level' => ['type' => 'string', 'description' => 'low | medium | high | critical. Default: medium.'],
                'support_level'   => ['type' => 'string', 'description' => 'champion | supporter | neutral | resistant | blocker. Default: neutral.'],
                'notes'           => ['type' => 'string'],
                'entity_id'       => ['type' => 'integer', 'description' => 'Optional: Verknüpfung mit Organization-Entity.'],
                'metadata'        => ['type' => 'object'],
            ],
            'required' => ['project_id', 'name'],
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

            $name = trim((string) ($arguments['name'] ?? ''));
            if ($name === '') return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');

            $stakeholder = SandboxStakeholder::create([
                'team_id'            => $rootTeamId,
                'user_id'            => $context->user?->id,
                'sandbox_project_id'  => $project->id,
                'name'               => $name,
                'role'               => ($arguments['role'] ?? null) ?: null,
                'influence_level'    => $arguments['influence_level'] ?? 'medium',
                'support_level'      => $arguments['support_level'] ?? 'neutral',
                'notes'              => ($arguments['notes'] ?? null) ?: null,
                'entity_id'          => ! empty($arguments['entity_id']) ? (int) $arguments['entity_id'] : null,
                'metadata'           => $arguments['metadata'] ?? null,
            ]);

            return ToolResult::success([
                'id'   => $stakeholder->id,
                'uuid' => $stakeholder->uuid,
                'name' => $stakeholder->name,
                'message' => 'Stakeholder erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['sandbox', 'stakeholders', 'create'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
