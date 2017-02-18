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
    /* Rarity Filters, very incomplete for Gen II */
    const VERY_COMMON = 0;
    const COMMON = 1;
    const UNCOMMON = 2;
    const RARE = 3;
    const VERY_RARE = 4;
    const SPECIAL = 5;
    const EPIC = 6;
    const LEGENDARY = 7;

    /* Special Generation filters */
    const GEN_I = 100;
    const GEN_II = 101;

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
                return [1, 4, 7, 11, 14, 17, 20, 21, 24, 25, 28, 35, 37, 39, 42, 50, 53, 54, 58, 63, 67, 72, 75, 77, 82, 84, 86, 88, 90, 93, 96, 101, 102, 104, 106, 109, 111, 246, 116, 120, 218, 215, 124, 198, 228, 163, 161, 231, 162, 165, 166, 220, 167, 168, 222, 238, 239, 211, 213, 170, 177, 206, 204, 193, 191, 209, 236, 183, 234];
            case self::UNCOMMON:
                return [12, 22, 30, 33, 38, 40, 44, 47, 51, 55, 57, 61, 64, 68, 73, 76, 85, 91, 97, 107, 108, 110, 113, 117, 119, 121, 123, 140, 147, 164, 194, 187, 188, 189, 182, 247, 205, 226, 219, 192, 200, 229, 185, 202, 190, 210, 225, 237, 203, 221, 216, 241, 240, 235, 178, 223, 224, 171];
            case self::RARE:
                return [15, 18, 59, 70, 78, 80, 83, 89, 95, 99, 103, 112, 114, 122, 126, 127, 135, 136, 143, 172, 173, 179, 180, 181, 195, 208, 186, 199, 212, 214, 184, 207, 217, 248, 232, 227, 242];
            case self::VERY_RARE:
                return [26, 31, 34, 45, 49, 62, 65, 71, 87, 94, 105, 115, 125, 130, 131, 134, 141, 148, 169, 196, 197, 230];
            case self::SPECIAL:
                return [2, 5, 8, 137, 138, 142, 152, 153, 154, 155, 156, 157, 158, 159, 160, 175, 176, 201, 233];
            case self::EPIC:
                return [3, 6, 9, 36, 128, 139, 149];
            case self::LEGENDARY:
                return [132, 144, 145, 146, 150, 151, 243, 244, 245, 249, 250, 251];
            case self::GEN_I:
                return range(1, 151);
            case self::GEN_II:
                return range(152, 251);

        }

        return [];
    }
}
