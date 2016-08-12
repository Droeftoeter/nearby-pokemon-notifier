<?php
namespace NearbyNotifier;

use POGOProtos\Map\Pokemon\WildPokemon;
use NearbyNotifier\Entity\Pokemon;
use POGOProtos\Networking\Responses\GetMapObjectsResponse;
use Pokapi\API;
use Pokapi\Authentication\Provider;
use Pokapi\Exception\Exception;
use Pokapi\Exception\NoResponse;

/**
 * Class Notifier
 *
 * @package NearbyNotifier
 * @author Freek Post <freek@kobalt.blue>
 */
class Notifier extends BaseNotifier
{

    /**
     * @var API
     */
    protected $api;

    /**
     * @var int
     */
    protected $encounters = 0;

    /**
     * Notifier constructor.
     *
     * @param Provider $authProvider
     * @param float $latitude
     * @param float $longitude
     * @param int $steps
     * @param float $radius
     */
    public function __construct(Provider $authProvider, float $latitude, float $longitude, int $steps = 5, float $radius = 0.04)
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

            $this->fetchObjects();
            usleep($this->stepInterval);
        }

        /* Clean up storage after each loop */
        $this->getStorage()->cleanup();
    }

    /**
     * Fetch map objects
     */
    public function fetchObjects()
    {
        try {
            return $this->handleResponse($this->api->getMapObjects());
        } catch(Exception $e) {
            // Log and retry.
            $this->getLogger()->error('An exception has been thrown while fetching map objects: {Exception}', [
                'Exception' => $e
            ]);
            usleep($this->stepInterval);
            return $this->fetchObjects();
        }
    }

    /**
     * Handle the response
     *
     * @param GetMapObjectsResponse $response
     */
    public function handleResponse(GetMapObjectsResponse $response)
    {
        /** @var \POGOProtos\Map\MapCell $mapCell */
        foreach ($response->getMapCellsList() as $mapCell) {
            if ($mapCell->getWildPokemonsList() && $mapCell->getWildPokemonsList()->count() > 0) {
                $wildPokemons = $mapCell->getWildPokemonsList();

                foreach ($wildPokemons as $wildPokemon) {
                    $this->addEncounter($wildPokemon);
                }
            }
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

            if ($this->encounters === 0) {
                $this->getLogger()->alert('No wild pokemons encountered. Softbanned?');
            }

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
            $this->api->initialize();
        } catch(NoResponse $e) {
            $this->getLogger()->error('Failed initialization, retrying...');
            sleep(5);
            return $this->init();
        }

        return true;
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
                $wildPokemon->getPokemonData()->getPokemonId()->value(),
                $wildPokemon->getTimeTillHiddenMs(),
                $wildPokemon->getSpawnPointId(),
                $wildPokemon->getLastModifiedTimestampMs()
            );

            $isNew = $this->getStorage()->isNew($pokemon);

            /* Log */
            $this->getLogger()->info("{State} encounter with {Name} found at {Latitude}, {Longitude}. Expires in {Expiry} minutes.", [
                'State' => $isNew ? 'New' : 'Existing',
                'Name' => $pokemon->getName(),
                'Latitude'  => $pokemon->getLatitude(),
                'Longitude' => $pokemon->getLongitude(),
                'Expiry' => $pokemon->getExpiryInMinutes()
            ]);

            /* Notify listeners */
            foreach ($this->handlers as $handler) {
                $handler->notify($pokemon, $isNew);
            }
        }

        $this->encounters++;
    }
}
