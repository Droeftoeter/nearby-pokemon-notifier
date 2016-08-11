<?php
namespace NearbyNotifier\Storage;

use NearbyNotifier\Entity\Pokemon;

/**
 * Class Process
 *
 * @package NearbyNotifier\Storage
 * @author Freek Post <freek@kobalt.blue>
 */
class Process implements Storage
{

    /**
     * @var int[]
     */
    protected $encounters = [];

    /**
     * Check if new
     *
     * @param Pokemon $pokemon
     * @return bool
     */
    public function isNew(Pokemon $pokemon) : bool
    {
        if (array_key_exists($pokemon->getEncounterId(), $this->encounters)) {
            return false;
        }

        $this->encounters[$pokemon->getEncounterId()] = $pokemon->getExpiry()->getTimestamp();
        return true;
    }

    /**
     * Cleans up the set
     */
    public function cleanup()
    {
        foreach ($this->encounters as $encounterId => $expiry)
        {
            if ($expiry < time()) {
                unset($this->encounters[$encounterId]);
            }
        }
    }
}
