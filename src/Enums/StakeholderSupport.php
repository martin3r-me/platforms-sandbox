<?php

namespace Platform\Sandbox\Enums;

enum StakeholderSupport: string
{
    case CHAMPION = 'champion';
    case SUPPORTER = 'supporter';
    case NEUTRAL = 'neutral';
    case RESISTANT = 'resistant';
    case BLOCKER = 'blocker';

    public function label(): string
    {
        return match ($this) {
            self::CHAMPION => 'Champion',
            self::SUPPORTER => 'Unterstützer',
            self::NEUTRAL => 'Neutral',
            self::RESISTANT => 'Widerständig',
            self::BLOCKER => 'Blocker',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CHAMPION => 'success',
            self::SUPPORTER => 'info',
            self::NEUTRAL => 'muted',
            self::RESISTANT => 'warning',
            self::BLOCKER => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
