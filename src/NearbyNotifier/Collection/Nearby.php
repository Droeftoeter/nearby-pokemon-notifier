<?php
namespace NearbyNotifier\Collection;

use Closure;
use NearbyNotifier\Entity\Pokemon;

/**
 * Class Nearby
 *
 * @package NearbyNotifier\Collection
 * @author Freek Post <freek@kobalt.blue>
 */
class Nearby
{

    /**
     * @var Pokemon[]
     */
    protected $pokemon = [];

    /**
     * @var Closure[]
     */
    protected $listeners = [];

    /**
     * Add a pokemon
     *
     * @param Pokemon $pokemon
     */
    public function addPokemon(Pokemon $pokemon)
    {
        if ($this->isNewEncounter($pokemon)) {
            $this->pokemon[$pokemon->getEncounterId()] = $pokemon;

            foreach ($this->listeners as $listener) {
                $listener($pokemon);
            }
        }

        $this->checkExpired();
    }

    /**
     * Register a listener
     *
     * @param Closure $closure
     */
    public function registerListener(Closure $closure)
    {
        $this->listeners[] = $closure;
    }

    /**
     * Check if new encounter
     *
     * @param Pokemon $pokemon
     * @return bool
     */
    protected function isNewEncounter(Pokemon $pokemon) : bool
    {
        return !array_key_exists($pokemon->getEncounterId(), $this->pokemon);
    }

    /**
     * Check expired state
     */
    protected function checkExpired()
    {
        foreach ($this->pokemon as $encounterId => $pokemon) {
            if ($pokemon->hasExpired()) {
                unset($this->pokemon[$encounterId]);
            }
        }
    }
}
