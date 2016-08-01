<?php
namespace NearbyNotifier\Filter;

use NearbyNotifier\Entity\Pokemon;
use NearbyNotifier\Enum\Rarity as RarityEnum;

/**
 * Filters based on Pokemon rarity
 *
 * @package NearbyNotifier\Filter
 */
class Rarity implements Filter
{

    /**
     * @var int[]
     */
    protected $pokemonIds = [];

    /**
     * Rarity constructor.
     *
     * @param array $rarities
     */
    public function __construct(array $rarities)
    {
        foreach ($rarities as $rarity)
        {
            $this->pokemonIds = array_unique(
                array_merge($this->pokemonIds, RarityEnum::getPokemonIds($rarity))
            );
        }
    }

    /**
     * Include a pokemon
     *
     * @param int $pokemonId
     * @return Rarity
     */
    public function includeId(int $pokemonId) : self
    {
        if (!in_array($pokemonId, $this->pokemonIds)) {
            $this->pokemonIds[] = $pokemonId;
        }
        return $this;
    }

    /**
     * Exclude a pokemon
     *
     * @param int $pokemonId
     *
     * @return Rarity
     */
    public function excludeId(int $pokemonId) : self
    {
        if (in_array($pokemonId, $this->pokemonIds)) {
            unset($this->pokemonIds[array_search($pokemonId, $this->pokemonIds)]);
            sort($this->pokemonIds);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function matches(Pokemon $pokemon) : bool
    {
        return in_array($pokemon->getPokemonId(), $this->pokemonIds);
    }
}
