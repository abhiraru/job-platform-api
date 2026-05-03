<?php

namespace Modules\Profile\Enums;

enum ProfileAvailability: string
{
    case OpenToWork = 'open_to_work';
    case OpenToOffers = 'open_to_offers';
    case NotLooking = 'not_looking';

    public function label(): string
    {
        return match ($this) {
            self::OpenToWork => 'Open to work',
            self::OpenToOffers => 'Open to offers',
            self::NotLooking => 'Not looking',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
