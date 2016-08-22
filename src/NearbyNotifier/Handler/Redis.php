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
     * @var string
     */
    protected $prefix;

    /**
     * @var string|null
     */
    protected $channel;

    /**
     * Redis constructor.
     *
     * @param Client $predis
     * @param array $filters
     * @param string $prefix
     * @param string $channel
     */
    public function __construct(Client $predis, array $filters = [], string $prefix = null, string $channel = null)
    {
        parent::__construct($filters);
        $this->predis = $predis;
        $this->channel = $channel;
        $this->prefix = $prefix;
    }

    /**
     * Handle
     *
     * @param Pokemon $pokemon
     * @param bool $newEncounter
     */
    public function handle(Pokemon $pokemon, bool $newEncounter)
    {
        $expiry = $pokemon->getExpiry()->getTimestamp() - time();
        $serialized = json_encode($pokemon);

        /* Set with expiration */
        $this->predis->setex($this->prefix . '_' . $pokemon->getEncounterId(), $expiry, $serialized);

        /* Publish on channel if set */
        if ($this->channel !== null) {
            $this->predis->publish($this->channel, $serialized);
        }
    }
}
