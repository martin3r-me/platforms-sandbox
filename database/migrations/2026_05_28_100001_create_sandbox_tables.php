<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── sandbox_projects ─────────────────────────────────────
        Schema::create('sandbox_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 255);
            $table->string('code', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->date('target_date')->nullable();
            $table->unsignedBigInteger('owner_entity_id')->nullable();
            $table->text('urgency_statement')->nullable();
            $table->text('vision_statement')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('owner_entity_id')
                ->references('id')->on('organization_entities')
                ->nullOnDelete();

            $table->index(['team_id', 'status']);
        });

        // ── sandbox_phases ───────────────────────────────────────
        Schema::create('sandbox_phases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sandbox_project_id')->constrained('sandbox_projects')->cascadeOnDelete();
            $table->tinyInteger('phase_number');
            $table->string('status')->default('not_started');
            $table->text('notes')->nullable();
            $table->string('responsible', 255)->nullable();
            $table->text('evidence')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['sandbox_project_id', 'phase_number']);
        });

        // ── sandbox_stakeholders ─────────────────────────────────
        Schema::create('sandbox_stakeholders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sandbox_project_id')->constrained('sandbox_projects')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('role', 255)->nullable();
            $table->string('influence_level')->default('medium');
            $table->string('support_level')->default('neutral');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('entity_id')
                ->references('id')->on('organization_entities')
                ->nullOnDelete();

            $table->index(['sandbox_project_id']);
        });

        // ── sandbox_actions ──────────────────────────────────────
        Schema::create('sandbox_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sandbox_project_id')->constrained('sandbox_projects')->cascadeOnDelete();
            $table->foreignId('sandbox_phase_id')->nullable()->constrained('sandbox_phases')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->date('due_date')->nullable();
            $table->string('responsible', 255)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sandbox_project_id', 'status']);
        });

        // ── sandbox_logs ─────────────────────────────────────────
        Schema::create('sandbox_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sandbox_project_id')->constrained('sandbox_projects')->cascadeOnDelete();
            $table->foreignId('sandbox_phase_id')->nullable()->constrained('sandbox_phases')->nullOnDelete();
            $table->string('type')->default('note');
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sandbox_project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_logs');
        Schema::dropIfExists('sandbox_actions');
        Schema::dropIfExists('sandbox_stakeholders');
        Schema::dropIfExists('sandbox_phases');
        Schema::dropIfExists('sandbox_projects');
    }
};
