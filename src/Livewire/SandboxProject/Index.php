<?php

namespace Platform\Sandbox\Livewire\SandboxProject;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Sandbox\Enums\SandboxProjectStatus;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Models\OrganizationTimePeriod;
use Platform\Organization\Services\StorePlannedPeriod;

class Index extends Component
{
    public string $search = '';
    public string $statusFilter = '';

    public bool $modalShow = false;
    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'code' => '',
        'description' => '',
        'status' => 'draft',
        'target_date' => null,
        'owner_entity_id' => '',
        'urgency_statement' => '',
        'vision_statement' => '',
    ];

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatedSearch(): void { unset($this->projects); }
    public function updatedStatusFilter(): void { unset($this->projects); }

    protected function rules(): array
    {
        return [
            'form.name'              => ['required', 'string', 'max:255'],
            'form.code'              => ['nullable', 'string', 'max:100'],
            'form.description'       => ['nullable', 'string'],
            'form.status'            => ['required', 'in:' . implode(',', SandboxProjectStatus::values())],
            'form.target_date'       => ['nullable', 'date'],
            'form.owner_entity_id'   => ['nullable', 'integer', 'exists:organization_entities,id'],
            'form.urgency_statement' => ['nullable', 'string'],
            'form.vision_statement'  => ['nullable', 'string'],
        ];
    }

    #[Computed]
    public function projects()
    {
        $q = SandboxProject::query()
            ->withCount(['phases as completed_phases_count' => fn ($pq) => $pq->where('status', 'completed')])
            ->withCount('phases')
            ->withCount('actions')
            ->where('team_id', Auth::user()->currentTeamRelation?->getRootTeam()?->id);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }

        return $q->with(['ownerEntity', 'phases', 'plannedPeriodEntries'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function recentActivity()
    {
        return \Platform\Sandbox\Models\SandboxLog::query()
            ->where('team_id', Auth::user()->currentTeamRelation?->getRootTeam()?->id)
            ->with(['project', 'phase', 'user'])
            ->orderByDesc('created_at')
            ->take(15)
            ->get();
    }

    #[Computed]
    public function availableEntities()
    {
        return OrganizationEntity::where('team_id', Auth::user()->currentTeamRelation?->getRootTeam()?->id)
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('form');
        $this->form['status'] = 'draft';
        $this->editingId = null;
        $this->modalShow = true;
    }

    public function edit(int $id): void
    {
        $project = SandboxProject::where('team_id', Auth::user()->currentTeamRelation?->getRootTeam()?->id)->find($id);
        if (! $project) return;

        $this->resetValidation();
        $this->editingId = $project->id;
        $this->form = [
            'name'              => (string) $project->name,
            'code'              => (string) ($project->code ?? ''),
            'description'       => (string) ($project->description ?? ''),
            'status'            => $project->status?->value ?? 'draft',
            'target_date'       => $project->plannedEnd()?->format('Y-m-d'),
            'owner_entity_id'   => (string) ($project->owner_entity_id ?? ''),
            'urgency_statement' => (string) ($project->urgency_statement ?? ''),
            'vision_statement'  => (string) ($project->vision_statement ?? ''),
        ];
        $this->modalShow = true;
    }

    public function store(): void
    {
        $data = $this->validate()['form'];

        $targetDate = $data['target_date'] ?: null;

        $payload = [
            'name'              => trim($data['name']),
            'code'              => $data['code'] !== '' ? $data['code'] : null,
            'description'       => $data['description'] !== '' ? $data['description'] : null,
            'status'            => $data['status'],
            'owner_entity_id'   => $data['owner_entity_id'] !== '' ? (int) $data['owner_entity_id'] : null,
            'urgency_statement' => $data['urgency_statement'] !== '' ? $data['urgency_statement'] : null,
            'vision_statement'  => $data['vision_statement'] !== '' ? $data['vision_statement'] : null,
        ];

        if ($this->editingId) {
            $project = SandboxProject::where('team_id', Auth::user()->currentTeamRelation?->getRootTeam()?->id)->find($this->editingId);
            if ($project) {
                $project->update($payload);

                // Soll-Zeitraum aktualisieren
                $existingPeriod = OrganizationTimePeriod::where('context_type', SandboxProject::class)
                    ->where('context_id', $project->id)
                    ->where('is_active', true)
                    ->first();

                if ($existingPeriod) {
                    app(StorePlannedPeriod::class)->update($existingPeriod, ['planned_end' => $targetDate]);
                } elseif ($targetDate) {
                    app(StorePlannedPeriod::class)->store([
                        'team_id' => $project->team_id,
                        'user_id' => Auth::id(),
                        'context_type' => SandboxProject::class,
                        'context_id' => $project->id,
                        'planned_start' => null,
                        'planned_end' => $targetDate,
                        'note' => null,
                        'is_active' => true,
                    ]);
                }

                $this->dispatch('toast', message: 'Projekt aktualisiert');
            }
        } else {
            $project = SandboxProject::create(array_merge($payload, [
                'team_id' => Auth::user()->currentTeamRelation?->getRootTeam()?->id,
                'user_id' => Auth::id(),
            ]));

            // Soll-Zeitraum erstellen
            if ($targetDate) {
                app(StorePlannedPeriod::class)->store([
                    'team_id' => $project->team_id,
                    'user_id' => Auth::id(),
                    'context_type' => SandboxProject::class,
                    'context_id' => $project->id,
                    'planned_start' => null,
                    'planned_end' => $targetDate,
                    'note' => null,
                    'is_active' => true,
                ]);
            }

            $project->createDefaultPhases();
            $this->dispatch('toast', message: 'Projekt mit 8 Kotter-Phasen erstellt');
        }

        $this->modalShow = false;
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        $project = SandboxProject::where('team_id', Auth::user()->currentTeamRelation?->getRootTeam()?->id)->find($id);
        if (! $project) return;

        $project->delete();
        $this->dispatch('toast', message: 'Projekt gelöscht');
    }

    public function render()
    {
        return view('sandbox::livewire.sandbox-project.index')
            ->layout('platform::layouts.app');
    }
}
