<?php

use Illuminate\Support\Facades\Route;
use Platform\Sandbox\Livewire\HalloWelt;
use Platform\Sandbox\Livewire\SandboxProject\Index as SandboxProjectIndex;
use Platform\Sandbox\Livewire\SandboxProject\KotterGuide;
use Platform\Sandbox\Livewire\SandboxProject\Show as SandboxProjectShow;

// Module root → redirect to project list
Route::get('/', fn () => redirect()->route('sandbox.projects.index'))->name('sandbox.dashboard');

// Hallo Welt (schlichte Beispielseite)
Route::get('/hallo-welt', HalloWelt::class)->name('sandbox.hallo-welt');

// Kotter Guide (standalone reference page)
Route::get('/kotter', KotterGuide::class)->name('sandbox.kotter');

// Sandbox Projects
Route::get('/projects', SandboxProjectIndex::class)->name('sandbox.projects.index');
Route::get('/projects/{project}', SandboxProjectShow::class)->name('sandbox.projects.show');
