<?php
namespace NearbyNotifier;
use NearbyNotifier\Entity\Pokemon;
use NearbyNotifier\Handler\Handler;
use POGOProtos\Map\Pokemon\WildPokemon;
use Pokapi\API;
use Pokapi\Authentication\Provider;
use Pokapi\Exception\NoResponse;
use Pokapi\Utility\Geo;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Notifier
 *
 * @package NearbyNotifier
 * @author Freek Post <freek@kobalt.blue>
 */
class Notifier extends BaseNotifier
{

    /**
     * @var Pokemon[]
     */
    protected $encounters = [];

    /**
     * @var API
     */
    protected $api;

    /**
     * Notifier constructor.
     *
     * @param Provider $authProvider
     * @param float $latitude
     * @param float $longitude
     * @param int $steps
     * @param float $radius
     */
    public function __construct(Provider $authProvider, float $latitude, float $longitude, int $steps = 5, float $radius = 0.07)
    {
        parent::__construct($latitude, $longitude, $steps, $radius);
        $this->api = new API($authProvider, $latitude, $longitude);
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        /* Loop */
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
                usleep($this->loopInterval);
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
    }

    /**
     * {@inheritDoc}
     */
    public function runContinously()
    {
        while(true) {
            $this->run();
            $this->getLogger()->debug('Waiting {LoopInterval} seconds before restarting...', [
                'LoopInterval' => round($this->loopInterval/1000/1000)
            ]);
            usleep($this->loopInterval);
        }
    }

    /**
     * Call these first
     *
     * If we don't then the Pokemon Go API will not recognize new accounts.
     */
    public function init()
    {
        try {
            $this->api->getPlayerData();
            $this->api->getInventory();
            $this->api->downloadSettings();
        } catch(NoResponse $e) {
            $this->getLogger()->error('Failed initialization, retrying...');
            sleep(5);
            return $this->init();
        }
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

            /* Cleanup */
            $this->checkExpired();
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
}
