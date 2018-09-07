<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 17:43
 */

namespace MainBundle\BashCommands;



use MainBundle\BashCommands\Traits\CommandStreamRedirection;
use MainBundle\BashCommands\Traits\CommandWithTimeout;

abstract class AbstractBashCommandBuilder
{
    use CommandStreamRedirection;
    use CommandWithTimeout;

    private $commandsAfter = [];

    /**
     * @return string
     */
    public function build()
    {
            return $this->buildTimeoutPrefix(). " " . $this->buildCommand() . " " . $this->buildRedirectOutput() . $this->buildChain();
    }

    /**
     * @return string
     */
    protected abstract function buildCommand();

    private function buildChain()
    {
        $ret = "";
        foreach ($this->commandsAfter as $commandAfter)
        {
            if(isset($commandAfter['builder'])){
                /** @var AbstractBashCommandBuilder $builder */
                $builder = $commandAfter['builder'];
                $ret .= $commandAfter['separator'] . " ( ". $builder->build() . ")";
            }else{
                $ret .= $commandAfter['separator'] . " ( ". $commandAfter['raw'] . ")";
            }
        }
        return $ret;
    }

    /**
     * Chain another command, regardless of the success of this one
     * @param AbstractBashCommandBuilder $commandBuilder
     * @return static
     */
    public function then(AbstractBashCommandBuilder $commandBuilder){
        $this->commandsAfter[] = [
            'builder' => $commandBuilder,
            'separator' => ';'
        ];
        return $this;
    }

    /**
     * @param AbstractBashCommandBuilder $commandBuilder
     * @return static
     */
    public function thenIfSuccess(AbstractBashCommandBuilder $commandBuilder){
        $this->commandsAfter[] = [
            'builder' => $commandBuilder,
            'separator' => '&&'
        ];
        return $this;
    }

    /**
     * @param AbstractBashCommandBuilder $commandBuilder
     * @return static
     */
    public function thenIfFailed(AbstractBashCommandBuilder $commandBuilder){
        $this->commandsAfter[] = [
            'builder' => $commandBuilder,
            'separator' => '||'
        ];
        return $this;
    }

    public function thenRaw($raw){
        $this->commandsAfter[] = [
            'raw' => $raw,
            'separator' => ';'
        ];
    }

    public function thenIfSuccessRaw($raw){
        $this->commandsAfter[] = [
            'raw' => $raw,
            'separator' => '&&'
        ];
    }

    public function thenIfFailedRaw($raw){
        $this->commandsAfter[] = [
            'raw' => $raw,
            'separator' => ' || '
        ];
    }
}