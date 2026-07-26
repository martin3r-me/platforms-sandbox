<?php

namespace Platform\Sandbox\Livewire;

use Livewire\Component;

class HalloWelt extends Component
{
    public function render()
    {
        return view('sandbox::livewire.hallo-welt')
            ->layout('platform::layouts.app');
    }
}
