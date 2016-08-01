<?php
namespace NearbyNotifier\Enum;

/**
 * Class Rarity
 *
 * @package NearbyNotifier\Enum
 * @author Freek Post <freek@kobalt.blue>
 */
class Rarity
{
    const VERY_COMMON = 0;
    const COMMON = 1;
    const UNCOMMON = 2;
    const RARE = 3;
    const VERY_RARE = 4;
    const SPECIAL = 5;
    const EPIC = 6;
    const LEGENDARY = 7;

    /**
     * Get pokemon ids by rarity
     *
     * @param int $value
     *
     * @return int[]
     */
    public static function getPokemonIds(int $value) : array
    {
        switch($value) {
            case self::VERY_COMMON:
                return [10, 13, 16, 19, 23, 27, 29, 32, 41, 46, 48, 52, 56, 60, 66, 69, 74, 79, 81, 92, 98, 100, 118, 129, 133];
            case self::COMMON:
                return [1, 4, 7, 11, 14, 17, 20, 21, 24, 25, 28, 35, 37, 39, 42, 50, 53, 54, 58, 63, 67, 72, 75, 77, 82, 84, 86, 88, 90, 93, 96, 101, 102, 104, 106, 109, 111, 116, 120, 124];
            case self::UNCOMMON:
                return [12, 22, 30, 33, 38, 40, 44, 47, 51, 54, 57, 61, 64, 68, 73, 76, 85, 91, 97, 107, 108, 110, 113, 117, 119, 121, 123, 140, 147];
            case self::RARE:
                return [15, 18, 59, 70, 78, 80, 83, 89, 95, 99, 103, 112, 114, 122, 126, 127, 135, 136, 143];
            case self::VERY_RARE:
                return [26, 31, 34, 45, 49, 62, 65, 71, 87, 94, 105, 115, 125, 130, 131, 134, 141, 148];
            case self::SPECIAL:
                return [2, 5, 8, 137, 138, 142];
            case self::EPIC:
                return [3, 6, 9, 36, 128, 139, 149];
            case self::LEGENDARY:
                return [132, 144, 145, 146, 150, 151];
        }

        return [];
    }
}
