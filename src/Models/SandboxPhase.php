<?php

namespace Platform\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Sandbox\Enums\SandboxPhaseNumber;
use Platform\Sandbox\Enums\SandboxPhaseStatus;

class SandboxPhase extends Model
{
    protected $table = 'sandbox_phases';

    protected $fillable = [
        'uuid', 'sandbox_project_id', 'phase_number', 'status',
        'notes', 'responsible', 'evidence',
        'started_at', 'completed_at', 'metadata',
    ];

    protected $casts = [
        'phase_number' => SandboxPhaseNumber::class,
        'status' => SandboxPhaseStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(SandboxProject::class, 'sandbox_project_id'); }
    public function actions(): HasMany { return $this->hasMany(SandboxAction::class, 'sandbox_phase_id'); }
    public function logs(): HasMany { return $this->hasMany(SandboxLog::class, 'sandbox_phase_id'); }
}
