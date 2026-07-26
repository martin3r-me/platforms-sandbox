<?php

namespace Platform\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Platform\Organization\Traits\HasPlannedPeriod;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Sandbox\Enums\SandboxProjectStatus;
use Platform\Sandbox\Enums\SandboxPhaseNumber;
use Platform\Organization\Models\OrganizationDimensionLink;
use Platform\Organization\Models\OrganizationEntity;
use Symfony\Component\Uid\UuidV7;

class SandboxProject extends Model
{
    use SoftDeletes, HasPlannedPeriod;

    protected $table = 'sandbox_projects';

    protected $fillable = [
        'uuid', 'team_id', 'user_id', 'name', 'code', 'description',
        'status', 'owner_entity_id',
        'urgency_statement', 'vision_statement',
        'metadata', 'completed_at',
    ];

    protected $casts = [
        'status' => SandboxProjectStatus::class,
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do { $uuid = UuidV7::generate(); } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
            if (! $model->user_id) { $model->user_id = Auth::id(); }
            if (! $model->team_id) { $model->team_id = Auth::user()?->currentTeamRelation?->getRootTeam()?->id; }
        });
    }

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function ownerEntity(): BelongsTo { return $this->belongsTo(OrganizationEntity::class, 'owner_entity_id'); }

    public function phases(): HasMany { return $this->hasMany(SandboxPhase::class, 'sandbox_project_id'); }
    public function stakeholders(): HasMany { return $this->hasMany(SandboxStakeholder::class, 'sandbox_project_id'); }
    public function actions(): HasMany { return $this->hasMany(SandboxAction::class, 'sandbox_project_id'); }
    public function logs(): HasMany { return $this->hasMany(SandboxLog::class, 'sandbox_project_id'); }

    public function dimensionLinks(): MorphMany
    {
        return $this->morphMany(OrganizationDimensionLink::class, 'linkable');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Create the 8 Kotter phases for this project.
     */
    public function createDefaultPhases(): void
    {
        foreach (SandboxPhaseNumber::cases() as $phase) {
            $this->phases()->create([
                'uuid' => UuidV7::generate(),
                'phase_number' => $phase->value,
                'status' => 'not_started',
            ]);
        }
    }

    /**
     * Get completion progress (completed phases / 8).
     */
    public function getProgressAttribute(): float
    {
        $total = $this->phases()->count();
        if ($total === 0) return 0;
        $completed = $this->phases()->where('status', 'completed')->count();
        return round($completed / $total, 2);
    }
}
