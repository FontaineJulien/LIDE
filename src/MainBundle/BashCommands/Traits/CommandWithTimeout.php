<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 17:49
 */

namespace MainBundle\BashCommands\Traits;


trait CommandWithTimeout
{
    /**
     * @var string
     */
    private $timeoutTime;
    /**
     * @var String
     */
    private $timeoutSignal;

    public function buildTimeoutPrefix()
    {
        if(!is_null($this->timeoutTime)){
            return "timeout --signal={$this->timeoutSignal} {$this->timeoutTime}";
        }
        return "";
    }

    /**
     * @param $timeoutTime
     * @param $timeOutSignal
     * @return $this
     */
    public function withTimeout($timeoutTime, $timeOutSignal = 'SIGKILL')
    {
        $this->timeoutTime = $timeoutTime;
        $this->timeoutSignal = $timeOutSignal;
        return $this;
    }
}