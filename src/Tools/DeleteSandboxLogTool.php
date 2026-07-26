<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sandbox\Models\SandboxLog;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class DeleteSandboxLogTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.logs.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /sandbox/logs/{id} - Löscht einen Log-Eintrag (soft delete).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'log_id'  => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
            ],
            'required' => ['log_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $found = $this->validateAndFindModel($arguments, $context, 'log_id', SandboxLog::class, 'NOT_FOUND', 'Log-Eintrag nicht gefunden.');
            if ($found['error']) return $found['error'];

            $log = $found['model'];
            if ((int) $log->team_id !== $rootTeamId) {
                return ToolResult::error('ACCESS_DENIED', 'Log-Eintrag gehört nicht zum Team.');
            }

            $log->delete();

            return ToolResult::success(['id' => $log->id, 'message' => 'Log-Eintrag gelöscht.']);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['sandbox', 'logs', 'delete'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
