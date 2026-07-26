<?php

namespace Platform\Sandbox\Enums;

enum StakeholderInfluence: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Niedrig',
            self::MEDIUM => 'Mittel',
            self::HIGH => 'Hoch',
            self::CRITICAL => 'Kritisch',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'muted',
            self::MEDIUM => 'info',
            self::HIGH => 'warning',
            self::CRITICAL => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
