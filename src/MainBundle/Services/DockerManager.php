<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 21:36
 */

namespace MainBundle\Services;


use MainBundle\BashCommands\BashCommandBuilder;
use MainBundle\BashCommands\Docker\DockerStartCommandBuilder;
use MainBundle\BashCommands\Docker\DockerStopCommandBuilder;
use Psr\Log\LoggerInterface;

class DockerManager
{
    /**
     * @var SshConnectionHandler
     */
    private $sshConnectionHandler;
    private $dockerTimeout;
    private $hosts;
    private $cpuAllocation;
    private $memoryAllocation;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DockerManager constructor.
     * @param LoggerInterface $logger
     * @param SshConnectionHandler $sshConnectionHandler
     * @param $dockerTimeout
     * @param $hosts
     * @param $cpuAllocation
     * @param $memoryAllocation
     */
    public function __construct(LoggerInterface $logger, SshConnectionHandler $sshConnectionHandler, $dockerTimeout, $hosts, $cpuAllocation, $memoryAllocation)
    {

        $this->logger = $logger;
        $this->sshConnectionHandler = $sshConnectionHandler;
        $this->dockerTimeout = $dockerTimeout;
        $this->hosts = $hosts;
        $this->cpuAllocation = $cpuAllocation;
        $this->memoryAllocation = $memoryAllocation;
    }

    /**
     * @param $containerIdentifier
     */
    public function stopContainer($containerIdentifier)
    {
        $dockerStopCommandBuilder = (new DockerStopCommandBuilder($containerIdentifier))
            ->withWaitTime(0)
            ->redirectStdout("/dev/null")
            ->redirectErrorToStdout();
        $this->sshConnectionHandler->execAndRead($dockerStopCommandBuilder->build());
    }

    /**
     * @param string $image
     * @param string $containerIdentifier
     * @param string|BashCommandBuilder $entryCommand
     * @param bool $stopIfExists
     */
    public function startContainer($image, $containerIdentifier, $entryCommand, $stopIfExists = true)
    {
        if($entryCommand instanceof BashCommandBuilder) {
            $entryCommand = $entryCommand->build();
        }

        $dockerStartCommandBuilder = DockerStartCommandBuilder::newInstance($image)
            ->withIdentifier($containerIdentifier)
            ->withRemove()
            ->withTimeout($this->dockerTimeout, 'SIGKILL')
            ->withPseudoTty()
            ->withInput()
            ->addHost($this->hosts)
            ->allocateCpu($this->cpuAllocation)
            ->allocateMemory($this->memoryAllocation)
            ->withStartCommand($entryCommand);

        if($stopIfExists) {
            $command = DockerStopCommandBuilder::newInstance($containerIdentifier)
                ->withWaitTime(0)
                ->redirectStdout("/dev/null")
                ->redirectErrorToStdout()
                ->then($dockerStartCommandBuilder)
                ->build();
        }else{
            $command = $dockerStartCommandBuilder->build();
        }

        $this->sshConnectionHandler->connect();
        $this->logger->debug("Starting Container [{$containerIdentifier}]", [
            'command' => $command
        ]);
        $this->sshConnectionHandler->execCmd($command);

    }

    public function isContainerRunning($containerIdentifier)
    {
        $cmd = "[[ -n $(docker ps -q -f name={$containerIdentifier}) ]] && echo \"OK\" || echo \"FINI\"";

        $this->sshConnectionHandler->connect();
        $out = $this->sshConnectionHandler->executeCommandAndReadOutput($cmd, true); //Need to test that

        $this->logger->debug($out, ["DockerManager::isContainerRunning"]);
        return (strcmp($out, "OK\n") === 0);
    }
}