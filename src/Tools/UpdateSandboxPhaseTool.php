<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Sandbox\Models\SandboxPhase;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;

class UpdateSandboxPhaseTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.phases.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /sandbox/phases/{id} - Aktualisiert Status, Notizen, Verantwortliche oder Nachweis einer Phase.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'     => ['type' => 'integer'],
                'phase_id'    => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'status'      => ['type' => 'string', 'description' => 'not_started | in_progress | completed | blocked.'],
                'notes'       => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'responsible' => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'evidence'    => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'metadata'    => ['type' => 'object'],
            ],
            'required' => ['phase_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $phaseId = $arguments['phase_id'] ?? null;
            if (! $phaseId) return ToolResult::error('VALIDATION_ERROR', 'phase_id ist erforderlich.');

            $phase = SandboxPhase::with('project')->find((int) $phaseId);
            if (! $phase || (int) $phase->project->team_id !== $rootTeamId) {
                return ToolResult::error('NOT_FOUND', 'Phase nicht gefunden.');
            }

            $update = [];
            if (array_key_exists('status', $arguments)) {
                $newStatus = (string) $arguments['status'];
                $update['status'] = $newStatus;
                if ($newStatus === 'in_progress' && ! $phase->started_at) {
                    $update['started_at'] = now();
                }
                if ($newStatus === 'completed' && ! $phase->completed_at) {
                    $update['completed_at'] = now();
                }
                if ($newStatus !== 'completed') {
                    $update['completed_at'] = null;
                }
            }
            foreach (['notes', 'responsible', 'evidence'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $val = (string) ($arguments[$field] ?? '');
                    $update[$field] = $val === '' ? null : $val;
                }
            }
            if (array_key_exists('metadata', $arguments)) {
                $update['metadata'] = $arguments['metadata'];
            }

            if (! empty($update)) $phase->update($update);
            $phase->refresh();

            return ToolResult::success([
                'id'           => $phase->id,
                'uuid'         => $phase->uuid,
                'phase_number' => $phase->phase_number,
                'status'       => $phase->status,
                'responsible'  => $phase->responsible,
                'message'      => 'Phase aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Phase: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category'      => 'action',
            'tags'          => ['sandbox', 'phases', 'update'],
            'read_only'     => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level'    => 'write',
            'idempotent'    => true,
        ];
    }
}
