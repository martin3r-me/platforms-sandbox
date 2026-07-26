<?php

namespace Platform\Sandbox\Enums;

enum SandboxActionStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Offen',
            self::IN_PROGRESS => 'In Bearbeitung',
            self::DONE => 'Erledigt',
            self::CANCELLED => 'Abgebrochen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'muted',
            self::IN_PROGRESS => 'warning',
            self::DONE => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
