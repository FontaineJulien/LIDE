<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 14:54
 */

namespace MainBundle\BashCommands\Docker;

use MainBundle\BashCommands\BashCommandBuilder;

/**
 * Class DockerStopCommandBuilder
 * This class is a helper to build a docker stop command
 * @package MainBundle\Docker
 */
class DockerStopCommandBuilder extends BashCommandBuilder
{

    private $waitTimeSet = false;

    /**
     * DockerStopCommandBuilder constructor.
     * @param string $containerId the identifier of the container to stop
     */
    public function __construct($containerId)
    {
        parent::__construct('docker stop');
        $this->addRawArgument($containerId);
    }


    public static function newInstance($containerIdentifier)
    {
        return new static($containerIdentifier);
    }

    /**
     * @param integer $seconds
     * @return DockerStopCommandBuilder
     */
    public function withWaitTime($seconds)
    {
        if($this->waitTimeSet){
            throw new \RuntimeException("Wait time already set");
        }
        $this->addFlagArgument('-t', $seconds);
        $this->waitTimeSet = true;
        return $this;
    }
}