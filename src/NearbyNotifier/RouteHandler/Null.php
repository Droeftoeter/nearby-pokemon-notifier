<?php
namespace NearbyNotifier\Handler;

use Pokapi\Request\Position;

/**
 * Class Null
 *
 * @package NearbyNotifier\RouteHandler
 * @author Freek Post <freek@kobalt.blue>
 */
class Null extends RouteHandler
{

    /**
     * Null constructor.
     *
     * @param mixed  $identifier
     */
    public function __construct($identifier = null)
    {
        parent::__construct($identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function walkTo(Position $position)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function stop()
    {
    }
}
