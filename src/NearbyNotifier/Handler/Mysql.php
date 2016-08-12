<?php
namespace NearbyNotifier\Handler;

use NearbyNotifier\Entity\Pokemon;
use PDO;

/**
 * Class Mysql
 *
 * Super simple example to add Pokemon to Mysql
 *
 * @package NearbyNotifier\Handler
 * @author Freek Post <freek@kobalt.blue>
 */
class Mysql extends Handler
{

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * Mysql constructor.
     *
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     * @param array $filters
     */
    public function __construct(string $host, string $database, string $username, string $password, array $filters = [])
    {
        parent::__construct($filters);
        $this->pdo = new PDO('mysql:host=' . $host . ';dbname=' . $database, $username, $password);
    }

    /**
     * Handle
     *
     * @param Pokemon $pokemon
     * @param bool $newEncounter
     */
    public function handle(Pokemon $pokemon, bool $newEncounter)
    {
        if ($newEncounter) {
            $query = $this->pdo->prepare("INSERT INTO pokemon VALUES(:encounter, :pokemon, :spawnPoint, :timestamp, :latitude, :longitude, :tth)");
            $query->bindValue('encounter', $pokemon->getEncounterId(), PDO::PARAM_INT);
            $query->bindValue('pokemon', $pokemon->getPokemonId(), PDO::PARAM_INT);
            $query->bindValue('spawnPoint', $pokemon->getSpawnPoint(), PDO::PARAM_INT);
            $query->bindValue('timestamp', $pokemon->getTimestamp(), PDO::PARAM_INT);
            $query->bindValue('latitude', $pokemon->getLatitude());
            $query->bindValue('longitude', $pokemon->getLongitude());
            $query->bindValue('tth', $pokemon->getExpiry()->getTimestamp());
            $query->execute();
        }
    }
}
