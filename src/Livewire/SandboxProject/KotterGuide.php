<?php

namespace Platform\Sandbox\Livewire\SandboxProject;

use Livewire\Component;
use Platform\Sandbox\Enums\SandboxPhaseNumber;

class KotterGuide extends Component
{
    public function render()
    {
        return view('sandbox::livewire.sandbox-project.kotter-guide', [
            'phases' => SandboxPhaseNumber::cases(),
        ])->layout('platform::layouts.app');
    }
}
