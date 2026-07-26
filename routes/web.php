<?php

use Illuminate\Support\Facades\Route;
use Platform\Sandbox\Livewire\Dashboard;

// Modul-Dashboard (Startseite)
Route::get('/', Dashboard::class)->name('sandbox.dashboard');
