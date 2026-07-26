<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sandbox-Projekte', 'href' => route('sandbox.projects.index')],
            ['label' => $project->name],
        ]">
            <x-slot name="left">
                <div class="flex items-center gap-1 mr-4">
                    @php
                        $tabConfig = [
                            'board' => ['icon' => 'heroicon-o-view-columns', 'label' => 'Board'],
                            'stakeholder' => ['icon' => 'heroicon-o-user-group', 'label' => 'Stakeholder'],
                            'log' => ['icon' => 'heroicon-o-document-text', 'label' => 'Log'],
                            'settings' => ['icon' => 'heroicon-o-cog-6-tooth', 'label' => 'Einstellungen'],
                        ];
                    @endphp
                    @foreach($tabConfig as $tab => $cfg)
                        <button wire:click="$set('activeTab', '{{ $tab }}')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium transition-all duration-150
                                       {{ $activeTab === $tab ? 'bg-[color:var(--nx-accent)]/10 text-[color:var(--nx-accent)]' : 'text-[color:var(--nx-text)] hover:bg-black/5' }}">
                            @svg($cfg['icon'], 'w-3.5 h-3.5')
                            {{ $cfg['label'] }}
                        </button>
                    @endforeach
                </div>
                @if($this->isDirty)
                    <x-nx-button variant="secondary" size="xs" wire:click="loadForm">Abbrechen</x-nx-button>
                    <x-nx-button variant="primary" size="xs" wire:click="save">Speichern</x-nx-button>
                @endif
            </x-slot>

            <x-nx-badge :variant="$project->status->color()" size="sm">{{ $project->status->label() }}</x-nx-badge>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Projekt" :defaultOpen="true" side="left">
            <div class="p-4 space-y-5">
                {{-- Progress --}}
                <div>
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--nx-muted)] mb-3" style="font-family: 'JetBrains Mono', monospace;">Fortschritt</h3>
                    @php
                        $completedPhases = $this->phases->where('status.value', 'completed')->count();
                        $totalPhases = $this->phases->count();
                        $progress = $totalPhases > 0 ? round(($completedPhases / $totalPhases) * 100) : 0;
                        $circumference = 2 * M_PI * 36;
                        $dashOffset = $circumference - ($circumference * $progress / 100);
                    @endphp
                    <div class="flex justify-center mb-3">
                        <div class="relative">
                            <svg width="80" height="80" viewBox="0 0 96 96">
                                <circle cx="48" cy="48" r="36" fill="none" stroke="var(--nx-line)" stroke-width="6" />
                                <circle cx="48" cy="48" r="36" fill="none" stroke="var(--nx-info)" stroke-width="6"
                                        stroke-dasharray="{{ $circumference }}"
                                        stroke-dashoffset="{{ $dashOffset }}"
                                        stroke-linecap="round"
                                        transform="rotate(-90 48 48)"
                                        style="transition: stroke-dashoffset 0.5s ease;" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-base font-bold text-[color:var(--nx-text)]" style="font-family: 'JetBrains Mono', monospace;">{{ $progress }}%</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center text-xs text-[color:var(--nx-muted)] mb-3">
                        {{ $completedPhases }}/{{ $totalPhases }} Phasen
                    </div>
                    <div class="space-y-1.5">
                        @foreach($this->phases as $phase)
                            @php
                                $phaseColor = $phase->phase_number->color();
                                $isActive = in_array($phase->status->value, ['completed', 'in_progress']);
                                $shapeColor = $isActive ? $phaseColor : 'var(--nx-line)';
                            @endphp
                            <div class="flex items-center gap-2.5 text-xs">
                                <svg width="14" height="14" viewBox="0 0 16 16" style="color: {{ $shapeColor }};">
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
                                <span class="truncate {{ $phase->status->value === 'completed' ? 'line-through opacity-50' : '' }} {{ $phase->status->value === 'in_progress' ? 'font-medium' : '' }}"
                                      style="{{ $isActive ? 'color: ' . $phaseColor . ';' : '' }}">
                                    {{ $phase->phase_number->shortLabel() }}
                                </span>
                                @if($phase->status->value === 'completed')
                                    <span class="ml-auto text-[10px]" style="color: {{ $phaseColor }};">&#10003;</span>
                                @elseif($phase->status->value === 'in_progress')
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full animate-pulse" style="background: {{ $phaseColor }};"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Key Metrics --}}
                <div class="border-t border-[color:var(--nx-line)] pt-4">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--nx-muted)] mb-2" style="font-family: 'JetBrains Mono', monospace;">Kennzahlen</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-[color:var(--nx-muted)]">Massnahmen offen</span>
                            <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $this->openActionsCount }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[color:var(--nx-muted)]">Stakeholder</span>
                            <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $this->stakeholders->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[color:var(--nx-muted)]">Log-Eintraege</span>
                            <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $this->totalLogsCount }}</span>
                        </div>
                        @if($project->plannedEnd())
                            <div class="flex justify-between">
                                <span class="text-[color:var(--nx-muted)]">Zieldatum</span>
                                <span class="font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $project->plannedEnd()->format('d.m.Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Activity Sidebar (right) --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Sandbox-Log" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-3">
                @php
                    $recentLogs = $this->project->logs()->with(['phase', 'user'])->orderByDesc('created_at')->take(10)->get();
                @endphp
                @if($recentLogs->isEmpty())
                    <p class="text-xs text-[color:var(--nx-muted)] text-center py-4">Noch keine Log-Eintraege.</p>
                @else
                    @foreach($recentLogs as $log)
                        <div class="flex gap-2.5 text-xs">
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
                                <p class="font-medium text-[color:var(--nx-text)] truncate">{{ $log->title }}</p>
                                <div class="flex items-center gap-2 text-[color:var(--nx-muted)] mt-0.5">
                                    <span style="font-family: 'JetBrains Mono', monospace;">{{ $log->created_at->format('d.m. H:i') }}</span>
                                    @if($log->user)
                                        <span>&middot; {{ $log->user->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Main content (default slot) --}}
    <x-ui-page-container width="contained">
        <div class="py-6">

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: BOARD --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'board')

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- PHASE JOURNEY BAR (in Card) --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="mb-8 rounded-xl border border-black/5 bg-[color:var(--nx-surface)]/60 backdrop-blur-sm p-5 pb-4">
                <h2 class="text-[10px] font-bold uppercase tracking-[0.2em] text-[color:var(--nx-muted)] mb-4" style="font-family: 'JetBrains Mono', monospace;">KOTTER 8-STEP MODEL</h2>

                <div class="relative px-2">
                    {{-- Connection line (background) --}}
                    <div class="absolute top-[20px] left-[calc(1rem+20px)] right-[calc(1rem+20px)] h-[2px] bg-black/[0.06]"></div>

                    {{-- Colored progress line overlay --}}
                    @php
                        $lastActiveIndex = -1;
                        foreach ($this->phases as $idx => $p) {
                            if (in_array($p->status->value, ['completed', 'in_progress'])) {
                                $lastActiveIndex = $idx;
                            }
                        }
                        $progressPercent = $lastActiveIndex >= 0 ? ($lastActiveIndex / 7) * 100 : 0;
                    @endphp
                    @if($lastActiveIndex >= 0)
                        <div class="absolute top-[20px] left-[calc(1rem+20px)] h-[2px] transition-all duration-700 ease-out"
                             style="width: {{ $progressPercent }}%; background: linear-gradient(90deg, var(--nx-tone-rose), var(--nx-warning), var(--nx-warning), var(--nx-tone-teal), var(--nx-tone-sky));"></div>
                    @endif

                    {{-- 8 Phase shapes --}}
                    <div class="relative flex items-start justify-between">
                        @foreach($this->phases as $idx => $phase)
                            @php
                                $jColor = $phase->phase_number->color();
                                $jShape = $phase->phase_number->shape();
                                $jStatus = $phase->status->value;
                                $isFilled = in_array($jStatus, ['completed', 'in_progress']);
                                $isActive = $jStatus === 'in_progress';
                                $isBlocked = $jStatus === 'blocked';
                                $fillColor = $isFilled ? $jColor : 'var(--nx-line)';
                                $strokeColor = $isBlocked ? 'var(--nx-danger)' : 'none';
                                $strokeWidth = $isBlocked ? '2' : '0';
                            @endphp
                            <button wire:click="editPhase({{ $phase->id }})" class="flex flex-col items-center group cursor-pointer" style="width: 60px;" title="{{ $phase->phase_number->label() }}">
                                {{-- Shape container with optional glow --}}
                                <div class="relative flex items-center justify-center w-[40px] h-[40px] transition-transform duration-200 group-hover:scale-110">
                                    @if($isActive)
                                        {{-- Glow ring for active phase --}}
                                        <div class="absolute inset-[-6px] rounded-full opacity-40 animate-ping" style="background: {{ $jColor }}; animation-duration: 2s;"></div>
                                        <div class="absolute inset-[-4px] rounded-full opacity-25" style="background: {{ $jColor }};"></div>
                                        <div class="absolute inset-[-2px] rounded-full opacity-15" style="box-shadow: 0 0 12px {{ $jColor }};"></div>
                                    @endif
                                    <svg width="40" height="40" viewBox="0 0 40 40" class="relative z-10">
                                        @switch($jShape)
                                            @case('triangle')
                                                <polygon points="20,3 37,37 3,37" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                                @break
                                            @case('diamond')
                                                <polygon points="20,3 37,20 20,37 3,20" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                                @break
                                            @case('circle')
                                                <circle cx="20" cy="20" r="17" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                                @break
                                            @case('square')
                                                <rect x="3" y="3" width="34" height="34" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                                @break
                                            @case('hexagon')
                                                <polygon points="20,3 35,11 35,29 20,37 5,29 5,11" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                                @break
                                            @case('pentagon')
                                                <polygon points="20,3 37,15 30,37 10,37 3,15" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                                @break
                                            @case('octagon')
                                                <polygon points="13,3 27,3 37,13 37,27 27,37 13,37 3,27 3,13" fill="{{ $fillColor }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"/>
                                                @break
                                        @endswitch
                                        {{-- Checkmark for completed --}}
                                        @if($jStatus === 'completed')
                                            <path d="M14 20l4 4 8-8" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                        @endif
                                        {{-- Phase number inside shape (not completed) --}}
                                        @if($jStatus !== 'completed')
                                            <text x="20" y="20" text-anchor="middle" dominant-baseline="central"
                                                  fill="{{ $isFilled ? 'white' : 'var(--nx-muted)' }}"
                                                  font-size="13" font-weight="700" font-family="'JetBrains Mono', monospace">{{ $phase->phase_number->value }}</text>
                                        @endif
                                    </svg>
                                </div>
                                {{-- Short label only (no redundant number) --}}
                                <span class="mt-1.5 text-[9px] text-center leading-tight max-w-[60px] truncate font-medium {{ !$isFilled ? 'text-[color:var(--nx-muted)]' : '' }}"
                                      @if($isFilled) style="color: {{ $jColor }};" @endif>
                                    {{ $phase->phase_number->shortLabel() }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- SANDBOX-WISDOM LEISTE --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="mb-6 flex items-center gap-3 rounded-lg bg-black/[0.02] border border-black/5 px-4 py-2.5 text-[11px] text-[color:var(--nx-text)]"
                 x-data="{
                    tips: [
                        '70% aller Sandbox-Projekte scheitern — meist an mangelnder Dringlichkeit und fehlender Koalition (Kotter, 1996).',
                        'Die 20/60/20-Regel: Gewinnen Sie die 60% Unentschlossenen ueber Ihre 20% Begeisterten.',
                        'Menschen widersetzen sich nicht der Veraenderung — sie widersetzen sich dem Veraendert-werden (Peter Senge).',
                        'Quick Wins sind kein Nice-to-have. Ohne fruehe Erfolge verliert jede Veraenderung an Glaubwuerdigkeit.',
                        'Kultur laesst sich nicht per Dekret aendern. Sie aendert sich durch neue Gewohnheiten und Erlebnisse.',
                        'Der groesste Fehler: Nach dem ersten Erfolg den Sieg erklaeren. Veraenderung braucht Ausdauer.',
                        'Widerstand ist ein Signal, kein Problem. Er zeigt, wo echte Anpassung noetig ist.',
                        'Eine Vision, die nicht in einem Satz erklaerbar ist, wird nie gelebt werden.'
                    ],
                    current: 0,
                    init() { this.current = Math.floor(Math.random() * this.tips.length); }
                 }">
                @svg('heroicon-o-academic-cap', 'w-4 h-4 text-[color:var(--nx-muted)] flex-shrink-0')
                <p class="flex-1 leading-relaxed" x-text="tips[current]"></p>
                <button @click="current = (current + 1) % tips.length" class="flex-shrink-0 text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)] transition-colors" title="Naechster Tipp">
                    @svg('heroicon-o-arrow-path', 'w-3.5 h-3.5')
                </button>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- ACTIVE PHASE SPOTLIGHT --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            @php
                $activePhase = $this->phases->firstWhere('status.value', 'in_progress');
            @endphp

            @if($activePhase)
                @php
                    $spotColor = $activePhase->phase_number->color();
                    $spotShape = $activePhase->phase_number->shape();
                    $phaseActions = $activePhase->actions ?? collect();
                    $openActions = $phaseActions->whereNotIn('status.value', ['done', 'cancelled'])->count();
                    $doneActions = $phaseActions->where('status.value', 'done')->count();
                @endphp
                <div class="mb-8 rounded-xl border border-black/5 p-5 relative overflow-hidden"
                     style="background: {{ $spotColor }}10;">
                    {{-- Watermark shape --}}
                    <div class="absolute -right-6 -bottom-6 opacity-[0.06] pointer-events-none">
                        <svg width="140" height="140" viewBox="0 0 80 80" style="color: {{ $spotColor }};">
                            @switch($spotShape)
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

                    <div class="flex items-start gap-6 relative z-10">
                        {{-- Left: Big phase number --}}
                        <div class="flex-shrink-0 hidden sm:block">
                            <span class="text-6xl font-black leading-none" style="font-family: 'JetBrains Mono', monospace; color: {{ $spotColor }}; opacity: 0.10;">
                                {{ $activePhase->phase_number->value }}
                            </span>
                        </div>

                        {{-- Center: Phase info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <x-nx-badge size="xs" color="primary">In Bearbeitung</x-nx-badge>
                            </div>
                            <h3 class="text-lg font-bold mb-1" style="color: {{ $spotColor }};">
                                {{ $activePhase->phase_number->label() }}
                            </h3>
                            <p class="text-xs text-[color:var(--nx-text)] mb-2 line-clamp-2">
                                {{ $activePhase->phase_number->description() }}
                            </p>
                            @if($activePhase->responsible)
                                <div class="text-xs text-[color:var(--nx-text)]">
                                    @svg('heroicon-o-user', 'w-3 h-3 inline-block')
                                    {{ $activePhase->responsible }}
                                </div>
                            @endif
                        </div>

                        {{-- Right: Quick stats + CTA --}}
                        <div class="flex-shrink-0 text-right space-y-2">
                            <div class="flex items-center gap-4 text-xs">
                                <div>
                                    <span class="block text-lg font-bold" style="font-family: 'JetBrains Mono', monospace; color: {{ $spotColor }};">{{ $openActions }}</span>
                                    <span class="text-[color:var(--nx-text)]">offen</span>
                                </div>
                                <div>
                                    <span class="block text-lg font-bold" style="font-family: 'JetBrains Mono', monospace; color: {{ $spotColor }};">{{ $doneActions }}</span>
                                    <span class="text-[color:var(--nx-text)]">erledigt</span>
                                </div>
                            </div>
                            <div class="flex gap-1.5">
                                <x-nx-button variant="secondary" size="xs" wire:click="editPhase({{ $activePhase->id }})">
                                    @svg('heroicon-o-pencil', 'w-3 h-3')
                                </x-nx-button>
                                <x-nx-button variant="primary" size="xs" wire:click="createAction({{ $activePhase->id }})">
                                    @svg('heroicon-o-plus', 'w-3 h-3')
                                    Massnahme
                                </x-nx-button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- No active phase — inviting CTA --}}
                @php $firstPhase = $this->phases->first(); @endphp
                <div class="mb-8 rounded-xl border border-dashed border-black/10 bg-[color:var(--nx-surface)]/40 p-6 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-black/[0.04] flex items-center justify-center">
                            @svg('heroicon-o-play', 'w-5 h-5 text-[color:var(--nx-text)]')
                        </div>
                        <div>
                            <p class="text-sm font-medium text-[color:var(--nx-text)] mb-1">Kein aktiver Schritt</p>
                            <p class="text-xs text-[color:var(--nx-text)]">Starten Sie den Sandbox-Prozess, indem Sie Phase 1 in Bearbeitung setzen.</p>
                        </div>
                        @if($firstPhase && $firstPhase->status->value === 'not_started')
                            <x-nx-button variant="primary" size="xs" wire:click="quickUpdatePhaseStatus({{ $firstPhase->id }}, 'in_progress')">
                                @svg('heroicon-o-play', 'w-3.5 h-3.5')
                                Phase 1 starten
                            </x-nx-button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- STAGE I --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="flex items-center gap-2 mb-3">
                <div class="h-px flex-1 bg-gradient-to-r from-[var(--nx-tone-rose)]/20 via-[var(--nx-warning)]/20 to-transparent"></div>
                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--nx-muted)] flex-shrink-0" style="font-family: 'JetBrains Mono', monospace;">I. Voraussetzungen schaffen</h3>
                <div class="h-px flex-1 bg-gradient-to-l from-[var(--nx-tone-rose)]/20 via-[var(--nx-warning)]/20 to-transparent"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                @foreach($this->phases->take(4) as $phase)
                    @include('sandbox::livewire.sandbox-project._phase-card', ['phase' => $phase])
                @endforeach
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- STAGE II --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="flex items-center gap-2 mb-3">
                <div class="h-px flex-1 bg-gradient-to-r from-[var(--nx-tone-teal)]/20 via-[var(--nx-tone-sky)]/20 to-transparent"></div>
                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--nx-muted)] flex-shrink-0" style="font-family: 'JetBrains Mono', monospace;">II. Umsetzen & Verankern</h3>
                <div class="h-px flex-1 bg-gradient-to-l from-[var(--nx-tone-teal)]/20 via-[var(--nx-tone-sky)]/20 to-transparent"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                @foreach($this->phases->skip(4) as $phase)
                    @include('sandbox::livewire.sandbox-project._phase-card', ['phase' => $phase])
                @endforeach
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- MASSNAHMEN (in Card) --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="rounded-xl border border-black/5 bg-[color:var(--nx-surface)]/60 backdrop-blur-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[color:var(--nx-accent)]/10 flex items-center justify-center">
                            @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 text-[color:var(--nx-accent)]')
                        </div>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-[color:var(--nx-text)]" style="font-family: 'JetBrains Mono', monospace;">Alle Massnahmen</h2>
                            <p class="text-[10px] text-[color:var(--nx-muted)]">{{ $this->actions->count() }} gesamt &middot; {{ $this->openActionsCount }} offen</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-nx-input-select
                            name="actionStatusFilter"
                            wire:model.live="actionStatusFilter"
                            :options="['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'done' => 'Erledigt', 'cancelled' => 'Abgebrochen']"
                            :nullable="true"
                            nullLabel="Alle"
                            size="xs"
                        />
                        <x-nx-button variant="primary" size="xs" wire:click="createAction">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            Massnahme
                        </x-nx-button>
                    </div>
                </div>

                @if($this->actions->isEmpty())
                    <div class="rounded-lg border border-dashed border-black/10 bg-black/[0.02] py-8 text-center">
                        <div class="flex flex-col items-center gap-2">
                            @svg('heroicon-o-clipboard-document-list', 'w-8 h-8 text-[color:var(--nx-muted)]')
                            <p class="text-sm text-[color:var(--nx-text)]">Noch keine Massnahmen definiert</p>
                            <p class="text-xs text-[color:var(--nx-muted)]">Massnahmen sind konkrete Aufgaben innerhalb einer Phase.</p>
                            <x-nx-button variant="secondary" size="xs" wire:click="createAction" class="mt-1">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                Erste Massnahme erstellen
                            </x-nx-button>
                        </div>
                    </div>
                @else
                    @php
                        $actionsByPhase = $this->actions->groupBy(fn($a) => $a->sandbox_phase_id ?? 0);
                        $phaseMap = $this->phases->keyBy('id');
                    @endphp
                    <div class="space-y-5">
                        @foreach($actionsByPhase->sortKeys() as $phaseId => $phaseActions)
                            @php
                                $groupPhase = $phaseId ? ($phaseMap[$phaseId] ?? null) : null;
                                $groupColor = $groupPhase ? $groupPhase->phase_number->color() : 'var(--nx-muted)';
                                $groupShape = $groupPhase ? $groupPhase->phase_number->shape() : null;
                                $groupLabel = $groupPhase ? $groupPhase->phase_number->value . '. ' . $groupPhase->phase_number->shortLabel() : 'Ohne Phase';
                            @endphp
                            <div>
                                {{-- Phase group header --}}
                                <div class="flex items-center gap-2 mb-2">
                                    @if($groupPhase)
                                        <svg width="14" height="14" viewBox="0 0 16 16" style="color: {{ $groupColor }};">
                                            @switch($groupShape)
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
                                        @svg('heroicon-o-minus-circle', 'w-3.5 h-3.5 text-[color:var(--nx-muted)]')
                                    @endif
                                    <span class="text-[11px] font-bold uppercase tracking-wider" style="font-family: 'JetBrains Mono', monospace; color: {{ $groupColor }};">{{ $groupLabel }}</span>
                                    <span class="text-[10px] text-[color:var(--nx-muted)]">{{ $phaseActions->count() }}</span>
                                    <div class="flex-1 h-px" style="background: {{ $groupColor }}20;"></div>
                                    @if($groupPhase)
                                        <button wire:click="createAction({{ $groupPhase->id }})" class="text-[10px] font-medium transition-colors hover:opacity-100 opacity-60" style="color: {{ $groupColor }};">
                                            @svg('heroicon-o-plus', 'w-3 h-3 inline-block') Hinzufuegen
                                        </button>
                                    @endif
                                </div>

                                {{-- Actions in this phase --}}
                                <div class="space-y-1.5 ml-1.5 border-l-2 pl-3" style="border-color: {{ $groupColor }}20;">
                                    @foreach($phaseActions as $action)
                                        <div class="flex items-center gap-3 rounded-lg border border-black/5 bg-[color:var(--nx-surface)]/80 px-3 py-2.5 hover:bg-[color:var(--nx-surface)] transition-colors">
                                            <button wire:click="quickUpdateActionStatus({{ $action->id }}, '{{ $action->status->value === 'done' ? 'open' : 'done' }}')"
                                                    class="flex-shrink-0 {{ $action->status->value === 'done' ? 'text-[color:var(--nx-success)]' : 'text-[color:var(--nx-text)] hover:text-[color:var(--nx-success)]' }} transition-colors">
                                                @svg($action->status->value === 'done' ? 'heroicon-s-check-circle' : 'heroicon-o-circle-stack', 'w-5 h-5')
                                            </button>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium {{ $action->status->value === 'done' ? 'line-through opacity-60' : '' }}">{{ $action->title }}</span>
                                                    <x-nx-badge :variant="$action->status->color()" size="xs">{{ $action->status->label() }}</x-nx-badge>
                                                </div>
                                                <div class="flex items-center gap-3 text-xs text-[color:var(--nx-text)] mt-0.5">
                                                    @if($action->responsible)
                                                        <span>@svg('heroicon-o-user', 'w-3 h-3 inline-block') {{ $action->responsible }}</span>
                                                    @endif
                                                    @if($action->due_date)
                                                        <span class="{{ $action->due_date->isPast() && !in_array($action->status->value, ['done', 'cancelled']) ? 'text-[color:var(--nx-danger)] font-medium' : '' }}">
                                                            @svg('heroicon-o-calendar', 'w-3 h-3 inline-block') {{ $action->due_date->format('d.m.Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <button wire:click="editAction({{ $action->id }})" class="text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)] transition-colors">
                                                    @svg('heroicon-o-pencil', 'w-4 h-4')
                                                </button>
                                                <button wire:click="deleteAction({{ $action->id }})" wire:confirm="Massnahme wirklich löschen?" class="text-[color:var(--nx-text)] hover:text-[color:var(--nx-danger)] transition-colors">
                                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>


        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: STAKEHOLDER --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @elseif($activeTab === 'stakeholder')

            {{-- 20/60/20 Verteilungs-Karte --}}
            <div class="mb-6 rounded-xl border border-black/5 bg-[color:var(--nx-surface)]/60 backdrop-blur-sm p-5" x-data="{ expanded: false }">
                <div class="flex items-start gap-4">
                    {{-- Gauss-Kurve Visual --}}
                    <div class="flex-shrink-0 hidden sm:block">
                        <svg width="180" height="80" viewBox="0 0 180 80" class="opacity-90">
                            {{-- Gaussian curve --}}
                            <defs>
                                <linearGradient id="gaussGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:var(--nx-danger);stop-opacity:0.15"/>
                                    <stop offset="20%" style="stop-color:var(--nx-danger);stop-opacity:0.15"/>
                                    <stop offset="20%" style="stop-color:var(--nx-muted);stop-opacity:0.10"/>
                                    <stop offset="80%" style="stop-color:var(--nx-muted);stop-opacity:0.10"/>
                                    <stop offset="80%" style="stop-color:var(--nx-success);stop-opacity:0.15"/>
                                    <stop offset="100%" style="stop-color:var(--nx-success);stop-opacity:0.15"/>
                                </linearGradient>
                            </defs>
                            {{-- Fill under curve --}}
                            <path d="M5,75 C5,75 20,72 36,65 C52,45 65,15 90,8 C115,15 128,45 144,65 C160,72 175,75 175,75 L175,78 L5,78 Z"
                                  fill="url(#gaussGrad)"/>
                            {{-- Curve line --}}
                            <path d="M5,75 C5,75 20,72 36,65 C52,45 65,15 90,8 C115,15 128,45 144,65 C160,72 175,75 175,75"
                                  fill="none" stroke="var(--nx-muted)" stroke-width="1.5" opacity="0.4"/>
                            {{-- Zone separators --}}
                            <line x1="36" y1="5" x2="36" y2="78" stroke="var(--nx-muted)" stroke-width="0.5" stroke-dasharray="3,3" opacity="0.5"/>
                            <line x1="144" y1="5" x2="144" y2="78" stroke="var(--nx-muted)" stroke-width="0.5" stroke-dasharray="3,3" opacity="0.5"/>
                            {{-- Zone labels --}}
                            <text x="18" y="70" text-anchor="middle" fill="var(--nx-danger)" font-size="11" font-weight="700" font-family="'JetBrains Mono', monospace">20%</text>
                            <text x="90" y="40" text-anchor="middle" fill="var(--nx-muted)" font-size="11" font-weight="700" font-family="'JetBrains Mono', monospace">60%</text>
                            <text x="160" y="70" text-anchor="middle" fill="var(--nx-success)" font-size="11" font-weight="700" font-family="'JetBrains Mono', monospace">20%</text>
                        </svg>
                    </div>
                    {{-- Text --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="text-xs font-bold text-[color:var(--nx-text)] mb-1" style="font-family: 'JetBrains Mono', monospace;">Die 20 / 60 / 20 Regel</h3>
                        <p class="text-[11px] text-[color:var(--nx-text)] leading-relaxed">
                            Bei jeder Veraenderung verteilen sich Menschen gemaess der Normalverteilung:
                            <span class="font-medium text-[var(--nx-success)]">20% sind dafuer</span>,
                            <span class="font-medium text-[var(--nx-muted)]">60% sind abwartend</span>,
                            <span class="font-medium text-[var(--nx-danger)]">20% sind dagegen</span>.
                        </p>
                        <button @click="expanded = !expanded" class="text-[10px] mt-1.5 font-medium text-[color:var(--nx-accent)] hover:underline">
                            <span x-text="expanded ? 'Weniger anzeigen' : 'Strategie-Tipps anzeigen'"></span>
                        </button>
                        <div x-show="expanded" x-collapse x-cloak class="mt-2 space-y-1.5 text-[11px] text-[color:var(--nx-text)] leading-relaxed">
                            <div class="flex items-start gap-2 rounded p-2 bg-[var(--nx-success)]/5">
                                <span class="font-bold text-[var(--nx-success)] flex-shrink-0">20% Befuerworter:</span>
                                <span>Ihre Champions. Staerken und sichtbar machen. Lassen Sie diese die 60% ueberzeugen — Peer-Einfluss wirkt staerker als Top-Down.</span>
                            </div>
                            <div class="flex items-start gap-2 rounded p-2 bg-[var(--nx-muted)]/5">
                                <span class="font-bold text-[var(--nx-muted)] flex-shrink-0">60% Abwartende:</span>
                                <span>Die entscheidende Masse. Brauchen konkrete Beweise (Quick Wins), klare Vorteile und wenig Risiko. Nicht ueberreden — ueberzeugen durch Ergebnisse.</span>
                            </div>
                            <div class="flex items-start gap-2 rounded p-2 bg-[var(--nx-danger)]/5">
                                <span class="font-bold text-[var(--nx-danger)] flex-shrink-0">20% Skeptiker:</span>
                                <span>Nicht bekaempfen, aber auch nicht zu viel Energie investieren. Einige werden nie ueberzeugt — das ist normal. Fokus auf die Mitte.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-[color:var(--nx-text)]" style="font-family: 'JetBrains Mono', monospace;">Stakeholder-Map</h2>
                <x-nx-button variant="primary" size="xs" wire:click="createStakeholder">
                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                    Stakeholder
                </x-nx-button>
            </div>

            {{-- Bauhaus Influence/Support Matrix --}}
            @if($this->stakeholders->isNotEmpty())
                <div class="grid grid-cols-5 gap-px bg-black/5 rounded-lg overflow-hidden mb-6 text-xs">
                    {{-- Header row --}}
                    <div class="bg-[color:var(--nx-surface)]/80 p-2"></div>
                    @foreach(\Platform\Sandbox\Enums\StakeholderSupport::cases() as $support)
                        <div class="bg-[color:var(--nx-surface)]/80 p-2 text-center">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-[color:var(--nx-text)]" style="font-family: 'JetBrains Mono', monospace;">
                                {{ $support->label() }}
                            </span>
                        </div>
                    @endforeach

                    {{-- Matrix rows --}}
                    @foreach(array_reverse(\Platform\Sandbox\Enums\StakeholderInfluence::cases()) as $influence)
                        <div class="bg-[color:var(--nx-surface)]/80 p-2 flex items-center justify-end pr-3">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-[color:var(--nx-text)]" style="font-family: 'JetBrains Mono', monospace;">
                                {{ $influence->label() }}
                            </span>
                        </div>
                        @foreach(\Platform\Sandbox\Enums\StakeholderSupport::cases() as $support)
                            @php
                                $cellStakeholders = $this->stakeholders->filter(fn($s) =>
                                    ($s->influence_level->value ?? $s->influence_level) === $influence->value &&
                                    ($s->support_level->value ?? $s->support_level) === $support->value
                                );
                                // Zone coloring: red for high-influence+resistant/blocker, green for high-influence+champion/supporter
                                $isHighInfluence = in_array($influence->value, ['high', 'critical']);
                                $isResistant = in_array($support->value, ['resistant', 'blocker']);
                                $isChampion = in_array($support->value, ['champion', 'supporter']);
                                $zoneBg = 'bg-[color:var(--nx-surface)]/60';
                                if ($isHighInfluence && $isResistant) {
                                    $zoneBg = 'bg-[var(--nx-danger)]/10';
                                } elseif ($isHighInfluence && $isChampion) {
                                    $zoneBg = 'bg-[var(--nx-success)]/10';
                                }
                            @endphp
                            <div class="{{ $zoneBg }} p-1.5 min-h-[3rem]">
                                @foreach($cellStakeholders as $s)
                                    <button wire:click="editStakeholder({{ $s->id }})"
                                            class="block w-full text-left px-1.5 py-0.5 rounded text-[10px] mb-0.5 truncate
                                                   bg-black/5 hover:bg-black/10 transition-colors font-medium">
                                        {{ $s->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif

            {{-- Stakeholder list --}}
            @if($this->stakeholders->isEmpty())
                <p class="text-xs text-[color:var(--nx-text)]">Keine Stakeholder erfasst.</p>
            @else
                <div class="space-y-2">
                    @foreach($this->stakeholders as $stakeholder)
                        <div class="flex items-center gap-3 rounded-lg border border-black/5 bg-[color:var(--nx-surface)]/60 backdrop-blur-sm px-4 py-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{ $stakeholder->name }}</span>
                                    <x-nx-badge :variant="$stakeholder->influence_level->color()" size="xs">{{ $stakeholder->influence_level->label() }}</x-nx-badge>
                                    <x-nx-badge :variant="$stakeholder->support_level->color()" size="xs">{{ $stakeholder->support_level->label() }}</x-nx-badge>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-[color:var(--nx-text)] mt-0.5">
                                    @if($stakeholder->role)
                                        <span>{{ $stakeholder->role }}</span>
                                    @endif
                                    @if($stakeholder->entity)
                                        <span>@svg('heroicon-o-building-office', 'w-3 h-3 inline-block') {{ $stakeholder->entity->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button wire:click="editStakeholder({{ $stakeholder->id }})" class="text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)] transition-colors">
                                    @svg('heroicon-o-pencil', 'w-4 h-4')
                                </button>
                                <button wire:click="deleteStakeholder({{ $stakeholder->id }})" wire:confirm="Stakeholder wirklich löschen?" class="text-[color:var(--nx-text)] hover:text-[color:var(--nx-danger)] transition-colors">
                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif


        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: LOG --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @elseif($activeTab === 'log')
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-[color:var(--nx-text)]" style="font-family: 'JetBrains Mono', monospace;">Sandbox-Log</h2>
                <div class="flex items-center gap-2">
                    <x-nx-input-select
                        name="logTypeFilter"
                        wire:model.live="logTypeFilter"
                        :options="['note' => 'Notiz', 'milestone' => 'Meilenstein', 'decision' => 'Entscheidung', 'risk' => 'Risiko', 'blocker' => 'Blocker']"
                        :nullable="true"
                        nullLabel="Alle Typen"
                        size="xs"
                    />
                    <x-nx-input-select
                        name="logPhaseFilter"
                        wire:model.live="logPhaseFilter"
                        :options="$this->phases->pluck('phase_number')->mapWithKeys(fn($p) => [$this->phases->firstWhere('phase_number', $p)->id => 'Phase ' . $p->value . ': ' . $p->shortLabel()])->toArray()"
                        :nullable="true"
                        nullLabel="Alle Phasen"
                        size="xs"
                    />
                    <x-nx-button variant="primary" size="xs" wire:click="createLog">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                        Eintrag
                    </x-nx-button>
                </div>
            </div>

            @if($this->logs->isEmpty())
                <p class="text-xs text-[color:var(--nx-text)]">Keine Log-Einträge vorhanden.</p>
            @else
                <div class="space-y-3">
                    @foreach($this->logs as $log)
                        <div class="relative pl-6 border-l-2 border-black/10 pb-4 last:pb-0">
                            {{-- Bauhaus timeline shape --}}
                            <div class="absolute -left-[8px] top-0.5">
                                @if($log->phase)
                                    <svg width="14" height="14" viewBox="0 0 16 16" style="color: {{ $log->phase->phase_number->color() }};">
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
                                    <div class="w-3 h-3 rounded-full border-2 border-white bg-[color:var(--nx-{{ $log->type->color() }})]"></div>
                                @endif
                            </div>

                            <div class="rounded-lg border border-black/5 bg-[color:var(--nx-surface)]/60 backdrop-blur-sm p-4">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div class="flex items-center gap-2">
                                        @svg($log->type->icon(), 'w-4 h-4 text-[color:var(--nx-' . $log->type->color() . ')]')
                                        <span class="text-sm font-medium">{{ $log->title }}</span>
                                        <x-nx-badge :variant="$log->type->color()" size="xs">{{ $log->type->label() }}</x-nx-badge>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button wire:click="editLog({{ $log->id }})" class="text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)] transition-colors">
                                            @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                        </button>
                                        <button wire:click="deleteLog({{ $log->id }})" wire:confirm="Eintrag wirklich löschen?" class="text-[color:var(--nx-text)] hover:text-[color:var(--nx-danger)] transition-colors">
                                            @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                        </button>
                                    </div>
                                </div>

                                @if($log->content)
                                    <p class="text-xs text-[color:var(--nx-text)] mt-1 whitespace-pre-line">{{ $log->content }}</p>
                                @endif

                                <div class="flex items-center gap-3 text-[10px] text-[color:var(--nx-text)] mt-2">
                                    <span style="font-family: 'JetBrains Mono', monospace;">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                                    @if($log->user)
                                        <span>{{ $log->user->name }}</span>
                                    @endif
                                    @if($log->phase)
                                        <span class="flex items-center gap-1">
                                            <svg width="8" height="8" viewBox="0 0 16 16" style="color: {{ $log->phase->phase_number->color() }};">
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
                                            {{ $log->phase->phase_number->shortLabel() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif


        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB: EINSTELLUNGEN --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @elseif($activeTab === 'settings')
            <div class="max-w-2xl space-y-6">
                <div class="rounded-xl border border-white/40 bg-[color:var(--nx-surface)]/60 backdrop-blur-sm p-6">
                    <h3 class="text-sm font-semibold mb-4">Projekt-Details</h3>

                    <div class="space-y-4">
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

                        <x-nx-input-textarea wire:model="form.urgency_statement" label="Warum ist die Veränderung nötig?" rows="3" />
                        <x-nx-input-textarea wire:model="form.vision_statement" label="Strategische Vision" rows="3" />
                    </div>

                    @if($this->isDirty)
                        <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-white/40">
                            <x-nx-button variant="secondary" size="sm" wire:click="loadForm">Abbrechen</x-nx-button>
                            <x-nx-button variant="primary" size="sm" wire:click="save">Speichern</x-nx-button>
                        </div>
                    @endif
                </div>

                {{-- Danger zone --}}
                <div class="rounded-xl border border-[color:var(--nx-danger)]/20 bg-[color:var(--nx-danger)]/5 p-6">
                    <h3 class="text-sm font-semibold text-[color:var(--nx-danger)] mb-2">Gefahrenzone</h3>
                    <p class="text-xs text-[color:var(--nx-text)] mb-4">Das Löschen eines Projekts entfernt alle Phasen, Stakeholder, Maßnahmen und Log-Einträge.</p>
                    <x-nx-button variant="danger" size="sm" wire:click="delete" wire:confirm="Projekt und alle zugehoerigen Daten wirklich löschen?">
                        @svg('heroicon-o-trash', 'w-4 h-4')
                        Projekt löschen
                    </x-nx-button>
                </div>
            </div>
        @endif
        </div>
    </x-ui-page-container>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}

    {{-- Stakeholder Modal --}}
    <x-nx-modal wire:model="stakeholderModalShow" :title="$editingStakeholderId ? 'Stakeholder bearbeiten' : 'Neuer Stakeholder'">
        <form wire:submit="storeStakeholder" class="space-y-4">
            <x-nx-input-text wire:model="stakeholderForm.name" label="Name" required />
            <x-nx-input-text wire:model="stakeholderForm.role" label="Rolle" />
            <div class="grid grid-cols-2 gap-4">
                <x-nx-input-select
                    name="stakeholderForm.influence_level"
                    wire:model="stakeholderForm.influence_level"
                    label="Einfluss"
                    :options="['low' => 'Niedrig', 'medium' => 'Mittel', 'high' => 'Hoch', 'critical' => 'Kritisch']"
                />
                <x-nx-input-select
                    name="stakeholderForm.support_level"
                    wire:model="stakeholderForm.support_level"
                    label="Unterstützung"
                    :options="['champion' => 'Champion', 'supporter' => 'Unterstützer', 'neutral' => 'Neutral', 'resistant' => 'Widerständig', 'blocker' => 'Blocker']"
                />
            </div>
            <x-nx-input-select
                name="stakeholderForm.entity_id"
                wire:model="stakeholderForm.entity_id"
                label="Organisation (optional)"
                :options="$this->availableEntities->pluck('name', 'id')->toArray()"
                :nullable="true"
                nullLabel="Keine Zuordnung"
            />
            <x-nx-input-textarea wire:model="stakeholderForm.notes" label="Notizen" rows="3" />

            <div class="flex justify-end gap-2">
                <x-nx-button variant="secondary" size="sm" wire:click="$set('stakeholderModalShow', false)" type="button">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" size="sm" type="submit">{{ $editingStakeholderId ? 'Speichern' : 'Erstellen' }}</x-nx-button>
            </div>
        </form>
    </x-nx-modal>

    {{-- Action Modal --}}
    <x-nx-modal wire:model="actionModalShow" :title="$editingActionId ? 'Massnahme bearbeiten' : 'Neue Massnahme'">
        <form wire:submit="storeAction" class="space-y-4">
            <x-nx-input-text wire:model="actionForm.title" label="Titel" required />
            <x-nx-input-textarea wire:model="actionForm.description" label="Beschreibung" rows="3" />
            <div class="grid grid-cols-2 gap-4">
                <x-nx-input-select
                    name="actionForm.status"
                    wire:model="actionForm.status"
                    label="Status"
                    :options="['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'done' => 'Erledigt', 'cancelled' => 'Abgebrochen']"
                />
                <x-nx-input-text wire:model="actionForm.due_date" label="Fällig am" type="date" />
            </div>
            <x-nx-input-text wire:model="actionForm.responsible" label="Verantwortlich" />
            <x-nx-input-select
                name="actionForm.phase_id"
                wire:model="actionForm.phase_id"
                label="Phase (optional)"
                :options="$this->phases->mapWithKeys(fn($p) => [$p->id => 'Phase ' . $p->phase_number->value . ': ' . $p->phase_number->shortLabel()])->toArray()"
                :nullable="true"
                nullLabel="Keine Zuordnung"
            />

            <div class="flex justify-end gap-2">
                <x-nx-button variant="secondary" size="sm" wire:click="$set('actionModalShow', false)" type="button">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" size="sm" type="submit">{{ $editingActionId ? 'Speichern' : 'Erstellen' }}</x-nx-button>
            </div>
        </form>
    </x-nx-modal>

    {{-- Log Modal --}}
    <x-nx-modal wire:model="logModalShow" :title="$editingLogId ? 'Log-Eintrag bearbeiten' : 'Neuer Log-Eintrag'">
        <form wire:submit="storeLog" class="space-y-4">
            <x-nx-input-text wire:model="logForm.title" label="Titel" required />
            <x-nx-input-select
                name="logForm.type"
                wire:model="logForm.type"
                label="Typ"
                :options="['note' => 'Notiz', 'milestone' => 'Meilenstein', 'decision' => 'Entscheidung', 'risk' => 'Risiko', 'blocker' => 'Blocker']"
            />
            <x-nx-input-textarea wire:model="logForm.content" label="Inhalt" rows="4" />
            <x-nx-input-select
                name="logForm.phase_id"
                wire:model="logForm.phase_id"
                label="Phase (optional)"
                :options="$this->phases->mapWithKeys(fn($p) => [$p->id => 'Phase ' . $p->phase_number->value . ': ' . $p->phase_number->shortLabel()])->toArray()"
                :nullable="true"
                nullLabel="Keine Zuordnung"
            />

            <div class="flex justify-end gap-2">
                <x-nx-button variant="secondary" size="sm" wire:click="$set('logModalShow', false)" type="button">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" size="sm" type="submit">{{ $editingLogId ? 'Speichern' : 'Erstellen' }}</x-nx-button>
            </div>
        </form>
    </x-nx-modal>
</x-ui-page>
