<?php
namespace NearbyNotifier;

use NearbyNotifier\Captcha\Handler;
use NearbyNotifier\Exception\FlaggedAccountException;
use POGOProtos\Map\Pokemon\WildPokemon;
use NearbyNotifier\Entity\Pokemon;
use POGOProtos\Networking\Responses\GetMapObjectsResponse;
use Pokapi\API;
use Pokapi\Authentication;
use Pokapi\Hashing;
use Pokapi\Exception\Exception;
use Pokapi\Exception\NoResponse;
use Pokapi\Request\DeviceInfo;
use Pokapi\Request\Position;
use Pokapi\Version\Latest;
use Protobuf\Collection;
use Psr\Log\LoggerInterface;

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
     * @var Handler|null
     */
    protected $captcha;

    /**
     * Notifier constructor.
     *
     * @param Hashing\Provider        $hashingProvider
     * @param Authentication\Provider $authProvider
     * @param DeviceInfo              $deviceInfo
     * @param float                   $latitude
     * @param float                   $longitude
     * @param int                     $steps
     * @param float                   $radius
     * @param LoggerInterface|null    $logger
     * @param Handler|null            $captchaHandler
     */
    public function __construct(
        Hashing\Provider $hashingProvider,
        Authentication\Provider $authProvider,
        DeviceInfo $deviceInfo,
        float $latitude,
        float $longitude,
        int $steps = 5,
        float $radius = 0.04,
        LoggerInterface $logger = null,
        Handler $captchaHandler = null
    ) {
        parent::__construct($latitude, $longitude, $steps, $radius, $logger);
        $position = new Position($latitude, $longitude, 12.0);
        $version  = new Latest();
        $this->api = new API($version, $authProvider, $position, $deviceInfo, $hashingProvider, $logger);

        $this->captcha = $captchaHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        /* Start */
        $this->getRouteHandler()->start();

        /* Captcha */
        $this->checkChallenge();

        /* Loop */
        foreach ($this->steps as $index => $step) {
            $newPosition = new Position($step[0], $step[1], 12.0);
            $randomized = $newPosition->createRandomized();
            $this->api->setPosition($randomized);

            /* Route */
            $this->getRouteHandler()->walkTo($randomized);

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
        $this->getRouteHandler()->stop();
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
        if ($response->getMapCellsList() instanceof Collection) {
            foreach ($response->getMapCellsList() as $mapCell) {
                if ($mapCell->getWildPokemonsList() && $mapCell->getWildPokemonsList()->count() > 0) {
                    $wildPokemons = $mapCell->getWildPokemonsList();

                    foreach ($wildPokemons as $wildPokemon) {
                        $this->addEncounter($wildPokemon);
                    }
                }
            }
        } else {
            $this->getLogger()->warning("Received empty MapCellsList");
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
     * Check if account is flagged
     *
     * @throws FlaggedAccountException
     */
    protected function checkChallenge() : bool
    {
        $challenge    = $this->api->checkChallenge();
        $challengeUrl = trim($challenge->getChallengeUrl());

        if ($challenge->getShowChallenge() || !empty($challengeUrl)) {
            $this->getLogger()->warning("Account is flagged for CAPTCHA.");
            if ($this->captcha === null) {
                throw new FlaggedAccountException("Account is flagged, and no CAPTCHA solvers are set. Challenge URL: " . $challengeUrl);
            }

            $this->getLogger()->debug("Sending challenge {Challenge} to solver.", array('Challenge' => $challengeUrl));
            $token = $this->captcha->solve($challengeUrl);
            $this->getLogger()->debug("Received solution token {Token}", array('Token' => $token));
            $this->api->verifyChallenge($token);

            return true;
        }

        return false;
    }

    /**
     * Adds an encounter to the internal array
     *
     * @param WildPokemon $wildPokemon
     */
    protected function addEncounter(WildPokemon $wildPokemon)
    {
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

        $this->encounters++;
    }
}
