<?php
namespace NearbyNotifier\RouteHandler;

use Pokapi\Request\Position;
use Predis\Client;

/**
 * Class Redis
 *
 * Super simple example to add Pokemon to Redis with an expiry
 *
 * @package NearbyNotifier\RouteHandler
 * @author Freek Post <freek@kobalt.blue>
 */
class Redis extends RouteHandler
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
     * @param string $channel
     * @param string $prefix
     * @param mixed  $identifier
     */
    public function __construct(Client $predis, string $channel = 'walkers', string $prefix = null, $identifier = null)
    {
        parent::__construct($identifier);
        $this->predis = $predis;
        $this->channel = $channel;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function walkTo(Position $position)
    {
        $this->predis->publish($this->channel, [
            'identifier'  => $this->identifier,
            'operation'   => 'walk',
            'coordinates' => [
                'lat'     => $position->getLatitude(),
                'lng'     => $position->getLongitude()
            ]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function start()
    {
        $this->predis->publish($this->channel, [
            'identifier'  => $this->identifier,
            'operation'   => 'start'
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function stop()
    {
        $this->predis->publish($this->channel, [
            'identifier'  => $this->identifier,
            'operation'   => 'stop'
        ]);
    }
}
