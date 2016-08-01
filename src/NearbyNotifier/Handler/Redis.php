<?php
namespace NearbyNotifier\Handler;

use NearbyNotifier\Entity\Pokemon;
use Predis\Client;

/**
 * Class Redis
 *
 * Super simple example to add Pokemon to Redis with an expiry
 *
 * @package NearbyNotifier\Handler
 * @author Freek Post <freek@kobalt.blue>
 */
class Redis extends Handler
{

    /**
     * @var Client
     */
    protected $predis;

    /**
     * Redis constructor.
     *
     * @param Client $predis
     * @param array $filters
     */
    public function __construct(Client $predis, array $filters = [])
    {
        parent::__construct($filters);
        $this->predis = $predis;
    }

    /**
     * Handle
     *
     * @param Pokemon $pokemon
     */
    public function handle(Pokemon $pokemon)
    {
        if ($pokemon->isNewEncounter()) {
            $this->addToRedis($pokemon);
        }
    }

    /**
     * Add to Redis
     *
     * @param Pokemon $pokemon
     */
    protected function addToRedis(Pokemon $pokemon)
    {
        $expiry = $pokemon->getExpiry()->getTimestamp() - time();
        $this->predis->setex($pokemon->getEncounterId(), $expiry, json_encode($pokemon->jsonSerialize()));
    }
}
