<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sandbox\Models\SandboxAction;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class DeleteSandboxActionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.actions.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /sandbox/actions/{id} - Löscht eine Massnahme (soft delete).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id'   => ['type' => 'integer'],
                'action_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
            ],
            'required' => ['action_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $found = $this->validateAndFindModel($arguments, $context, 'action_id', SandboxAction::class, 'NOT_FOUND', 'Massnahme nicht gefunden.');
            if ($found['error']) return $found['error'];

            $action = $found['model'];
            if ((int) $action->team_id !== $rootTeamId) {
                return ToolResult::error('ACCESS_DENIED', 'Massnahme gehört nicht zum Team.');
            }

            $action->delete();

            return ToolResult::success(['id' => $action->id, 'message' => 'Massnahme gelöscht.']);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['sandbox', 'actions', 'delete'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
