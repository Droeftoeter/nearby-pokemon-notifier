<?php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use NearbyNotifier\Enum\Rarity as RarityEnum;
use NearbyNotifier\Filter\Distance;
use NearbyNotifier\Filter\Rarity;
use NearbyNotifier\Handler\Slack;
use NearbyNotifier\Notifier;
use Pokapi\Authentication\TrainersClub;

require '../vendor/autoload.php';
date_default_timezone_set("Europe/Amsterdam");

/* Configuration */
$settings = [
    'latitude' => 51.436596, // Eindhoven!
    'longitude' => 5.478001,
    'steps' => 5,
    'username' => 'USERNAME', // Your PTC username
    'password' => 'PASSWORD', // Your PTC password
    'slackhook' => '',
];

/* Check for people who refuse to change the config... */
if ($settings['username'] === 'USERNAME' || empty($settings['slackhook'])) {
    throw new \Exception("Please check your configuration in slack.php");
}

/* Instantiate notifier */
$notifier = new Notifier(
    new TrainersClub($settings['username'], $settings['password']),
    $settings['latitude'],
    $settings['longitude'],
    $settings['steps']
);

/* Attach Logger */
$logger = new Logger('notifier');
$logger->pushHandler(new StreamHandler("php://output"));
$logger->pushProcessor(new PsrLogMessageProcessor());
$notifier->setLogger($logger);

/* Attach Slack handler */
$notifier->attach(new Slack(
    $settings['slackhook'],
    $settings['latitude'],
    $settings['longitude'],
    [
        new Rarity([ // Of all rarities
            RarityEnum::VERY_COMMON,
            RarityEnum::COMMON,
            RarityEnum::UNCOMMON,
            RarityEnum::RARE,
            RarityEnum::VERY_RARE,
            RarityEnum::SPECIAL,
            RarityEnum::EPIC,
            RarityEnum::LEGENDARY,
        ]),
        new Distance( // Between 0 and 1000 meters of the provided location
            $settings['latitude'],
            $settings['longitude'],
            0,
            1000
        )
    ]
));

/* Run */
$notifier->run();
