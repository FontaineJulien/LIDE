<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 14:55
 */

namespace MainBundle\BashCommands\Docker;


use MainBundle\BashCommands\BashCommandBuilder;

class DockerAttachCommandBuilder extends BashCommandBuilder
{

    /**
     * @var boolean
     */
    private $inputSet;
    private $containerIdentifier;

    public function __construct($containerIdentifier)
    {
        parent::__construct('docker start');
        $this->addFlagArgument('-a');
        $this->addRawArgument($containerIdentifier);
        $this->containerIdentifier = $containerIdentifier;
    }

    public function interactive(){
        if($this->inputSet){
            throw new \RuntimeException("Input mode already set");
        }
        $this->inputSet = true;
        $this->addFlagArgument('-i');
        return $this;
    }

    public function notInteractive(){
        if($this->inputSet){
            throw new \RuntimeException("Input mode already set");
        }
        $this->inputSet = true;
        return $this;
    }


    public static function newInstance($containerIdentifier)
    {
        return new static($containerIdentifier);
    }
}