<?php

namespace Platform\Sandbox\Enums;

enum SandboxProjectStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Entwurf',
            self::ACTIVE => 'Aktiv',
            self::PAUSED => 'Pausiert',
            self::COMPLETED => 'Abgeschlossen',
            self::CANCELLED => 'Abgebrochen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'muted',
            self::ACTIVE => 'success',
            self::PAUSED => 'warning',
            self::COMPLETED => 'info',
            self::CANCELLED => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
