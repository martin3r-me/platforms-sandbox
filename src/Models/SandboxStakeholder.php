<?php

namespace Platform\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Sandbox\Enums\StakeholderInfluence;
use Platform\Sandbox\Enums\StakeholderSupport;
use Platform\Organization\Models\OrganizationEntity;
use Symfony\Component\Uid\UuidV7;

class SandboxStakeholder extends Model
{
    use SoftDeletes;

    protected $table = 'sandbox_stakeholders';

    protected $fillable = [
        'uuid', 'team_id', 'user_id', 'sandbox_project_id',
        'name', 'role', 'influence_level', 'support_level',
        'notes', 'entity_id', 'metadata',
    ];

    protected $casts = [
        'influence_level' => StakeholderInfluence::class,
        'support_level' => StakeholderSupport::class,
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
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function entity(): BelongsTo { return $this->belongsTo(OrganizationEntity::class, 'entity_id'); }
}
