<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 15:54
 */

namespace MainBundle\BashCommands\Traits;


trait CommandStreamRedirection
{
    private $errorToStdout = false;
    private $stdoutRedirect;

    public function redirectErrorToStdout()
    {
        $this->errorToStdout = true;
        return $this;
    }

    public function redirectStdout($target)
    {
        $this->stdoutRedirect = $target;
        return $this;
    }

    protected function buildRedirectOutput()
    {
        if ($this->stdoutRedirect) {
            $redirect = "> {$this->stdoutRedirect}";
            if ($this->errorToStdout) {
                $redirect .= " 2>&1";
                return $redirect;
            }

            return "";
        }
        return "";
    }
}