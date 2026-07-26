<?php

namespace Platform\Sandbox\Enums;

enum SandboxPhaseStatus: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Nicht gestartet',
            self::IN_PROGRESS => 'In Bearbeitung',
            self::COMPLETED => 'Abgeschlossen',
            self::BLOCKED => 'Blockiert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'muted',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::BLOCKED => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
