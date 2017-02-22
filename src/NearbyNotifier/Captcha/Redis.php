<?php
namespace NearbyNotifier\Captcha;

use Pokapi\Captcha\Solver;
use Predis\Client;

class Redis implements Solver
{

    /**
     * @var Client
     */
    protected $predis;

    /**
     * @var null|string
     */
    protected $prefix;

    /**
     * @var null|string
     */
    protected $channel;

    /**
     * @var int
     */
    protected $expiry = 60*60;

    /**
     * Redis constructor.
     *
     * @param Client $predis
     * @param string|null $prefix
     * @param string|null $channel
     */
    public function __construct(Client $predis, string $prefix = null, string $channel = null)
    {
        $this->predis  = $predis;
        $this->prefix  = $prefix;
        $this->channel = $channel;
    }

    /**
     * Solve a CAPTCHA
     *
     * @param string $challenge
     *
     * @return string
     */
    public function solve(string $challenge) : string
    {
        $key = $this->prefix . '_challenge_' . $challenge;
        $this->predis->setex($key, $this->expiry, $challenge);

        if ($this->channel !== null) {
            $this->predis->publish($this->channel, $key);
        }

        /* Loop and wait for result */
        while (true) {
            $value = $this->predis->get($key);
            if ($value !== $challenge) {
                return $value;
            }
            sleep(1);
        }

        return false;
    }
}
