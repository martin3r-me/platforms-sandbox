<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Sandbox\Models\SandboxStakeholder;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class ListSandboxStakeholdersTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.stakeholders.GET'; }

    public function getDescription(): string
    {
        return 'GET /sandbox/stakeholders - Listet Stakeholder eines Sandbox-Projekts. Filter: influence_level, support_level.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(['team_id', 'project_id', 'influence_level', 'support_level']),
            [
                'properties' => [
                    'team_id'         => ['type' => 'integer'],
                    'project_id'      => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                    'influence_level' => ['type' => 'string', 'description' => 'Optional: low | medium | high | critical.'],
                    'support_level'   => ['type' => 'string', 'description' => 'Optional: champion | supporter | neutral | resistant | blocker.'],
                ],
                'required' => ['project_id'],
            ]
        );
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

            $q = SandboxStakeholder::query()->where('sandbox_project_id', $project->id);

            if (! empty($arguments['influence_level'])) $q->where('influence_level', $arguments['influence_level']);
            if (! empty($arguments['support_level'])) $q->where('support_level', $arguments['support_level']);

            $this->applyStandardSearch($q, $arguments, ['name', 'role', 'notes']);
            $this->applyStandardSort($q, $arguments, ['name', 'influence_level', 'support_level', 'created_at'], 'name', 'asc');

            $result = $this->applyStandardPaginationResult($q, $arguments);
            $items = $result['data']->map(fn (SandboxStakeholder $s) => [
                'id'              => $s->id,
                'uuid'            => $s->uuid,
                'name'            => $s->name,
                'role'            => $s->role,
                'influence_level' => $s->influence_level,
                'support_level'   => $s->support_level,
                'notes'           => $s->notes,
                'entity_id'       => $s->entity_id,
            ])->values()->toArray();

            return ToolResult::success([
                'data'       => $items,
                'pagination' => $result['pagination'] ?? null,
                'project_id' => $project->id,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Stakeholder: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read', 'tags' => ['sandbox', 'stakeholders', 'lookup'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'safe', 'idempotent' => true,
        ];
    }
}
