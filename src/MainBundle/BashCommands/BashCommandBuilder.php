<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 18:09
 */

namespace MainBundle\BashCommands;


class BashCommandBuilder extends AbstractBashCommandBuilder
{
    /**
     * @var string
     */
    private $executable;
    /**
     * @var string[]
     */
    private $args = [];

    /**
     * BashCommandBuilder constructor.
     * @param $executable
     */
    public function __construct($executable)
    {
        $this->executable = $executable;
    }

    protected function buildCommand()
    {
        return $this->executable . " " . join(" ", $this->args);
    }

    public function addFlagArgument($flag, $arg = null)
    {
        if(is_null($arg)){
            $this->args[] = $flag;
        }else{
            $this->args[] = "{$flag} $arg";
        }
        return $this;
    }

    /**
     * @param string $flag
     * @param bool $long
     * @return static
     */
    public function addFlag($flag, $long = false)
    {
        $this->args[] = ($long ? '--' : '-') . $flag;
        return $this;
    }

    public function addRawArgument($arg)
    {
        $this->args[] = $arg;
        return $this;
    }
}