<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class DeleteSandboxProjectTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.projects.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /sandbox/projects/{id} - Löscht ein Sandbox-Projekt (soft delete). Phasen werden kaskadiert gelöscht.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id'    => ['type' => 'integer'],
                'project_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
            ],
            'required' => ['project_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $found = $this->validateAndFindModel($arguments, $context, 'project_id', SandboxProject::class, 'NOT_FOUND', 'Sandbox-Projekt nicht gefunden.');
            if ($found['error']) return $found['error'];

            $project = $found['model'];
            if ((int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('ACCESS_DENIED', 'Projekt gehört nicht zum Root/Elterteam.');
            }

            $project->delete();

            return ToolResult::success([
                'id'      => $project->id,
                'message' => 'Sandbox-Projekt gelöscht (soft delete).',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category'      => 'action',
            'tags'          => ['sandbox', 'projects', 'delete'],
            'read_only'     => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level'    => 'write',
            'idempotent'    => true,
        ];
    }
}
