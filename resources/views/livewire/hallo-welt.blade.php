{{--
    Hallo Welt — schlichte Beispielseite (nx-Design-System)

    Seiten-Rahmen (Shell) wie im module-template: x-ui-page + Navbar/Actionbar +
    x-ui-page-container. Der Inhalt baut ausschließlich mit x-nx-* Komponenten.
--}}

<x-ui-page>
    {{-- Navbar --}}
    <x-slot name="navbar">
        <x-ui-page-navbar title="Hallo Welt" />
    </x-slot>

    {{-- Actionbar = Seitenkopf mit Breadcrumb --}}
    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Sandbox', 'icon' => 'beaker'],
            ['label' => 'Hallo Welt'],
        ]" />
    </x-slot>

    {{-- Hauptinhalt --}}
    <x-ui-page-container width="contained" spacing="space-y-6">
        <x-nx-card>
            <x-nx-section icon="heroicon-o-hand-raised" title="Hallo Welt"
                          description="Eine schlichte Beispielseite im Sandbox-Modul.">
                <p class="text-sm text-[color:var(--nx-text)]">Hallo Welt</p>
            </x-nx-section>
        </x-nx-card>
    </x-ui-page-container>
</x-ui-page>
