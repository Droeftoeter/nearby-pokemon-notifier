<?php
namespace NearbyNotifier;

use Pokapi\Authentication\Provider;
use Pokapi\Request\DeviceInfo;
use Exception;

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
     * @var DeviceInfo[]
     */
    protected $deviceInfo;

    /**
     * @var int[]
     */
    protected $pids = [];

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * ForkedNotifier constructor.
     *
     * @param Provider[] $authProviders
     * @param DeviceInfo[] $deviceInfos
     * @param float $latitude
     * @param float $longitude
     * @param int $steps
     * @param float $radius
     */
    public function __construct(array $authProviders, array $deviceInfos, float $latitude, float $longitude, int $steps = 5, float $radius = 0.04)
    {
        parent::__construct($latitude, $longitude, $steps, $radius);
        foreach ($authProviders as $authProvider) {
            $this->addAuthProvider($authProvider);
        }

        foreach ($deviceInfos as $deviceInfo) {
            $this->addDeviceInfo($deviceInfo);
        }
    }

    /**
     * Set the paths
     *
     * @param array $paths
     *
     * @return ForkedNotifier
     */
    public function setPaths(array $paths) : self
    {
        $this->paths = $paths;
        return $this;
    }

    /**
     * Add a path
     *
     * @param array $path
     *
     * @return ForkedNotifier
     */
    public function addPath(array $path) : self
    {
        $this->paths[] = $path;
        return $this;
    }

    /**
     * Add an authentication provider.
     *
     * @param Provider $authProvider
     *
     * @return ForkedNotifier
     */
    public function addAuthProvider(Provider $authProvider) : self
    {
        $this->authProviders[] = $authProvider;
        return $this;
    }

    /**
     * Add device information.
     *
     * @param DeviceInfo $deviceInfo
     *
     * @return ForkedNotifier
     */
    public function addDeviceInfo(DeviceInfo $deviceInfo) : self
    {
        $this->deviceInfo[] = $deviceInfo;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function run()
    {
        /* Check */
        if (count($this->authProviders) > count($this->deviceInfo)) {
            throw new Exception("Not enough devices for all accounts.");
        }

        /* Check two */
        if (count($this->paths) < count($this->authProviders)) {
            throw new Exception("Not enough paths for all accounts.");
        }

        /* Start forks */
        foreach ($this->authProviders as $index => $provider) {
            $this->pids[] = $this->fork($this->paths[$index], $provider, $this->deviceInfo[$index]);
            sleep(1);
        }

        /* If parent process, wait for all children to finish */
        foreach ($this->pids as $pid) {
            pcntl_waitpid($pid, $status);
            unset($this->pids[$pid]);

            $this->getLogger()->debug("Child with process ID {PID} has finished.", [
                'PID' => $pid
            ]);
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
     * @param DeviceInfo $deviceInfo
     *
     * @return int
     */
    protected function fork(array $steps, Provider $authProvider, DeviceInfo $deviceInfo) : int
    {
        $pid = pcntl_fork();
        if (!$pid) {
            $notifier = new Notifier($authProvider, $deviceInfo, $this->latitude, $this->longitude, 1, 0.07);
            $notifier->setStorage($this->getStorage());
            $notifier->setRouteHandler($this->getRouteHandler()->setIdentifier($pid));
            $notifier->overrideSteps($steps);
            $notifier->setLogger($this->getLogger());
            $notifier->init();
            sleep(1);

            foreach ($this->handlers as $handler) {
                $notifier->attach($handler);
            }

            $notifier->run();
            exit;
        }

        $this->getLogger()->debug("Spawned child process {PID} to walk {Steps} steps.", [
            'Steps' => count($steps),
            'PID' => $pid
        ]);

        return $pid;
    }
}
