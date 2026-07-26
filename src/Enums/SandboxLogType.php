<?php

namespace Platform\Sandbox\Enums;

enum SandboxLogType: string
{
    case NOTE = 'note';
    case MILESTONE = 'milestone';
    case DECISION = 'decision';
    case RISK = 'risk';
    case BLOCKER = 'blocker';

    public function label(): string
    {
        return match ($this) {
            self::NOTE => 'Notiz',
            self::MILESTONE => 'Meilenstein',
            self::DECISION => 'Entscheidung',
            self::RISK => 'Risiko',
            self::BLOCKER => 'Blocker',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOTE => 'muted',
            self::MILESTONE => 'success',
            self::DECISION => 'info',
            self::RISK => 'warning',
            self::BLOCKER => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NOTE => 'heroicon-o-document-text',
            self::MILESTONE => 'heroicon-o-flag',
            self::DECISION => 'heroicon-o-scale',
            self::RISK => 'heroicon-o-exclamation-triangle',
            self::BLOCKER => 'heroicon-o-no-symbol',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
