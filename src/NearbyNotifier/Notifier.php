<?php
namespace NearbyNotifier;
use NearbyNotifier\Entity\Pokemon;
use NearbyNotifier\Handler\Handler;
use POGOProtos\Map\Pokemon\WildPokemon;
use Pokapi\API;
use Pokapi\Authentication\Provider;
use Pokapi\Utility\Geo;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Notifier
 *
 * @package NearbyNotifier
 * @author Freek Post <freek@kobalt.blue>
 */
class Notifier
{

    /**
     * @var Pokemon[]
     */
    protected $encounters = [];

    /**
     * @var Handler[]
     */
    protected $handlers = [];

    /**
     * @var API
     */
    protected $api;

    /**
     * @var array
     */
    protected $steps;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $loopInterval = 60000 * 1000; // 60 seconds.

    /**
     * @var int
     */
    protected $stepInterval = 1000 * 1000; // 1 second.

    public function __construct(Provider $authProvider, float $latitude, float $longitude, int $steps = 5)
    {
        $this->api = new API($authProvider, $latitude, $longitude);
        $this->steps = Geo::generateSteps($latitude, $longitude, $steps);
    }

    /**
     * Attach a handler
     *
     * @param Handler $handler
     * @return Notifier
     */
    public function attach(Handler $handler) : self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * Run the notifier
     */
    public function run()
    {
        while (true) {
            foreach ($this->steps as $index => $step) {
                $this->api->setLocation($step[0], $step[1]);

                /* Log */
                $this->getLogger()->debug("Walking {Step} of {Steps}", [
                    'Step' => $index+1,
                    'Steps' => count($this->steps)
                ]);

                try {
                    $response = $this->api->getMapObjects();
                } catch(\Pokapi\Exception\Exception $e) {
                    // Log and skip...
                    $this->getLogger()->error('An exception has been thrown while fetching map objects: {Exception}', [
                        'Exception' => $e
                    ]);
                    continue;
                }

                /** @var \POGOProtos\Map\MapCell $mapCell */
                foreach ($response->getMapCellsArray() as $mapCell) {
                    if ($mapCell->getWildPokemonsCount() > 0) {
                        $wildPokemons = $mapCell->getWildPokemonsArray();

                        /** @var \POGOProtos\Map\Pokemon\WildPokemon $wildPokemon */
                        foreach ($wildPokemons as $wildPokemon) {
                            $this->addEncounter($wildPokemon);
                        }
                    }
                }
                usleep($this->stepInterval);
            }

            $this->getLogger()->debug('Waiting {LoopInterval} seconds before restarting...', [
                'LoopInterval' => round($this->loopInterval/1000)
            ]);
            usleep($this->loopInterval);
        }
    }

    /**
     * Set the logger
     *
     * @param LoggerInterface $logger
     *
     * @return Notifier
     */
    public function setLogger(LoggerInterface $logger) : self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Set the step interval in milliseconds
     *
     * @param int $interval
     * @return $this
     */
    public function setStepInterval(int $interval)
    {
        $this->stepInterval = round($interval * 1000);
        return $this;
    }

    /**
     * Set the loop interval in milliseconds
     *
     * @param int $interval
     * @return Notifier
     */
    public function setLoopInterval(int $interval) : self
    {
        $this->loopInterval = round($interval * 1000);
        return $this;
    }

    /**
     * Adds an encounter to the internal array
     *
     * @param WildPokemon $wildPokemon
     */
    protected function addEncounter(WildPokemon $wildPokemon)
    {
        if ($wildPokemon->getTimeTillHiddenMs() > 0) {

            /* Create entity */
            $pokemon = new Pokemon(
                $wildPokemon->getEncounterId(),
                $wildPokemon->getLatitude(),
                $wildPokemon->getLongitude(),
                $wildPokemon->getPokemonData()->getPokemonId(),
                $wildPokemon->getTimeTillHiddenMs(),
                $this->hasEncounter($wildPokemon->getEncounterId())
            );

            /* Log */
            $this->getLogger()->info("{State} encounter with {Name} found at {Latitude}, {Longitude}", [
                'State' => $pokemon->isNewEncounter() ? 'New' : 'Existing',
                'Name' => $pokemon->getName(),
                'Latitude'  => $pokemon->getLatitude(),
                'Longitude' => $pokemon->getLongitude()
            ]);

            /* Update internal array */
            $this->encounters[$wildPokemon->getEncounterId()] = $pokemon;

            /* Notify listeners */
            foreach ($this->handlers as $handler) {
                $handler->notify($pokemon);
            }
        }
    }

    /**
     * Check if we already have a encounter
     *
     * @param int $encounterId
     * @return bool
     */
    protected function hasEncounter(int $encounterId) : bool
    {
        return !array_key_exists($encounterId, $this->encounters);
    }

    /**
     * Check expired state
     */
    protected function checkExpired()
    {
        foreach ($this->encounters as $encounterId => $pokemon) {
            if ($pokemon->hasExpired()) {
                unset($this->encounters[$encounterId]);
            }
        }
    }

    /**
     * Get the logger
     *
     * @return LoggerInterface
     */
    protected function getLogger() : LoggerInterface
    {
        if (!$this->logger instanceof LoggerInterface) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}
