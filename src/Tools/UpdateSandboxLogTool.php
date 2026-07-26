<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sandbox\Models\SandboxLog;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class UpdateSandboxLogTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.logs.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /sandbox/logs/{id} - Aktualisiert einen Log-Eintrag.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id'  => ['type' => 'integer'],
                'log_id'   => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'title'    => ['type' => 'string'],
                'type'     => ['type' => 'string', 'description' => 'note | milestone | decision | risk | blocker.'],
                'content'  => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'phase_id' => ['type' => 'integer', 'description' => '0 oder null zum Entfernen.'],
                'metadata' => ['type' => 'object'],
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

            $update = [];
            if (array_key_exists('title', $arguments)) {
                $val = trim((string) ($arguments['title'] ?? ''));
                if ($val === '') return ToolResult::error('VALIDATION_ERROR', 'title darf nicht leer sein.');
                $update['title'] = $val;
            }
            if (array_key_exists('type', $arguments)) $update['type'] = (string) $arguments['type'];
            if (array_key_exists('content', $arguments)) {
                $val = (string) ($arguments['content'] ?? '');
                $update['content'] = $val === '' ? null : $val;
            }
            if (array_key_exists('phase_id', $arguments)) {
                $val = $arguments['phase_id'];
                $update['sandbox_phase_id'] = (! empty($val) && (int) $val > 0) ? (int) $val : null;
            }
            if (array_key_exists('metadata', $arguments)) $update['metadata'] = $arguments['metadata'];

            if (! empty($update)) $log->update($update);
            $log->refresh();

            return ToolResult::success([
                'id' => $log->id, 'uuid' => $log->uuid,
                'title' => $log->title, 'type' => $log->type,
                'message' => 'Log-Eintrag aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['sandbox', 'logs', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
