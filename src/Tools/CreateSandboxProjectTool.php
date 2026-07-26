<?php

namespace Platform\Sandbox\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Tools\Concerns\ResolvesSandboxTeam;
use Platform\Organization\Services\StorePlannedPeriod;

class CreateSandboxProjectTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSandboxTeam;

    public function getName(): string { return 'sandbox.projects.POST'; }

    public function getDescription(): string
    {
        return 'POST /sandbox/projects - Erstellt ein Sandbox-Projekt (Kotter 8-Step). Erstellt automatisch 8 Phasen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'           => ['type' => 'integer'],
                'name'              => ['type' => 'string', 'description' => 'ERFORDERLICH: Name des Sandbox-Projekts.'],
                'code'              => ['type' => 'string', 'description' => 'Optional: Kurz-Code (z.B. "CP-001").'],
                'description'       => ['type' => 'string'],
                'status'            => ['type' => 'string', 'description' => 'Optional: draft | active | paused | completed | cancelled. Default: draft.'],
                'target_date'       => ['type' => 'string', 'description' => 'Optional: Zieldatum (YYYY-MM-DD).'],
                'owner_entity_id'   => ['type' => 'integer', 'description' => 'Optional: Owner-Entity (Abteilung, Person, etc.).'],
                'urgency_statement' => ['type' => 'string', 'description' => 'Optional: Warum ist die Veränderung nötig?'],
                'vision_statement'  => ['type' => 'string', 'description' => 'Optional: Strategische Vision.'],
                'metadata'          => ['type' => 'object', 'description' => 'Optional: Freie JSON-Metadaten.'],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $name = trim((string) ($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $project = SandboxProject::create([
                'team_id'           => $rootTeamId,
                'user_id'           => $context->user?->id,
                'name'              => $name,
                'code'              => ($arguments['code'] ?? null) ?: null,
                'description'       => ($arguments['description'] ?? null) ?: null,
                'status'            => $arguments['status'] ?? 'draft',
                'owner_entity_id'   => ! empty($arguments['owner_entity_id']) ? (int) $arguments['owner_entity_id'] : null,
                'urgency_statement' => ($arguments['urgency_statement'] ?? null) ?: null,
                'vision_statement'  => ($arguments['vision_statement'] ?? null) ?: null,
                'metadata'          => $arguments['metadata'] ?? null,
            ]);

            // Soll-Zeitraum über zentrales System speichern (target_date → planned_end)
            $targetDate = ($arguments['target_date'] ?? null) ?: null;
            if ($targetDate) {
                app(StorePlannedPeriod::class)->store([
                    'team_id' => $rootTeamId,
                    'user_id' => $context->user?->id,
                    'context_type' => SandboxProject::class,
                    'context_id' => $project->id,
                    'planned_start' => null,
                    'planned_end' => $targetDate,
                    'note' => null,
                    'is_active' => true,
                ]);
            }

            // Auto-create 8 Kotter phases
            $project->createDefaultPhases();

            return ToolResult::success([
                'id'      => $project->id,
                'uuid'    => $project->uuid,
                'name'    => $project->name,
                'code'    => $project->code,
                'status'  => $project->status,
                'team_id' => $project->team_id,
                'phases_created' => 8,
                'message' => 'Sandbox-Projekt mit 8 Kotter-Phasen erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Sandbox-Projekts: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category'      => 'action',
            'tags'          => ['sandbox', 'projects', 'create'],
            'read_only'     => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level'    => 'write',
            'idempotent'    => false,
        ];
    }
}
