<?php

namespace Platform\Sandbox\Livewire\SandboxProject;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Platform\Sandbox\Enums\SandboxActionStatus;
use Platform\Sandbox\Enums\SandboxLogType;
use Platform\Sandbox\Enums\SandboxPhaseNumber;
use Platform\Sandbox\Enums\SandboxPhaseStatus;
use Platform\Sandbox\Enums\SandboxProjectStatus;
use Platform\Sandbox\Enums\StakeholderInfluence;
use Platform\Sandbox\Enums\StakeholderSupport;
use Platform\Sandbox\Models\SandboxAction;
use Platform\Sandbox\Models\SandboxLog;
use Platform\Sandbox\Models\SandboxPhase;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Sandbox\Models\SandboxStakeholder;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Models\OrganizationTimePeriod;
use Platform\Organization\Services\StorePlannedPeriod;

class Show extends Component
{
    public SandboxProject $project;
    public array $form = [];

    #[Url(as: 'tab')]
    public string $activeTab = 'board';

    // Phase inline editing
    public ?int $editingPhaseId = null;
    public array $phaseForm = [
        'status' => '',
        'responsible' => '',
        'notes' => '',
        'evidence' => '',
        'blocked_reason' => '',
    ];

    // Stakeholder CRUD
    public bool $stakeholderModalShow = false;
    public ?int $editingStakeholderId = null;
    public array $stakeholderForm = [
        'name' => '',
        'role' => '',
        'influence_level' => 'medium',
        'support_level' => 'neutral',
        'notes' => '',
        'entity_id' => '',
    ];

    // Action CRUD
    public bool $actionModalShow = false;
    public ?int $editingActionId = null;
    public array $actionForm = [
        'title' => '',
        'description' => '',
        'status' => 'open',
        'due_date' => null,
        'responsible' => '',
        'phase_id' => '',
    ];

    // Log CRUD
    public bool $logModalShow = false;
    public ?int $editingLogId = null;
    public array $logForm = [
        'title' => '',
        'type' => 'note',
        'content' => '',
        'phase_id' => '',
    ];

    // Filters
    public string $logTypeFilter = '';
    public string $logPhaseFilter = '';
    public string $actionStatusFilter = '';

    public function mount(SandboxProject $project): void
    {
        $this->project = $project->load(['ownerEntity', 'user', 'plannedPeriodEntries']);
        $this->loadForm();
    }

    public function loadForm(): void
    {
        $this->form = [
            'name'              => $this->project->name,
            'code'              => $this->project->code ?? '',
            'description'       => $this->project->description ?? '',
            'status'            => $this->project->status?->value ?? 'draft',
            'target_date'       => $this->project->plannedEnd()?->format('Y-m-d'),
            'owner_entity_id'   => (string) ($this->project->owner_entity_id ?? ''),
            'urgency_statement' => $this->project->urgency_statement ?? '',
            'vision_statement'  => $this->project->vision_statement ?? '',
        ];
    }

    #[Computed]
    public function isDirty(): bool
    {
        return $this->form['name'] !== ($this->project->name ?? '') ||
               $this->form['code'] !== ($this->project->code ?? '') ||
               $this->form['description'] !== ($this->project->description ?? '') ||
               $this->form['status'] !== ($this->project->status?->value ?? 'draft') ||
               $this->form['target_date'] !== $this->project->plannedEnd()?->format('Y-m-d') ||
               $this->form['owner_entity_id'] != ($this->project->owner_entity_id ?? '') ||
               $this->form['urgency_statement'] !== ($this->project->urgency_statement ?? '') ||
               $this->form['vision_statement'] !== ($this->project->vision_statement ?? '');
    }

    #[Computed]
    public function phases()
    {
        return $this->project->phases()
            ->with('actions')
            ->withCount('actions')
            ->orderBy('phase_number')
            ->get();
    }

    #[Computed]
    public function stakeholders()
    {
        return $this->project->stakeholders()
            ->with('entity')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function actions()
    {
        $q = $this->project->actions()->with('phase');
        if ($this->actionStatusFilter !== '') $q->where('status', $this->actionStatusFilter);
        return $q->orderByDesc('created_at')->get();
    }

    #[Computed]
    public function logs()
    {
        $q = $this->project->logs()->with(['phase', 'user']);
        if ($this->logTypeFilter !== '') $q->where('type', $this->logTypeFilter);
        if ($this->logPhaseFilter !== '') $q->where('sandbox_phase_id', (int) $this->logPhaseFilter);
        return $q->orderByDesc('created_at')->get();
    }

    #[Computed]
    public function openActionsCount(): int
    {
        return $this->project->actions()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();
    }

    #[Computed]
    public function totalLogsCount(): int
    {
        return $this->project->logs()->count();
    }

    #[Computed]
    public function currentPhase(): ?SandboxPhase
    {
        return $this->project->phases()
            ->where('status', 'in_progress')
            ->orderBy('phase_number')
            ->first();
    }

    #[Computed]
    public function projectMomentum(): string
    {
        $phases = $this->phases;

        if ($phases->isEmpty()) {
            return 'not_started';
        }

        if ($phases->contains(fn ($p) => $p->status->value === 'blocked')) {
            return 'blocked';
        }

        if ($phases->every(fn ($p) => $p->status->value === 'completed')) {
            return 'completed';
        }

        if ($phases->contains(fn ($p) => $p->status->value === 'in_progress')) {
            return 'progressing';
        }

        if ($phases->contains(fn ($p) => $p->status->value === 'completed')) {
            return 'active';
        }

        return 'not_started';
    }

    #[Computed]
    public function availableEntities()
    {
        return OrganizationEntity::where('team_id', Auth::user()->currentTeamRelation?->getRootTeam()?->id)
            ->orderBy('name')
            ->get();
    }

    // ── Project save/delete ─────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'form.name'              => 'required|string|max:255',
            'form.code'              => 'nullable|string|max:100',
            'form.description'       => 'nullable|string',
            'form.status'            => 'required|in:' . implode(',', SandboxProjectStatus::values()),
            'form.target_date'       => 'nullable|date',
            'form.owner_entity_id'   => 'nullable|integer|exists:organization_entities,id',
            'form.urgency_statement' => 'nullable|string',
            'form.vision_statement'  => 'nullable|string',
        ]);

        $update = [
            'name'              => $this->form['name'],
            'code'              => $this->form['code'] !== '' ? $this->form['code'] : null,
            'description'       => $this->form['description'] !== '' ? $this->form['description'] : null,
            'status'            => $this->form['status'],
            'owner_entity_id'   => $this->form['owner_entity_id'] !== '' ? (int) $this->form['owner_entity_id'] : null,
            'urgency_statement' => $this->form['urgency_statement'] !== '' ? $this->form['urgency_statement'] : null,
            'vision_statement'  => $this->form['vision_statement'] !== '' ? $this->form['vision_statement'] : null,
        ];

        if ($this->form['status'] === 'completed' && ! $this->project->completed_at) {
            $update['completed_at'] = now();
        } elseif ($this->form['status'] !== 'completed') {
            $update['completed_at'] = null;
        }

        $this->project->update($update);

        // Soll-Zeitraum über zentrales System speichern (target_date → planned_end)
        $plannedEnd = $this->form['target_date'] ?: null;
        $existingPeriod = OrganizationTimePeriod::where('context_type', SandboxProject::class)
            ->where('context_id', $this->project->id)
            ->where('is_active', true)
            ->first();

        if ($existingPeriod) {
            app(StorePlannedPeriod::class)->update($existingPeriod, ['planned_end' => $plannedEnd]);
        } elseif ($plannedEnd) {
            app(StorePlannedPeriod::class)->store([
                'team_id' => $this->project->team_id,
                'user_id' => Auth::id(),
                'context_type' => SandboxProject::class,
                'context_id' => $this->project->id,
                'planned_start' => null,
                'planned_end' => $plannedEnd,
                'note' => null,
                'is_active' => true,
            ]);
        }

        $this->project->refresh();
        $this->loadForm();
        $this->dispatch('toast', message: 'Projekt gespeichert');
    }

    public function delete()
    {
        $this->project->delete();
        $this->dispatch('toast', message: 'Projekt gelöscht');
        return redirect()->route('sandbox.projects.index');
    }

    // ── Phase inline update ─────────────────────────────────────

    public function editPhase(int $id): void
    {
        $phase = $this->project->phases()->find($id);
        if (! $phase) return;

        $this->editingPhaseId = $phase->id;
        $this->phaseForm = [
            'status'         => $phase->status?->value ?? 'not_started',
            'responsible'    => $phase->responsible ?? '',
            'notes'          => $phase->notes ?? '',
            'evidence'       => $phase->evidence ?? '',
            'blocked_reason' => $phase->metadata['blocked_reason'] ?? '',
        ];
    }

    public function updatePhase(): void
    {
        $phase = $this->project->phases()->find($this->editingPhaseId);
        if (! $phase) return;

        $metadata = $phase->metadata ?? [];
        if ($this->phaseForm['blocked_reason'] !== '') {
            $metadata['blocked_reason'] = $this->phaseForm['blocked_reason'];
        } else {
            unset($metadata['blocked_reason']);
        }

        $update = [
            'status'      => $this->phaseForm['status'],
            'responsible' => $this->phaseForm['responsible'] !== '' ? $this->phaseForm['responsible'] : null,
            'notes'       => $this->phaseForm['notes'] !== '' ? $this->phaseForm['notes'] : null,
            'evidence'    => $this->phaseForm['evidence'] !== '' ? $this->phaseForm['evidence'] : null,
            'metadata'    => $metadata ?: null,
        ];

        if ($this->phaseForm['status'] === 'in_progress' && ! $phase->started_at) {
            $update['started_at'] = now();
        }
        if ($this->phaseForm['status'] === 'completed' && ! $phase->completed_at) {
            $update['completed_at'] = now();
        }
        if ($this->phaseForm['status'] !== 'completed') {
            $update['completed_at'] = null;
        }

        $phase->update($update);
        $this->editingPhaseId = null;
        unset($this->phases);
        $this->dispatch('toast', message: 'Phase aktualisiert');
    }

    public function cancelPhaseEdit(): void
    {
        $this->editingPhaseId = null;
    }

    public function quickUpdatePhaseStatus(int $phaseId, string $status): void
    {
        $phase = $this->project->phases()->find($phaseId);
        if (! $phase) return;

        $update = ['status' => $status];
        if ($status === 'in_progress' && ! $phase->started_at) $update['started_at'] = now();
        if ($status === 'completed' && ! $phase->completed_at) $update['completed_at'] = now();
        if ($status === 'not_started') {
            $update['started_at'] = null;
            $update['completed_at'] = null;
        }
        if ($status !== 'completed') $update['completed_at'] = null;

        $phase->update($update);
        unset($this->phases);
        $this->dispatch('toast', message: 'Phase-Status aktualisiert');
    }

    // ── Stakeholder CRUD ────────────────────────────────────────

    public function createStakeholder(): void
    {
        $this->resetValidation();
        $this->editingStakeholderId = null;
        $this->stakeholderForm = [
            'name' => '', 'role' => '', 'influence_level' => 'medium',
            'support_level' => 'neutral', 'notes' => '', 'entity_id' => '',
        ];
        $this->stakeholderModalShow = true;
    }

    public function editStakeholder(int $id): void
    {
        $stakeholder = $this->project->stakeholders()->find($id);
        if (! $stakeholder) return;

        $this->resetValidation();
        $this->editingStakeholderId = $stakeholder->id;
        $this->stakeholderForm = [
            'name'            => $stakeholder->name,
            'role'            => $stakeholder->role ?? '',
            'influence_level' => $stakeholder->influence_level?->value ?? 'medium',
            'support_level'   => $stakeholder->support_level?->value ?? 'neutral',
            'notes'           => $stakeholder->notes ?? '',
            'entity_id'       => (string) ($stakeholder->entity_id ?? ''),
        ];
        $this->stakeholderModalShow = true;
    }

    public function storeStakeholder(): void
    {
        $this->validate([
            'stakeholderForm.name'            => 'required|string|max:255',
            'stakeholderForm.role'            => 'nullable|string|max:255',
            'stakeholderForm.influence_level' => 'required|in:' . implode(',', StakeholderInfluence::values()),
            'stakeholderForm.support_level'   => 'required|in:' . implode(',', StakeholderSupport::values()),
            'stakeholderForm.notes'           => 'nullable|string',
            'stakeholderForm.entity_id'       => 'nullable|integer|exists:organization_entities,id',
        ]);

        $payload = [
            'name'            => $this->stakeholderForm['name'],
            'role'            => $this->stakeholderForm['role'] !== '' ? $this->stakeholderForm['role'] : null,
            'influence_level' => $this->stakeholderForm['influence_level'],
            'support_level'   => $this->stakeholderForm['support_level'],
            'notes'           => $this->stakeholderForm['notes'] !== '' ? $this->stakeholderForm['notes'] : null,
            'entity_id'       => $this->stakeholderForm['entity_id'] !== '' ? (int) $this->stakeholderForm['entity_id'] : null,
        ];

        if ($this->editingStakeholderId) {
            $stakeholder = $this->project->stakeholders()->find($this->editingStakeholderId);
            $stakeholder?->update($payload);
            $this->dispatch('toast', message: 'Stakeholder aktualisiert');
        } else {
            $this->project->stakeholders()->create(array_merge($payload, [
                'team_id' => Auth::user()->currentTeamRelation?->getRootTeam()?->id,
                'user_id' => Auth::id(),
            ]));
            $this->dispatch('toast', message: 'Stakeholder erstellt');
        }

        $this->stakeholderModalShow = false;
        unset($this->stakeholders);
    }

    public function deleteStakeholder(int $id): void
    {
        $this->project->stakeholders()->where('id', $id)->delete();
        unset($this->stakeholders);
        $this->dispatch('toast', message: 'Stakeholder gelöscht');
    }

    // ── Action CRUD ─────────────────────────────────────────────

    public function createAction(?int $phaseId = null): void
    {
        $this->resetValidation();
        $this->editingActionId = null;
        $this->actionForm = [
            'title' => '', 'description' => '', 'status' => 'open',
            'due_date' => null, 'responsible' => '',
            'phase_id' => $phaseId ? (string) $phaseId : '',
        ];
        $this->actionModalShow = true;
    }

    public function editAction(int $id): void
    {
        $action = $this->project->actions()->find($id);
        if (! $action) return;

        $this->resetValidation();
        $this->editingActionId = $action->id;
        $this->actionForm = [
            'title'       => $action->title,
            'description' => $action->description ?? '',
            'status'      => $action->status?->value ?? 'open',
            'due_date'    => $action->due_date?->format('Y-m-d'),
            'responsible' => $action->responsible ?? '',
            'phase_id'    => (string) ($action->sandbox_phase_id ?? ''),
        ];
        $this->actionModalShow = true;
    }

    public function storeAction(): void
    {
        $this->validate([
            'actionForm.title'       => 'required|string|max:255',
            'actionForm.description' => 'nullable|string',
            'actionForm.status'      => 'required|in:' . implode(',', SandboxActionStatus::values()),
            'actionForm.due_date'    => 'nullable|date',
            'actionForm.responsible' => 'nullable|string|max:255',
            'actionForm.phase_id'    => 'nullable|integer|exists:sandbox_phases,id',
        ]);

        $payload = [
            'title'          => $this->actionForm['title'],
            'description'    => $this->actionForm['description'] !== '' ? $this->actionForm['description'] : null,
            'status'         => $this->actionForm['status'],
            'due_date'       => $this->actionForm['due_date'] ?: null,
            'responsible'    => $this->actionForm['responsible'] !== '' ? $this->actionForm['responsible'] : null,
            'sandbox_phase_id' => $this->actionForm['phase_id'] !== '' ? (int) $this->actionForm['phase_id'] : null,
        ];

        if ($this->actionForm['status'] === 'done') {
            $payload['completed_at'] = now();
        }

        if ($this->editingActionId) {
            $action = $this->project->actions()->find($this->editingActionId);
            if ($action) {
                if ($this->actionForm['status'] === 'done' && ! $action->completed_at) {
                    $payload['completed_at'] = now();
                } elseif ($this->actionForm['status'] !== 'done') {
                    $payload['completed_at'] = null;
                }
                $action->update($payload);
            }
            $this->dispatch('toast', message: 'Massnahme aktualisiert');
        } else {
            $this->project->actions()->create(array_merge($payload, [
                'team_id' => Auth::user()->currentTeamRelation?->getRootTeam()?->id,
                'user_id' => Auth::id(),
            ]));
            $this->dispatch('toast', message: 'Massnahme erstellt');
        }

        $this->actionModalShow = false;
        unset($this->actions, $this->phases);
    }

    public function deleteAction(int $id): void
    {
        $this->project->actions()->where('id', $id)->delete();
        unset($this->actions, $this->phases);
        $this->dispatch('toast', message: 'Massnahme gelöscht');
    }

    public function quickUpdateActionStatus(int $actionId, string $status): void
    {
        $action = $this->project->actions()->find($actionId);
        if (! $action) return;

        $update = ['status' => $status];
        if ($status === 'done' && ! $action->completed_at) $update['completed_at'] = now();
        if ($status !== 'done') $update['completed_at'] = null;

        $action->update($update);
        unset($this->actions, $this->phases);
    }

    // ── Log CRUD ────────────────────────────────────────────────

    public function createLog(?int $phaseId = null): void
    {
        $this->resetValidation();
        $this->editingLogId = null;
        $this->logForm = [
            'title' => '', 'type' => 'note', 'content' => '',
            'phase_id' => $phaseId ? (string) $phaseId : '',
        ];
        $this->logModalShow = true;
    }

    public function editLog(int $id): void
    {
        $log = $this->project->logs()->find($id);
        if (! $log) return;

        $this->resetValidation();
        $this->editingLogId = $log->id;
        $this->logForm = [
            'title'    => $log->title,
            'type'     => $log->type?->value ?? 'note',
            'content'  => $log->content ?? '',
            'phase_id' => (string) ($log->sandbox_phase_id ?? ''),
        ];
        $this->logModalShow = true;
    }

    public function storeLog(): void
    {
        $this->validate([
            'logForm.title'    => 'required|string|max:255',
            'logForm.type'     => 'required|in:' . implode(',', SandboxLogType::values()),
            'logForm.content'  => 'nullable|string',
            'logForm.phase_id' => 'nullable|integer|exists:sandbox_phases,id',
        ]);

        $payload = [
            'title'           => $this->logForm['title'],
            'type'            => $this->logForm['type'],
            'content'         => $this->logForm['content'] !== '' ? $this->logForm['content'] : null,
            'sandbox_phase_id' => $this->logForm['phase_id'] !== '' ? (int) $this->logForm['phase_id'] : null,
        ];

        if ($this->editingLogId) {
            $log = $this->project->logs()->find($this->editingLogId);
            $log?->update($payload);
            $this->dispatch('toast', message: 'Log-Eintrag aktualisiert');
        } else {
            $this->project->logs()->create(array_merge($payload, [
                'team_id' => Auth::user()->currentTeamRelation?->getRootTeam()?->id,
                'user_id' => Auth::id(),
            ]));
            $this->dispatch('toast', message: 'Log-Eintrag erstellt');
        }

        $this->logModalShow = false;
        unset($this->logs);
    }

    public function deleteLog(int $id): void
    {
        $this->project->logs()->where('id', $id)->delete();
        unset($this->logs);
        $this->dispatch('toast', message: 'Log-Eintrag gelöscht');
    }

    public function render()
    {
        return view('sandbox::livewire.sandbox-project.show')
            ->layout('platform::layouts.app');
    }
}
