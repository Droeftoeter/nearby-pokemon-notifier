# Pokemon Go Notifier PHP CLI App

## Requirements

* PHP 7
* cURL extension

## Example
Check out the ```examples/slack.php``` file. Don't forget to set your configuration.
For a forked example, check out the ```examples/forked.php``` file.

## Usage

Because of recent changes, the default step-interval is set at 6 seconds. If you want to search a bigger area, use the ForkedNotifier with multiple accounts.

```php
// Create a new notifier with a PTC username, password 
// and your current latitude and longitude.
$notifier = new Notifier(
    new TrainersClub('username', 'password'),
    51.436596, // Latitude
    5.478001, // Longitude
    5, // The amount of steps to walk
    0.07 // The search radius
);

// Attach a new Slack handler
$notifier->attach(new Slack(
    'incoming webhook url',
    51.436596,
    5.478001,
    [
        new Rarity([ // Of all rarities
            Enum\Rarity::VERY_COMMON,
            Enum\Rarity::COMMON,
            Enum\Rarity::UNCOMMON,
            Enum\Rarity::RARE,
            Enum\Rarity::VERY_RARE,
            Enum\Rarity::SPECIAL,
            Enum\Rarity::EPIC,
            Enum\Rarity::LEGENDARY,
        ]),
        new Distance( // Between 0 and 1000 meters of the provided location
            $settings['latitude'],
            $settings['longitude'],
            0,
            1000
        )
    ]
));

// To run the notifier once:
$notifier->run();

// Or in a loop:
$notifier->runContinously();
```

## Multiple accounts

If you want to speed up the search, you can use the ForkedNotifier.
The workload will be split up between all of the provided accounts, running as seperate children.

```php
// Create a forked notifier with multiple accounts
// and your current latitude and longitude.
$notifier = new ForkedNotifier(
    [
        new TrainersClub('username', 'password'),
        new TrainersClub('username2', 'password2'),
        new TrainersClub('username3', 'password3'),
    ],
    51.436596, // Latitude
    5.478001, // Longitude
    5, // The amount of steps to walk,
    0.07 // The search radius. 
);
```

## Handlers

Currently there are only two handlers:

* Slack
* Redis (So you can query Redis and display the pokemon on a map)

Feel free to create your own by extending the ```Handler``` class
