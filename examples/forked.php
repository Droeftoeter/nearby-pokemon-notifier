<?php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use NearbyNotifier\Enum\Rarity as RarityEnum;
use NearbyNotifier\Filter\Distance;
use NearbyNotifier\Filter\Rarity;
use NearbyNotifier\ForkedNotifier;
use NearbyNotifier\Handler\Slack;
use Pokapi\Authentication\TrainersClub;
use Pokapi\Request\DeviceInfo;

require '../vendor/autoload.php';
date_default_timezone_set("Europe/Amsterdam");

/* Configuration */
$settings = [
    'latitude' => 51.436596, // Eindhoven!
    'longitude' => 5.478001,
    'steps' => 6,
    'radius' => 0.04, // 70 Meters!
    'slackhook' => '',
];

/* Providers */
$providers = [
    new TrainersClub('ACCOUNT_1', 'PASSWORD_1'),
    new TrainersClub('ACCOUNT_2', 'PASSWORD_2'),
    new TrainersClub('ACCOUNT_3', 'PASSWORD_3')
];

/* Device Info */
$deviceInfo = [
    DeviceInfo::getDefault('DEVICE_ID_1'),
    DeviceInfo::getDefault('DEVICE_ID_2'),
    DeviceInfo::getDefault('DEVICE_ID_3'),
];

/* Instantiate notifier */
$notifier = new ForkedNotifier(
    $providers,
    $deviceInfo,
    $settings['latitude'],
    $settings['longitude'],
    $settings['steps'],
    $settings['radius']
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

/* Run Once */
$notifier->run();
// To loop:
//$notifier->runContinously();
