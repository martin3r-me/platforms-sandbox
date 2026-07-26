<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Sandbox\Models\SandboxStakeholder;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class UpdateSandboxStakeholderTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.stakeholders.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /sandbox/stakeholders/{id} - Aktualisiert einen Stakeholder.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id'         => ['type' => 'integer'],
                'stakeholder_id'  => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'name'            => ['type' => 'string'],
                'role'            => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'influence_level' => ['type' => 'string', 'description' => 'low | medium | high | critical.'],
                'support_level'   => ['type' => 'string', 'description' => 'champion | supporter | neutral | resistant | blocker.'],
                'notes'           => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'entity_id'       => ['type' => 'integer', 'description' => '0 oder null zum Leeren.'],
                'metadata'        => ['type' => 'object'],
            ],
            'required' => ['stakeholder_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $found = $this->validateAndFindModel($arguments, $context, 'stakeholder_id', SandboxStakeholder::class, 'NOT_FOUND', 'Stakeholder nicht gefunden.');
            if ($found['error']) return $found['error'];

            $stakeholder = $found['model'];
            if ((int) $stakeholder->team_id !== $rootTeamId) {
                return ToolResult::error('ACCESS_DENIED', 'Stakeholder gehört nicht zum Team.');
            }

            $update = [];
            if (array_key_exists('name', $arguments)) {
                $val = trim((string) ($arguments['name'] ?? ''));
                if ($val === '') return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                $update['name'] = $val;
            }
            foreach (['role', 'notes'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $val = (string) ($arguments[$field] ?? '');
                    $update[$field] = $val === '' ? null : $val;
                }
            }
            if (array_key_exists('influence_level', $arguments)) $update['influence_level'] = (string) $arguments['influence_level'];
            if (array_key_exists('support_level', $arguments)) $update['support_level'] = (string) $arguments['support_level'];
            if (array_key_exists('entity_id', $arguments)) {
                $val = $arguments['entity_id'];
                $update['entity_id'] = (! empty($val) && (int) $val > 0) ? (int) $val : null;
            }
            if (array_key_exists('metadata', $arguments)) $update['metadata'] = $arguments['metadata'];

            if (! empty($update)) $stakeholder->update($update);
            $stakeholder->refresh();

            return ToolResult::success([
                'id' => $stakeholder->id, 'uuid' => $stakeholder->uuid,
                'name' => $stakeholder->name, 'message' => 'Stakeholder aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['sandbox', 'stakeholders', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
