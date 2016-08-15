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

        /* Splits the workload */
        $stepChunks = array_chunk($this->steps, ceil(count($this->steps) / count($this->authProviders)));

        /* Also walk back to prevent a softban */
        foreach ($stepChunks as $chunkIndex => $steps) {
            $reverse = array_slice(array_reverse($steps), 1, count($steps)-2);
            $stepChunks[$chunkIndex] = array_merge($steps, $reverse);
        }

        $this->getLogger()->debug("Splitting workload of ~{Total} into {Chunks} chunks.", [
            'Total'  => count($this->steps*2),
            'Chunks' => count($stepChunks)
        ]);

        if (count($stepChunks[0] * 2) > 100) {
            $this->getLogger()->alert("Chunk size is more than 100. Considering adding more accounts.");
        }

        /* Spawn a fork for every authentication provider */
        foreach ($stepChunks as $chunk => $steps) {
            $this->pids[] = $this->fork($steps, $this->authProviders[$chunk], $this->deviceInfo[$chunk]);
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
