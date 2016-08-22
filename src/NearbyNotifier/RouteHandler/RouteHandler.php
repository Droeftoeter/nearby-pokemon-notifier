<?php
namespace NearbyNotifier\Handler;

use NearbyNotifier\Entity\Pokemon;
use NearbyNotifier\Filter\Filter;
use Pokapi\Request\Position;

/**
 * Class RouteHandler
 *
 * @package NearbyNotifier\RouteHandler
 * @author Freek Post <freek@kobalt.blue>
 */
abstract class RouteHandler
{

    /**
     * @var mixed
     */
    protected $identifier;

    /**
     * RouteHandler constructor.
     *
     * @param mixed $identifier
     */
    public function __construct($identifier = null)
    {
        $this->identifier = $identifier;
    }

    /**
     * Set the identifier
     *
     * @param mixed $identifier
     *
     * @return RouteHandler
     */
    public function setIdentifier($identifier) : self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @param Position $position
     *
     * @return void
     */
    abstract public function walkTo(Position $position);

    /**
     * @return void
     */
    abstract public function stop();

    /**
     * @return void
     */
    abstract public function start();
}
