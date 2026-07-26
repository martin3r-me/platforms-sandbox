@php
    $phaseColor = $phase->phase_number->color();
    $phaseShape = $phase->phase_number->shape();
    $isInProgress = $phase->status->value === 'in_progress';
    $isCompleted = $phase->status->value === 'completed';
    $isBlocked = $phase->status->value === 'blocked';
    $isNotStarted = $phase->status->value === 'not_started';
@endphp

<div class="relative rounded-xl border bg-[color:var(--nx-surface)]/60 backdrop-blur-sm shadow-[var(--nx-shadow-card)] overflow-hidden border-l-[3px] transition-all duration-200
            {{ $isInProgress ? 'ring-1 ring-offset-1 border-t-[2px]' : '' }}
            {{ $isCompleted ? 'opacity-80' : '' }}
            {{ $isBlocked ? 'ring-1 ring-[color:var(--nx-danger)]/30' : '' }}
            {{ !$isInProgress && !$isCompleted && !$isBlocked ? 'border-black/5' : '' }}"
     style="border-left-color: {{ $phaseColor }};
            {{ $isInProgress ? 'ring-color: ' . $phaseColor . '40; border-top-color: ' . $phaseColor . '60;' : '' }}">

    {{-- Watermark shape (large, background) --}}
    <div class="absolute -right-4 -bottom-4 opacity-[0.04] pointer-events-none">
        <svg width="80" height="80" viewBox="0 0 80 80" style="color: {{ $phaseColor }};">
            @switch($phaseShape)
                @case('triangle')
                    <polygon points="40,5 75,75 5,75" fill="currentColor"/>
                    @break
                @case('diamond')
                    <polygon points="40,5 75,40 40,75 5,40" fill="currentColor"/>
                    @break
                @case('circle')
                    <circle cx="40" cy="40" r="35" fill="currentColor"/>
                    @break
                @case('square')
                    <rect x="5" y="5" width="70" height="70" fill="currentColor"/>
                    @break
                @case('hexagon')
                    <polygon points="40,5 70,20 70,60 40,75 10,60 10,20" fill="currentColor"/>
                    @break
                @case('pentagon')
                    <polygon points="40,5 75,30 60,75 20,75 5,30" fill="currentColor"/>
                    @break
                @case('octagon')
                    <polygon points="25,5 55,5 75,25 75,55 55,75 25,75 5,55 5,25" fill="currentColor"/>
                    @break
            @endswitch
        </svg>
    </div>

    {{-- Phase Header --}}
    <div class="flex items-start gap-3 p-4 pb-2 relative z-10">
        {{-- Phase number + shape icon --}}
        <div class="flex-shrink-0 flex flex-col items-center gap-0.5">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center relative" style="background: {{ $phaseColor }}12;">
                @if($isCompleted)
                    <svg width="20" height="20" viewBox="0 0 20 20" style="color: {{ $phaseColor }};">
                        <path d="M6 10l3 3 5-6" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @elseif($isInProgress)
                    <span class="text-lg font-black" style="font-family: 'JetBrains Mono', monospace; color: {{ $phaseColor }};">{{ $phase->phase_number->value }}</span>
                    <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background: {{ $phaseColor }};"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5" style="background: {{ $phaseColor }};"></span>
                    </span>
                @elseif($isBlocked)
                    <span class="text-lg font-black text-[color:var(--nx-danger)]" style="font-family: 'JetBrains Mono', monospace;">{{ $phase->phase_number->value }}</span>
                @else
                    <span class="text-lg font-black" style="font-family: 'JetBrains Mono', monospace; color: {{ $phaseColor }}; opacity: 0.4;">{{ $phase->phase_number->value }}</span>
                @endif
            </div>
        </div>

        {{-- Title + badge --}}
        <div class="min-w-0 flex-1">
            <h3 class="text-sm font-bold leading-tight mb-0.5" style="color: {{ $phaseColor }};">
                {{ $phase->phase_number->shortLabel() }}
            </h3>
            <div class="flex items-center gap-1.5">
                <x-nx-badge :variant="$phase->status->color()" size="xs"
                            style="background-color: {{ $phaseColor }}12; border-color: {{ $phaseColor }}25;">
                    {{ $phase->status->label() }}
                </x-nx-badge>
                @if($phase->responsible)
                    <span class="text-[10px] text-[color:var(--nx-text)] truncate">
                        @svg('heroicon-o-user', 'w-2.5 h-2.5 inline-block') {{ $phase->responsible }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Phase icon --}}
        <div class="flex-shrink-0 opacity-40">
            @svg($phase->phase_number->icon(), 'w-5 h-5')
        </div>
    </div>

    {{-- Description --}}
    <div class="px-4 pb-2 relative z-10">
        <p class="text-[11px] text-[color:var(--nx-text)] leading-relaxed line-clamp-2">
            {{ $phase->phase_number->description() }}
        </p>
    </div>

    {{-- Notes (if present) --}}
    @if($phase->notes && $editingPhaseId !== $phase->id)
        <div class="mx-4 mb-2 relative z-10">
            <div class="text-[11px] text-[color:var(--nx-text)] bg-black/[0.03] rounded p-2 line-clamp-2 border-l-2" style="border-color: {{ $phaseColor }}30;">
                {{ $phase->notes }}
            </div>
        </div>
    @endif

    {{-- Actions count --}}
    @if($phase->actions_count > 0 && $editingPhaseId !== $phase->id)
        @php
            $phaseActionsOpen = $phase->actions->whereNotIn('status.value', ['done', 'cancelled'])->count();
            $phaseActionsDone = $phase->actions->where('status.value', 'done')->count();
        @endphp
        <div class="mx-4 mb-2 relative z-10">
            <div class="flex items-center gap-2 rounded-md px-2.5 py-1.5 text-[11px]" style="background: {{ $phaseColor }}08;">
                @svg('heroicon-o-clipboard-document-list', 'w-3.5 h-3.5 flex-shrink-0')
                <span class="font-medium" style="color: {{ $phaseColor }};">{{ $phase->actions_count }}</span>
                <span class="text-[color:var(--nx-text)]">Massnahmen</span>
                @if($phaseActionsDone > 0)
                    <span class="text-[10px] text-[color:var(--nx-success)]">{{ $phaseActionsDone }} erledigt</span>
                @endif
                @if($phaseActionsOpen > 0)
                    <span class="ml-auto text-[10px] font-medium" style="color: {{ $phaseColor }};">{{ $phaseActionsOpen }} offen</span>
                @endif
            </div>
        </div>
    @endif

    {{-- Kernfrage (immer sichtbar) --}}
    @if($editingPhaseId !== $phase->id)
        <div class="px-4 pb-2 relative z-10">
            <div class="rounded px-2.5 py-2 text-[11px] leading-relaxed" style="background: {{ $phaseColor }}06; border-left: 2px solid {{ $phaseColor }}30;">
                <span class="font-semibold" style="color: {{ $phaseColor }};">Kernfrage:</span>
                <span class="text-[color:var(--nx-text)]">{{ $phase->phase_number->keyQuestion() }}</span>
            </div>
        </div>
    @endif

    {{-- Inline edit form --}}
    @if($editingPhaseId === $phase->id)
        <div class="space-y-2.5 border-t border-black/5 mx-4 pt-2.5 pb-4 relative z-10" x-data="{ status: @entangle('phaseForm.status') }">
            {{-- Row 1: Status + Responsible --}}
            <div class="grid grid-cols-2 gap-2">
                <x-nx-input-select
                    name="phaseForm.status"
                    wire:model.live="phaseForm.status"
                    x-model="status"
                    :options="['not_started' => 'Nicht gestartet', 'in_progress' => 'In Bearbeitung', 'completed' => 'Abgeschlossen', 'blocked' => 'Blockiert']"
                    size="xs"
                    label="Status"
                />
                <x-nx-input-text wire:model="phaseForm.responsible" placeholder="Verantwortlich" size="xs" label="Verantwortlich" />
            </div>

            {{-- Row 2: Timestamps (readonly) --}}
            <div class="flex items-center gap-4 text-[10px] text-[color:var(--nx-text)] bg-black/[0.02] rounded px-2 py-1.5">
                <span>
                    @svg('heroicon-o-play', 'w-3 h-3 inline-block')
                    Gestartet: {{ $phase->started_at ? $phase->started_at->format('d.m.Y') : '—' }}
                </span>
                <span>
                    @svg('heroicon-o-check', 'w-3 h-3 inline-block')
                    Abgeschlossen: {{ $phase->completed_at ? $phase->completed_at->format('d.m.Y') : '—' }}
                </span>
            </div>

            {{-- Row 3: Blocked reason (conditional) --}}
            <div x-show="status === 'blocked'" x-cloak>
                <x-nx-input-text wire:model="phaseForm.blocked_reason" placeholder="Grund der Blockierung..." size="xs" label="Blockiert-Grund" />
            </div>

            {{-- Row 4: Notes --}}
            <x-nx-input-textarea wire:model="phaseForm.notes" placeholder="Notizen..." rows="3" size="xs" label="Notizen" />

            {{-- Row 5: Evidence --}}
            <x-nx-input-textarea wire:model="phaseForm.evidence" placeholder="Nachweis/Dokumentation..." rows="3" size="xs" label="Nachweis" />

            {{-- Actions --}}
            <div class="flex gap-1 pt-1">
                <x-nx-button variant="primary" size="xs" wire:click="updatePhase" style="background-color: {{ $phaseColor }};">Speichern</x-nx-button>
                <x-nx-button variant="secondary" size="xs" wire:click="cancelPhaseEdit">Abbrechen</x-nx-button>
            </div>
        </div>
    @endif

    {{-- Quick actions footer --}}
    @if($editingPhaseId !== $phase->id)
        <div class="flex items-center gap-1.5 border-t border-black/5 px-4 py-2 relative z-10">
            <button wire:click="editPhase({{ $phase->id }})"
                    class="text-xs transition-colors px-1.5 py-0.5 rounded hover:bg-black/5"
                    style="color: {{ $phaseColor }};">
                @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
            </button>
            <button wire:click="createAction({{ $phase->id }})"
                    class="text-xs transition-colors px-1.5 py-0.5 rounded hover:bg-black/5"
                    style="color: {{ $phaseColor }};"
                    title="Massnahme hinzufuegen">
                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
            </button>
            @if($isNotStarted)
                <button wire:click="quickUpdatePhaseStatus({{ $phase->id }}, 'in_progress')"
                        class="ml-auto text-[10px] font-medium transition-colors px-2 py-0.5 rounded hover:bg-black/5 flex items-center gap-1"
                        style="color: {{ $phaseColor }};"
                        title="Phase starten">
                    @svg('heroicon-o-play', 'w-3 h-3')
                    Starten
                </button>
            @elseif($isCompleted)
                <button wire:click="quickUpdatePhaseStatus({{ $phase->id }}, 'not_started')"
                        class="ml-auto text-xs text-[color:var(--nx-success)] hover:text-[color:var(--nx-text)] transition-colors px-1.5 py-0.5 rounded hover:bg-black/5"
                        title="Zuruecksetzen auf nicht gestartet">
                    @svg('heroicon-s-check-circle', 'w-3.5 h-3.5')
                </button>
            @elseif($isInProgress)
                <button wire:click="quickUpdatePhaseStatus({{ $phase->id }}, 'completed')"
                        class="ml-auto text-[10px] font-medium text-[color:var(--nx-text)] hover:text-[color:var(--nx-success)] transition-colors px-2 py-0.5 rounded hover:bg-black/5 flex items-center gap-1"
                        title="Als abgeschlossen markieren">
                    @svg('heroicon-o-check-circle', 'w-3 h-3')
                    Abschliessen
                </button>
            @else
                <button wire:click="quickUpdatePhaseStatus({{ $phase->id }}, 'completed')"
                        class="ml-auto text-xs text-[color:var(--nx-text)] hover:text-[color:var(--nx-success)] transition-colors px-1.5 py-0.5 rounded hover:bg-black/5"
                        title="Als abgeschlossen markieren">
                    @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                </button>
            @endif
        </div>
    @endif
</div>
