<?php

namespace Platform\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Sandbox\Enums\SandboxLogType;
use Symfony\Component\Uid\UuidV7;

class SandboxLog extends Model
{
    use SoftDeletes;

    protected $table = 'sandbox_logs';

    protected $fillable = [
        'uuid', 'team_id', 'user_id', 'sandbox_project_id', 'sandbox_phase_id',
        'type', 'title', 'content', 'metadata',
    ];

    protected $casts = [
        'type' => SandboxLogType::class,
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = UuidV7::generate();
            }
            if (! $model->user_id) { $model->user_id = Auth::id(); }
        });
    }

    public function project(): BelongsTo { return $this->belongsTo(SandboxProject::class, 'sandbox_project_id'); }
    public function phase(): BelongsTo { return $this->belongsTo(SandboxPhase::class, 'sandbox_phase_id'); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
