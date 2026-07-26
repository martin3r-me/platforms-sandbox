<div>
    <div x-show="!collapsed" class="px-3 pt-3 pb-2 border-b border-[var(--nx-text)] mb-2">
        <span class="text-[10px] uppercase tracking-widest text-[color:var(--nx-muted)] font-medium">Sandbox</span>
    </div>

    <div x-show="!collapsed" class="px-2 mb-1">
        <a href="{{ route('sandbox.projects.index') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-1.5 rounded-md text-[13px] text-[color:var(--nx-faint)] hover:bg-[var(--nx-text)] hover:text-white transition-colors">
            @svg('heroicon-o-squares-2x2', 'w-4 h-4')
            <span>Projekte</span>
        </a>
        <a href="{{ route('sandbox.kotter') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-1.5 rounded-md text-[13px] text-[color:var(--nx-faint)] hover:bg-[var(--nx-text)] hover:text-white transition-colors">
            @svg('heroicon-o-academic-cap', 'w-4 h-4')
            <span>Kotter Guide</span>
        </a>
        <a href="{{ route('sandbox.hallo-welt') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-1.5 rounded-md text-[13px] text-[color:var(--nx-faint)] hover:bg-[var(--nx-text)] hover:text-white transition-colors">
            @svg('heroicon-o-hand-raised', 'w-4 h-4')
            <span>Hallo Welt</span>
        </a>
    </div>

    {{-- Collapsed View --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--nx-text)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('sandbox.projects.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[color:var(--nx-muted)] hover:text-white hover:bg-[var(--nx-text)] transition-colors" title="Projekte">
                @svg('heroicon-o-squares-2x2', 'w-5 h-5')
            </a>
            <a href="{{ route('sandbox.kotter') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[color:var(--nx-muted)] hover:text-white hover:bg-[var(--nx-text)] transition-colors" title="Kotter Guide">
                @svg('heroicon-o-academic-cap', 'w-5 h-5')
            </a>
            <a href="{{ route('sandbox.hallo-welt') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[color:var(--nx-muted)] hover:text-white hover:bg-[var(--nx-text)] transition-colors" title="Hallo Welt">
                @svg('heroicon-o-hand-raised', 'w-5 h-5')
            </a>
        </div>
    </div>

    {{-- Status-basierte Gruppierung --}}
    <div x-show="!collapsed" class="mt-2">
        @foreach($statusGroups as $group)
            <div x-data="{ open: localStorage.getItem('sandbox.status.{{ $group['status']->value }}') !== 'false' }"
                 wire:key="status-group-{{ $group['status']->value }}"
                 class="mb-1">
                {{-- Status-Header --}}
                <button type="button"
                        @click="open = !open; localStorage.setItem('sandbox.status.{{ $group['status']->value }}', open)"
                        class="flex items-center gap-2 w-full px-3 py-1.5 text-left hover:bg-[var(--nx-text)] rounded-md transition-colors group">
                    <span class="w-3 h-3 flex-shrink-0 flex items-center justify-center transition-transform text-[var(--nx-muted)]"
                          :class="open ? 'rotate-90' : ''">
                        @svg('heroicon-o-chevron-right', 'w-2.5 h-2.5')
                    </span>
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 bg-[color:var(--nx-{{ $group['color'] }})]"></span>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[color:var(--nx-muted)] group-hover:text-[color:var(--nx-faint)] transition-colors">
                        {{ $group['label'] }}
                    </span>
                    <span class="ml-auto text-[10px] tabular-nums text-[var(--nx-muted)] opacity-60">{{ $group['count'] }}</span>
                </button>

                {{-- Status-Inhalt --}}
                <div x-show="open" x-collapse class="ml-1">
                    {{-- Entity-Baum für diesen Status --}}
                    @foreach($group['linked'] as $typeGroup)
                        <x-ui-sidebar-list wire:key="status-{{ $group['status']->value }}-type-{{ $typeGroup['type_id'] }}" :label="$typeGroup['type_name']">
                            @foreach($typeGroup['entities'] as $entityNode)
                                @include('sandbox::livewire.partials.sidebar-entity-node', [
                                    'node' => $entityNode,
                                    'typeIcon' => $typeGroup['type_icon'] ?? null,
                                ])
                            @endforeach
                        </x-ui-sidebar-list>
                    @endforeach

                    {{-- Unverknüpfte Projekte dieses Status --}}
                    @if($group['unlinked']->isNotEmpty())
                        <x-ui-sidebar-list label="Unverknüpft">
                            @foreach($group['unlinked'] as $project)
                                <a wire:key="status-{{ $group['status']->value }}-unlinked-{{ $project->id }}"
                                   href="{{ route('sandbox.projects.show', $project) }}"
                                   wire:navigate
                                   title="{{ $project->name }}"
                                   class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 text-[var(--nx-text)] hover:text-[var(--nx-accent)] transition truncate">
                                    @svg('heroicon-o-arrows-right-left', 'w-3 h-3 flex-shrink-0 opacity-40')
                                    <span class="truncate text-[11px]">{{ $project->name }}</span>
                                </a>
                            @endforeach
                        </x-ui-sidebar-list>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Leer-Zustand --}}
        @if($statusGroups->isEmpty())
            <div class="px-3 py-1 text-xs text-[var(--nx-muted)]">
                Keine Sandbox-Projekte
            </div>
        @endif
    </div>
</div>
