<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Sandbox-Projekte" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[['label' => 'Sandbox-Projekte']]">
            <x-slot name="left">
                <x-nx-input-select
                    wire:key="filter-status"
                    name="statusFilter"
                    :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'paused' => 'Pausiert', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen']"
                    wire:model.live="statusFilter"
                    :nullable="true"
                    nullLabel="Alle Status"
                    size="xs"
                />
            </x-slot>

            <x-nx-button variant="primary" size="sm" wire:click="create">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neues Projekt</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Suche" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4">
                <x-nx-input-text wire:model.live.debounce.300ms="search" placeholder="Name, Code, Beschreibung..." size="sm" />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Activity Sidebar (right) --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivität" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-3">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-muted)]">Letzte Änderungen</div>
                @forelse($this->recentActivity as $log)
                    <a href="{{ route('sandbox.projects.show', $log->sandbox_project_id) }}" class="flex gap-2.5 text-xs group">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($log->phase)
                                <svg width="12" height="12" viewBox="0 0 16 16" style="color: {{ $log->phase->phase_number->color() }};">
                                    @switch($log->phase->phase_number->shape())
                                        @case('triangle')
                                            <polygon points="8,1 15,15 1,15" fill="currentColor"/>
                                            @break
                                        @case('diamond')
                                            <polygon points="8,1 15,8 8,15 1,8" fill="currentColor"/>
                                            @break
                                        @case('circle')
                                            <circle cx="8" cy="8" r="7" fill="currentColor"/>
                                            @break
                                        @case('square')
                                            <rect x="1" y="1" width="14" height="14" fill="currentColor"/>
                                            @break
                                        @case('hexagon')
                                            <polygon points="8,1 14,4 14,12 8,15 2,12 2,4" fill="currentColor"/>
                                            @break
                                        @case('pentagon')
                                            <polygon points="8,1 15,6 12,15 4,15 1,6" fill="currentColor"/>
                                            @break
                                        @case('octagon')
                                            <polygon points="5,1 11,1 15,5 15,11 11,15 5,15 1,11 1,5" fill="currentColor"/>
                                            @break
                                    @endswitch
                                </svg>
                            @else
                                <div class="w-3 h-3 rounded-full bg-[color:var(--nx-muted)]"></div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-[color:var(--nx-text)] truncate group-hover:text-[color:var(--nx-accent)] transition-colors">{{ $log->title }}</p>
                            @if($log->project)
                                <p class="text-[10px] text-[color:var(--nx-muted)] truncate">{{ $log->project->name }}</p>
                            @endif
                            <div class="flex items-center gap-2 text-[color:var(--nx-muted)] mt-0.5">
                                <span style="font-family: 'JetBrains Mono', monospace;">{{ $log->created_at->format('d.m. H:i') }}</span>
                                @if($log->user)
                                    <span>&middot; {{ $log->user->name }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-xs text-[color:var(--nx-muted)] text-center py-4">Noch keine Aktivität.</p>
                @endforelse
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Main content (default slot) --}}
    <x-ui-page-container width="contained">
        <div class="pt-6">
            @if($this->projects->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    @svg('heroicon-o-arrows-right-left', 'w-12 h-12 text-[color:var(--nx-faint)] mb-4')
                    <h3 class="text-sm font-semibold text-[color:var(--nx-text)] mb-1">Keine Sandbox-Projekte</h3>
                    <p class="text-xs text-[color:var(--nx-muted)] mb-4">Erstellen Sie ein neues Sandbox-Projekt, um den Kotter 8-Stufen-Prozess zu starten.</p>
                    <x-nx-button variant="primary" size="sm" wire:click="create">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Neues Projekt
                    </x-nx-button>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->projects as $project)
                        @php
                            $activePhase = $project->phases->firstWhere('status.value', 'in_progress');
                            $borderColor = $activePhase ? $activePhase->phase_number->color() : 'var(--nx-line)';
                        @endphp
                        <a href="{{ route('sandbox.projects.show', $project) }}"
                           class="group block rounded-2xl border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-6 shadow-[var(--nx-shadow-card)] hover:shadow-[var(--nx-shadow-pop)] hover:-translate-y-0.5 transition-all duration-200 border-l-[4px]"
                           style="border-left-color: {{ $borderColor }};">

                            {{-- Header --}}
                            <div class="flex items-start justify-between gap-3 mb-1">
                                <div class="min-w-0">
                                    <h3 class="font-bold text-sm text-[color:var(--nx-text)] truncate group-hover:text-[color:var(--nx-text)] transition-colors">{{ $project->name }}</h3>
                                    @if($project->code)
                                        <span class="text-[11px] text-[color:var(--nx-muted)]" style="font-family: 'JetBrains Mono', monospace;">{{ $project->code }}</span>
                                    @endif
                                </div>
                                <x-nx-badge :color="$project->status->color()" size="xs">{{ $project->status->label() }}</x-nx-badge>
                            </div>

                            {{-- Active Phase Banner --}}
                            @if($activePhase)
                                <div class="flex items-center gap-1.5 mb-3 mt-1">
                                    <span class="w-2 h-2 rounded-full animate-pulse flex-shrink-0" style="background: {{ $activePhase->phase_number->color() }};"></span>
                                    <span class="text-xs font-semibold" style="color: {{ $activePhase->phase_number->color() }};">
                                        Phase {{ $activePhase->phase_number->value }}: {{ $activePhase->phase_number->label() }}
                                    </span>
                                </div>
                            @else
                                <div class="mb-3"></div>
                            @endif

                            @if($project->description)
                                <p class="text-xs text-[color:var(--nx-muted)] line-clamp-2 mb-3">{{ $project->description }}</p>
                            @endif

                            {{-- Mini Bauhaus Shapes + Progress --}}
                            <div class="mb-4">
                                <div class="flex items-center gap-1.5 mb-2">
                                    @foreach($project->phases->sortBy('phase_number.value') as $phase)
                                        @php
                                            $miniStatus = $phase->status->value;
                                            $miniIsFilled = in_array($miniStatus, ['completed', 'in_progress']);
                                            $miniColor = $miniIsFilled ? $phase->phase_number->color() : 'var(--nx-line)';
                                            $miniIsActive = $miniStatus === 'in_progress';
                                        @endphp
                                        <div class="relative">
                                            <svg width="16" height="16" viewBox="0 0 16 16" style="color: {{ $miniColor }};">
                                                @switch($phase->phase_number->shape())
                                                    @case('triangle')
                                                        <polygon points="8,1 15,15 1,15" fill="currentColor"/>
                                                        @break
                                                    @case('diamond')
                                                        <polygon points="8,1 15,8 8,15 1,8" fill="currentColor"/>
                                                        @break
                                                    @case('circle')
                                                        <circle cx="8" cy="8" r="7" fill="currentColor"/>
                                                        @break
                                                    @case('square')
                                                        <rect x="1" y="1" width="14" height="14" fill="currentColor"/>
                                                        @break
                                                    @case('hexagon')
                                                        <polygon points="8,1 14,4 14,12 8,15 2,12 2,4" fill="currentColor"/>
                                                        @break
                                                    @case('pentagon')
                                                        <polygon points="8,1 15,6 12,15 4,15 1,6" fill="currentColor"/>
                                                        @break
                                                    @case('octagon')
                                                        <polygon points="5,1 11,1 15,5 15,11 11,15 5,15 1,11 1,5" fill="currentColor"/>
                                                        @break
                                                @endswitch
                                            </svg>
                                            @if($miniIsActive)
                                                <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full animate-pulse" style="background: {{ $miniColor }};"></span>
                                            @endif
                                        </div>
                                    @endforeach
                                    <span class="ml-auto text-[color:var(--nx-muted)] text-[10px] font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $project->completed_phases_count }}/{{ $project->phases_count }}</span>
                                </div>

                                <div class="flex gap-0.5">
                                    @foreach($project->phases->sortBy('phase_number.value') as $phase)
                                        @php
                                            $segColor = match($phase->status->value) {
                                                'completed' => $phase->phase_number->color(),
                                                'in_progress' => $phase->phase_number->color() . '80',
                                                default => 'var(--nx-line)',
                                            };
                                        @endphp
                                        <div class="flex-1 h-2 rounded-full transition-all duration-500"
                                             style="background: {{ $segColor }};"></div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="flex items-center gap-4 text-xs text-[color:var(--nx-muted)] pt-3 border-t border-[color:var(--nx-line)]">
                                <span class="flex items-center gap-1">
                                    @svg('heroicon-o-clipboard-document-list', 'w-3.5 h-3.5')
                                    {{ $project->actions_count }} Maßnahmen
                                </span>
                                @if($project->plannedEnd())
                                    <span class="flex items-center gap-1" style="font-family: 'JetBrains Mono', monospace;">
                                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                                        {{ $project->plannedEnd()->format('d.m.Y') }}
                                    </span>
                                @endif
                                @if($project->ownerEntity)
                                    <span class="flex items-center gap-1 ml-auto truncate">
                                        @svg('heroicon-o-user-circle', 'w-3.5 h-3.5 flex-shrink-0')
                                        <span class="truncate">{{ $project->ownerEntity->name }}</span>
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Create/Edit Modal --}}
    <x-nx-modal wire:model="modalShow" :title="$editingId ? 'Projekt bearbeiten' : 'Neues Sandbox-Projekt'">
        <form wire:submit="store" class="space-y-4">
            <x-nx-input-text wire:model="form.name" label="Name" required />
            <x-nx-input-text wire:model="form.code" label="Code" placeholder="z.B. CP-001" />
            <x-nx-input-textarea wire:model="form.description" label="Beschreibung" rows="3" />

            <div class="grid grid-cols-2 gap-4">
                <x-nx-input-select
                    name="form.status"
                    wire:model="form.status"
                    label="Status"
                    :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'paused' => 'Pausiert', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen']"
                />
                <x-nx-input-text wire:model="form.target_date" label="Zieldatum" type="date" />
            </div>

            <x-nx-input-select
                name="form.owner_entity_id"
                wire:model="form.owner_entity_id"
                label="Owner (Organisation)"
                :options="$this->availableEntities->pluck('name', 'id')->toArray()"
                :nullable="true"
                nullLabel="Kein Owner"
            />

            <x-nx-input-textarea wire:model="form.urgency_statement" label="Warum ist die Veränderung nötig?" rows="2" />
            <x-nx-input-textarea wire:model="form.vision_statement" label="Strategische Vision" rows="2" />

            <div class="flex justify-end gap-2">
                <x-nx-button variant="secondary" size="sm" wire:click="$set('modalShow', false)" type="button">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" size="sm" type="submit">
                    {{ $editingId ? 'Speichern' : 'Erstellen' }}
                </x-nx-button>
            </div>
        </form>
    </x-nx-modal>
</x-ui-page>
