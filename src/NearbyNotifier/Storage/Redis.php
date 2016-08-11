<?php
namespace NearbyNotifier\Storage;

use NearbyNotifier\Entity\Pokemon;
use Predis\Client;

/**
 * Class Redis
 * Very crude
 *
 * @package NearbyNotifier\Storage
 * @author Freek Post <freek@kobalt.blue>
 */
class Redis implements Storage
{

    /**
     * @var Client
     */
    protected $predis;

    /**
     * @var string
     */
    protected $setName;

    /**
     * Redis constructor.
     *
     * @param Client $predis
     * @param string $hashSetName
     */
    public function __construct(Client $predis, string $hashSetName = 'encounters')
    {
        $this->predis = $predis;
        $this->setName = $hashSetName;
    }

    /**
     * Check if new
     *
     * @param Pokemon $pokemon
     * @return bool
     */
    public function isNew(Pokemon $pokemon) : bool
    {
        if ($this->predis->hexists($this->setName, $pokemon->getEncounterId()) === 1) {
            return false;
        }

        $this->predis->hset($this->setName, $pokemon->getEncounterId(), $pokemon->getExpiry()->getTimestamp());
        return true;
    }

    /**
     * Cleans up the set
     */
    public function cleanup()
    {
        foreach ($this->predis->hgetall($this->setName) as $encounterId => $expiry)
        {
            if ($expiry < time()) {
                $this->predis->hdel($this->setName, $encounterId);
            }
        }
    }
}
