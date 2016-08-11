<?php
namespace NearbyNotifier\Storage;

use NearbyNotifier\Entity\Pokemon;

/**
 * Interface Storage
 *
 * @package NearbyNotifier\Storage
 * @author Freek Post <freek@kobalt.blue>
 */
interface Storage
{

    /**
     * Checks if an encounter is new
     *
     * @param Pokemon $pokemon
     * @return bool
     */
    public function isNew(Pokemon $pokemon) : bool;

    /**
     * Cleans up expired encounters
     *
     * @return void
     */
    public function cleanup();
}
