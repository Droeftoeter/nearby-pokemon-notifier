<?php
namespace NearbyNotifier\Handler;

use NearbyNotifier\Entity\Pokemon;
use NearbyNotifier\Filter\Filter;

/**
 * Class Handler
 *
 * @package NearbyNotifier\Handler
 * @author Freek Post <freek@kobalt.blue>
 */
abstract class Handler
{

    /**
     * @var Filter[]
     */
    protected $filters = [];

    /**
     * Listener constructor.
     *
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        foreach ($filters as $filter)
        {
            $this->addFilter($filter);
        }
    }

    /**
     * Notifies of Pokemon
     *
     * @param Pokemon $pokemon
     *
     * @return void
     */
    public function notify(Pokemon $pokemon)
    {
        foreach ($this->filters as $filter)
        {
            if (!$filter->matches($pokemon)) {
                return;
            }
        }

        $this->handle($pokemon);
    }

    /**
     * Add a filter
     *
     * @param Filter $filter
     *
     * @return Handler
     */
    public function addFilter(Filter $filter) : self
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * Handle Pokemon that passed the filters
     *
     * @param Pokemon $pokemon
     *
     * @return void
     */
    abstract protected function handle(Pokemon $pokemon);
}
