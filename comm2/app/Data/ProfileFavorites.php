<?php

declare(strict_types=1);

namespace App\Data;

class ProfileFavorites
{
    /**
     * Allowed values for favorite city
     *
     * @return array<string>
     */
    public static function cities(): array
    {
        return [
            'Los Santos',
            'San Fierro',
            'Las Venturas',
        ];
    }

    /**
     * Allowed values for favorite vehicle
     *
     * @return array<string>
     */
    public static function vehicles(): array
    {
        return [
            'Vehicle 1',
            'Vehicle 2',
            'Vehicle 3',
            'Vehicle 4',
            'Vehicle 5',
        ];
    }

    /**
     * Allowed values for favorite character
     *
     * @return array<string>
     */
    public static function characters(): array
    {
        return [
            'Character 1',
            'Character 2',
            'Character 3',
            'Character 4',
            'Character 5',
        ];
    }

    /**
     * Allowed values for favorite gang
     *
     * @return array<string>
     */
    public static function gangs(): array
    {
        return [
            'Gang 1',
            'Gang 2',
            'Gang 3',
            'Gang 4',
            'Gang 5',
        ];
    }

    /**
     * Allowed values for favorite weapon
     *
     * @return array<string>
     */
    public static function weapons(): array
    {
        return [
            'Weapon 1',
            'Weapon 2',
            'Weapon 3',
            'Weapon 4',
            'Weapon 5',
        ];
    }

    /**
     * Allowed values for favorite radio station
     *
     * @return array<string>
     */
    public static function radioStations(): array
    {
        return [
            'Radio Station 1',
            'Radio Station 2',
            'Radio Station 3',
            'Radio Station 4',
            'Radio Station 5',
        ];
    }
}
