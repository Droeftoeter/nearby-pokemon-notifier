<?php
namespace NearbyNotifier\Filter;

use NearbyNotifier\Entity\Pokemon;
use Pokapi\Utility\Geo;

/**
 * Class Distance
 *
 * @package NearbyNotifier\Filter
 * @author Freek Post <freek@kobalt.blue>
 */
class Distance implements Filter
{

    /**
     * @var int
     */
    protected $minimum;

    /**
     * @var int
     */
    protected $maximum;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * Distance constructor.
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $minimum
     * @param int $maximum
     */
    public function __construct(float $latitude, float $longitude, int $minimum, int $maximum)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->minimum = $minimum;
        $this->maximum = $maximum;
    }

    /**
     * {@inheritDoc}
     */
    public function matches(Pokemon $pokemon) : bool
    {
        $distance = Geo::calculateDistance($this->latitude, $this->longitude, $pokemon->getLatitude(), $pokemon->getLongitude());
        return $distance >= $this->minimum && $distance <= $this->maximum;
    }
}
