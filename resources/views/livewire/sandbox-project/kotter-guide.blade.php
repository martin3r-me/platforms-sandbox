<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Kotter 8-Step Model" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sandbox-Projekte', 'href' => route('sandbox.projects.index')],
            ['label' => 'Kotter 8-Step Model'],
        ]">
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained">
        <div class="max-w-4xl mx-auto space-y-12">

            {{-- Hero --}}
            <div class="text-center py-8">
                <div class="flex justify-center gap-2 mb-6">
                    @foreach($phases as $phase)
                        <svg width="28" height="28" viewBox="0 0 40 40">
                            @switch($phase->shape())
                                @case('triangle')
                                    <polygon points="20,3 37,37 3,37" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('diamond')
                                    <polygon points="20,3 37,20 20,37 3,20" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('circle')
                                    <circle cx="20" cy="20" r="17" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('square')
                                    <rect x="3" y="3" width="34" height="34" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('hexagon')
                                    <polygon points="20,3 35,11 35,29 20,37 5,29 5,11" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('pentagon')
                                    <polygon points="20,3 37,15 30,37 10,37 3,15" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('octagon')
                                    <polygon points="13,3 27,3 37,13 37,27 27,37 13,37 3,27 3,13" fill="{{ $phase->color() }}"/>
                                    @break
                            @endswitch
                        </svg>
                    @endforeach
                </div>
                <h1 class="text-3xl font-black text-[color:var(--nx-text)] mb-3" style="font-family: 'JetBrains Mono', monospace;">
                    KOTTER'S 8 STUFEN
                </h1>
                <p class="text-base text-[color:var(--nx-muted)] max-w-2xl mx-auto leading-relaxed">
                    John P. Kotter entwickelte das 8-Stufen-Modell als Antwort auf die Frage, warum 70% aller Transformationen scheitern.
                    Jede Stufe baut auf der vorherigen auf &mdash; keine darf uebersprungen werden.
                </p>
                <p class="text-sm text-[color:var(--nx-muted)] mt-4 italic">
                    &ldquo;The rate of change is not going to slow down anytime soon. If anything, competition in most industries will probably speed up even more in the next few decades.&rdquo;
                </p>
                <p class="text-xs text-[color:var(--nx-muted)] mt-1">&mdash; John P. Kotter</p>
            </div>

            {{-- Divider --}}
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-[color:var(--nx-muted)]" style="font-family: 'JetBrains Mono', monospace;">Die 8 Stufen</span>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
            </div>

            {{-- Phase Cards --}}
            @php
                $kotterQuotes = [
                    1 => 'Ohne genuegend Dringlichkeit wird die Transformation nicht gelingen. Menschen finden tausend Gruende, nicht zu kooperieren.',
                    2 => 'Eine starke Fuehrungskoalition braucht die richtige Zusammensetzung, genuegend Vertrauen und ein gemeinsames Ziel.',
                    3 => 'Ohne eine sinnvolle Vision kann eine Transformation leicht in eine Liste verwirrender Projekte zerfallen.',
                    4 => 'Transformation ist keine Aufgabe fuer wenige. Sie braucht eine wachsende Armee von Freiwilligen.',
                    5 => 'Hindernisse zu beseitigen gibt den Menschen Kraft und sendet ein Signal: Wir meinen es ernst.',
                    6 => 'Kurzfristige Erfolge sind der Beweis, dass sich die Muehe lohnt. Sie nehmen Zynikern den Wind aus den Segeln.',
                    7 => 'Wer zu frueh den Sieg erklaert, verliert alles. Veraenderung braucht Jahre, nicht Monate.',
                    8 => 'Neue Verhaltensweisen muessen in der Unternehmenskultur verwurzelt werden, sonst verschwinden sie.',
                ];

                $kotterPrinciples = [
                    1 => ['Marktdaten und Wettbewerb analysieren', 'Krisen oder Chancen identifizieren', 'Ehrliche Diskussion anstossen'],
                    2 => ['Gruppe mit genuegend Macht zusammenstellen', 'Vertrauen und gemeinsames Ziel aufbauen', 'Teamarbeit foerdern'],
                    3 => ['Vision entwickeln, die die Transformation lenkt', 'Strategie zur Umsetzung formulieren', 'In 5 Minuten erklaerbar machen'],
                    4 => ['Vision ueber alle Kanaele kommunizieren', 'Fuehrungskoalition lebt die Vision vor', 'Bedenken ernst nehmen und adressieren'],
                    5 => ['Strukturen aendern, die die Vision untergraben', 'Risikobereitschaft und neue Ideen foerdern', 'Blocker im System beseitigen'],
                    6 => ['Sichtbare Verbesserungen planen', 'Erfolge erzielen und anerkennen', 'Menschen belohnen, die Erfolge ermoeglichen'],
                    7 => ['Glaubwuerdigkeit nutzen, um weitere Systeme zu aendern', 'Menschen einstellen und foerdern, die die Vision umsetzen', 'Prozess mit neuen Projekten beleben'],
                    8 => ['Zusammenhang zwischen Verhalten und Erfolg aufzeigen', 'Nachfolgeplanung an neuer Kultur ausrichten', 'Neue Normen in Onboarding verankern'],
                ];

                $stageLabels = [
                    1 => 'Voraussetzungen',
                    5 => 'Umsetzung & Verankerung',
                ];
            @endphp

            @foreach($phases as $idx => $phase)
                @php $num = $phase->value; @endphp

                {{-- Stage divider --}}
                @if($num === 1)
                    <div class="flex items-center gap-3 mt-4">
                        <div class="w-8 h-px bg-[color:var(--nx-muted)]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--nx-muted)]" style="font-family: 'JetBrains Mono', monospace;">I. Voraussetzungen schaffen</span>
                    </div>
                @elseif($num === 5)
                    <div class="flex items-center gap-3 mt-8">
                        <div class="w-8 h-px bg-[color:var(--nx-muted)]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--nx-muted)]" style="font-family: 'JetBrains Mono', monospace;">II. Umsetzen & Verankern</span>
                    </div>
                @endif

                {{-- Phase Card --}}
                <div class="relative rounded-2xl border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-8 overflow-hidden">
                    {{-- Background watermark --}}
                    <div class="absolute -right-8 -top-8 opacity-[0.04] pointer-events-none">
                        <svg width="200" height="200" viewBox="0 0 40 40">
                            @switch($phase->shape())
                                @case('triangle')
                                    <polygon points="20,3 37,37 3,37" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('diamond')
                                    <polygon points="20,3 37,20 20,37 3,20" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('circle')
                                    <circle cx="20" cy="20" r="17" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('square')
                                    <rect x="3" y="3" width="34" height="34" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('hexagon')
                                    <polygon points="20,3 35,11 35,29 20,37 5,29 5,11" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('pentagon')
                                    <polygon points="20,3 37,15 30,37 10,37 3,15" fill="{{ $phase->color() }}"/>
                                    @break
                                @case('octagon')
                                    <polygon points="13,3 27,3 37,13 37,27 27,37 13,37 3,27 3,13" fill="{{ $phase->color() }}"/>
                                    @break
                            @endswitch
                        </svg>
                    </div>

                    <div class="flex items-start gap-6 relative z-10">
                        {{-- Left: Number + Shape --}}
                        <div class="flex-shrink-0 flex flex-col items-center">
                            <span class="text-5xl font-black leading-none mb-2" style="font-family: 'JetBrains Mono', monospace; color: {{ $phase->color() }}; opacity: 0.15;">
                                {{ $num }}
                            </span>
                            <svg width="48" height="48" viewBox="0 0 40 40">
                                @switch($phase->shape())
                                    @case('triangle')
                                        <polygon points="20,3 37,37 3,37" fill="{{ $phase->color() }}"/>
                                        @break
                                    @case('diamond')
                                        <polygon points="20,3 37,20 20,37 3,20" fill="{{ $phase->color() }}"/>
                                        @break
                                    @case('circle')
                                        <circle cx="20" cy="20" r="17" fill="{{ $phase->color() }}"/>
                                        @break
                                    @case('square')
                                        <rect x="3" y="3" width="34" height="34" fill="{{ $phase->color() }}"/>
                                        @break
                                    @case('hexagon')
                                        <polygon points="20,3 35,11 35,29 20,37 5,29 5,11" fill="{{ $phase->color() }}"/>
                                        @break
                                    @case('pentagon')
                                        <polygon points="20,3 37,15 30,37 10,37 3,15" fill="{{ $phase->color() }}"/>
                                        @break
                                    @case('octagon')
                                        <polygon points="13,3 27,3 37,13 37,27 27,37 13,37 3,27 3,13" fill="{{ $phase->color() }}"/>
                                        @break
                                @endswitch
                            </svg>
                        </div>

                        {{-- Right: Content --}}
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-bold mb-1" style="color: {{ $phase->color() }};">
                                {{ $phase->label() }}
                            </h2>
                            <p class="text-sm text-[color:var(--nx-muted)] mb-4 leading-relaxed">
                                {{ $phase->description() }}
                            </p>

                            {{-- Quote --}}
                            <blockquote class="border-l-[3px] pl-4 mb-4 italic text-sm text-[color:var(--nx-muted)] leading-relaxed" style="border-color: {{ $phase->color() }}40;">
                                &ldquo;{{ $kotterQuotes[$num] }}&rdquo;
                            </blockquote>

                            {{-- Key principles --}}
                            <div>
                                <h4 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[color:var(--nx-muted)] mb-2" style="font-family: 'JetBrains Mono', monospace;">Kernprinzipien</h4>
                                <ul class="space-y-1.5">
                                    @foreach($kotterPrinciples[$num] as $principle)
                                        <li class="flex items-start gap-2 text-xs text-[color:var(--nx-muted)]">
                                            <svg width="12" height="12" viewBox="0 0 16 16" class="flex-shrink-0 mt-0.5" style="color: {{ $phase->color() }};">
                                                @switch($phase->shape())
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
                                            {{ $principle }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Footer --}}
            <div class="text-center py-8 border-t border-[color:var(--nx-line)]">
                <p class="text-xs text-[color:var(--nx-muted)] mb-2">Basierend auf</p>
                <p class="text-sm font-semibold text-[color:var(--nx-text)]">&ldquo;Leading Sandbox&rdquo; (1996) &amp; &ldquo;Accelerate&rdquo; (2014)</p>
                <p class="text-xs text-[color:var(--nx-muted)] mt-1">von John P. Kotter, Harvard Business School</p>
            </div>

        </div>
    </x-ui-page-container>
</x-ui-page>
