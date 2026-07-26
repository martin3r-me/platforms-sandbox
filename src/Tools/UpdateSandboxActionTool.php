<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sandbox\Models\SandboxAction;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class UpdateSandboxActionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.actions.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /sandbox/actions/{id} - Aktualisiert eine Massnahme.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id'     => ['type' => 'integer'],
                'action_id'   => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'title'       => ['type' => 'string'],
                'description' => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'status'      => ['type' => 'string', 'description' => 'open | in_progress | done | cancelled.'],
                'due_date'    => ['type' => 'string', 'description' => 'YYYY-MM-DD oder "" zum Leeren.'],
                'responsible' => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'phase_id'    => ['type' => 'integer', 'description' => '0 oder null zum Entfernen.'],
                'metadata'    => ['type' => 'object'],
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

            $update = [];
            if (array_key_exists('title', $arguments)) {
                $val = trim((string) ($arguments['title'] ?? ''));
                if ($val === '') return ToolResult::error('VALIDATION_ERROR', 'title darf nicht leer sein.');
                $update['title'] = $val;
            }
            foreach (['description', 'responsible'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $val = (string) ($arguments[$field] ?? '');
                    $update[$field] = $val === '' ? null : $val;
                }
            }
            if (array_key_exists('status', $arguments)) {
                $update['status'] = (string) $arguments['status'];
                if ($arguments['status'] === 'done' && ! $action->completed_at) {
                    $update['completed_at'] = now();
                } elseif ($arguments['status'] !== 'done') {
                    $update['completed_at'] = null;
                }
            }
            if (array_key_exists('due_date', $arguments)) {
                $val = (string) ($arguments['due_date'] ?? '');
                $update['due_date'] = $val === '' ? null : $val;
            }
            if (array_key_exists('phase_id', $arguments)) {
                $val = $arguments['phase_id'];
                $update['sandbox_phase_id'] = (! empty($val) && (int) $val > 0) ? (int) $val : null;
            }
            if (array_key_exists('metadata', $arguments)) $update['metadata'] = $arguments['metadata'];

            if (! empty($update)) $action->update($update);
            $action->refresh();

            return ToolResult::success([
                'id' => $action->id, 'uuid' => $action->uuid,
                'title' => $action->title, 'status' => $action->status,
                'message' => 'Massnahme aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['sandbox', 'actions', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
