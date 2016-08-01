<?php
namespace NearbyNotifier\Filter;

use NearbyNotifier\Entity\Pokemon;

/**
 * Interface Filter
 *
 * @package NearbyNotifier\Filter
 * @author Freek Post <freek@kobalt.blue>
 */
interface Filter
{
    /**
     * Checks if a WildPokemon matches the provided filter
     *
     * @param Pokemon $pokemon
     *
     * @return bool
     */
    public function matches(Pokemon $pokemon) : bool;
}
