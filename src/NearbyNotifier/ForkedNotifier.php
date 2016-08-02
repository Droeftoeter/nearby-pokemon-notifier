<?php
namespace NearbyNotifier;

use Pokapi\Authentication\Provider;

/**
 * Class ForkedNotifier
 *
 * @package NearbyNotifier
 * @author Freek Post <freek@kobalt.blue>
 */
class ForkedNotifier extends BaseNotifier
{

    /**
     * @var Provider[]
     */
    protected $authProviders;

    /**
     * @var int
     */
    protected $pid;

    /**
     * ForkedNotifier constructor.
     *
     * @param array $authProviders
     * @param float $latitude
     * @param float $longitude
     * @param int $steps
     * @param float $radius
     */
    public function __construct(array $authProviders, float $latitude, float $longitude, int $steps = 5, float $radius = 0.07)
    {
        parent::__construct($latitude, $longitude, $steps, $radius);
        foreach ($authProviders as $authProvider) {
            $this->addAuthProvider($authProvider);
        }
    }

    /**
     * Add an authentication provider.
     *
     * @param Provider $authProvider
     * @return ForkedNotifier
     */
    public function addAuthProvider(Provider $authProvider) : self
    {
        $this->authProviders[] = $authProvider;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        /* Splits the workload */
        $stepChunks = array_chunk($this->steps, ceil(count($this->steps) / count($this->authProviders)));
        $this->getLogger()->debug("Splitting workload into {Chunks} chunks.", [
            'Chunks' => count($stepChunks)
        ]);

        /* Spawn a fork for every authentication provider */
        foreach ($stepChunks as $chunk => $steps) {
            $this->fork($steps, $this->authProviders[$chunk]);
        }

        /* If parent process */
        if ($this->pid) {
            pcntl_wait($status);
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
     * Fork a notifier
     *
     * @param array $steps
     * @param Provider $authProvider
     */
    protected function fork(array $steps, Provider $authProvider)
    {
        $this->pid = pcntl_fork();
        if (!$this->pid) {
            $this->getLogger()->debug("Spawning child process to walk {Steps} steps.", [
                'Steps' => count($steps)
            ]);
            $notifier = new Notifier($authProvider, $this->latitude, $this->longitude, 1, 0.07);
            $notifier->overrideSteps($steps);
            $notifier->setLogger($this->getLogger());

            foreach ($this->handlers as $handler) {
                $notifier->attach($handler);
            }

            $notifier->run();
            exit;
        }
    }
}