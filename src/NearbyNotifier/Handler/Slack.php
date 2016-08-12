<?php
namespace NearbyNotifier\Handler;

use Maknz\Slack\Client;
use NearbyNotifier\Entity\Pokemon;
use Pokapi\Utility\Geo;

/**
 * Class Slack
 *
 * @package NearbyNotifier\Handler
 * @author Freek Post <freek@kobalt.blue>
 */
class Slack extends Handler
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * Slack constructor.
     *
     * @param string $webhook
     * @param float $latitude
     * @param float $longitude
     * @param array $filters
     */
    public function __construct(string $webhook, float $latitude, float $longitude, array $filters = array())
    {
        parent::__construct($filters);

        $this->client = new Client($webhook, [
            'username' => 'Ash Ketchum'
        ]);

        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Notify slack of new pokemon!
     *
     * @param Pokemon $pokemon
     * @param bool $newEncounter
     */
    protected function handle(Pokemon $pokemon, bool $newEncounter)
    {
        if ($newEncounter) {
            $this->client
                ->withIcon('https://pbs.twimg.com/profile_images/1206423764/Ashbot.PNG')
                ->attach([
                    'fallback' => 'Er zit een ' . $pokemon->getName() . ' in de buurt voor de komende ' . $pokemon->getExpiryInMinutes() . ' minuten.',
                    'title' => $pokemon->getName(),
                    'title_link' => $this->getMapLink($pokemon),
                    "image_url" => $this->getStaticMapImage($pokemon),
                    "thumb_url" => "http://n.kobalt.blue/" . $pokemon->getPokemonId() . ".png",
                    "fields" => [[
                        "title" => "Verdwijnt om",
                        "value" => $pokemon->getExpiry()->format('H:i'),
                        "short" => true
                    ],[
                        "title" => "Afstand",
                        "value" => ceil(Geo::calculateDistance($this->latitude, $this->longitude, $pokemon->getLatitude(), $pokemon->getLongitude())) . ' meter',
                        "short" => true
                    ]],
                    'text' => 'Er zit een ' . $pokemon->getName() . ' in de buurt voor de komende ' . $pokemon->getExpiryInMinutes() . ' minuten.',
                ])->send();
        }
    }

    /**
     * Get Google Maps link for this pokemon
     *
     * @param Pokemon $pokemon
     * @return string
     */
    protected function getMapLink(Pokemon $pokemon) : string
    {
        return 'http://maps.google.com/?q=' . $pokemon->getLatitude() . ',' . $pokemon->getLongitude();
    }

    /**
     * Get a Google static maps image of the location and pokemon
     *
     * @param Pokemon $pokemon
     * @return string
     */
    protected function getStaticMapImage(Pokemon $pokemon) : string
    {
        $baseUrl = "http://maps.googleapis.com/maps/api/staticmap?";
        $queryString = http_build_query([
            'center'  => $pokemon->getLatitude() . ',' . $pokemon->getLongitude(),
            'zoom'    => 16,
            'size'    => '300x300',
            'sensor'  => false,
        ]);

        $queryString .= '&markers=icon:' . "http://n.kobalt.blue/" . $pokemon->getPokemonId() . ".png" . '%7C' . $pokemon->getLatitude() . ',' . $pokemon->getLongitude();

        return $baseUrl . $queryString;
    }
}
