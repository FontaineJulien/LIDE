<?php

namespace MainBundle\Services;

use Psr\Log\LoggerInterface;

class SshConnectionHandler
{
    private $connection;
    private $shell;
    private $cmd;   //dernière commande lue
    private $msg;   //dernier message envoyé
    
    private static $TIME_OUT = 60;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $ssh_adr;
    private $ssh_port;
    private $ssh_login;
    private $ssh_password;

    /**
     * SshConnectionHandler constructor.
     * @param $ssh_adr
     * @param $ssh_port
     * @param $ssh_login
     * @param $ssh_password
     * @param LoggerInterface $logger
     */
    public function __construct($ssh_adr, $ssh_port, $ssh_login, $ssh_password, LoggerInterface $logger){
        $this->logger = $logger;
        $this->ssh_adr = $ssh_adr;
        $this->ssh_port = $ssh_port;
        $this->ssh_login = $ssh_login;
        $this->ssh_password = $ssh_password;
    }

    public function connect(){
        if(is_null($this->connection)){
            $this->connection = ssh2_connect($this->ssh_adr, $this->ssh_port);

            ssh2_auth_password($this->connection,$this->ssh_login,$this->ssh_password);

            $this->shell = ssh2_shell($this->connection,"bash",null,10000,10000, SSH2_TERM_UNIT_CHARS);
        }
    }

    /**
     * Lit dans le shell
     * @param int $id_user
     * @return array
     */
    function read($id_user)
    {
        if(is_null($this->connection)){
            throw new \RuntimeException("Ssh connection was not initialised");
        }

        $out = "";
        $start = false;
//        $msg_rencontre =false;
        $start_time = time();
        $max_time = 2; //time in seconds
        
        // Si la connexion ssh a échoué
        if ($this->shell == null) {
            $output [] = "Erreur, connexion ssh \n";
            $output [] = "yes";
            return $output;
        }        

        $this->logger->debug('GestionSSH: starting read');
        while ((time()-$start_time)< SshConnectionHandler::$TIME_OUT) {

            $new_start_time = time();
            while (((time()-$new_start_time) < $max_time)) {

                $line = fgets($this->shell);

                if (!(strstr($line,$this->cmd))) { //On n'affiche pas la commande 
                    
                    if (!$start && preg_match("/beginOutput/", $line)) { //Permet de ne pas afficher les lignes d'initialisation du shell
                        $start = true;
                        $this->logger->debug('GestionSSH: start');
                    } else if ($start && !preg_match("/beginOutput/", $line)) {
                        $out .= $line;
                    }
                }
            }
        
            //Si la chaine est differente de null
            if($out!=null){
                
                //On teste si le docker est terminé
                $testfin = $this->dockerTermine($id_user);
                $this->logger->info('GestionSSH: Docker terminé');
                //Le docker est terminé si testfin est égal à "NOT OK"
                if($testfin){
                    
                    //On récupère  tout sauf la dernière ligne (invit de commande du shell);
                    $outTab = explode("\n",$out,-1);
                    
                    $out = "";
                    
                    //i commence à 1 pour ne pas afficher le message encoyé
                    for($i=1;$i<count($outTab);$i++){
                        $out.=$outTab[$i]."\n";
                    }               
                    
                    $output [] = $out;
                    $output [] = "yes";
                    return $output;
                } else{                    
                    $output [] = $out;
                    $output [] = "no";
                    return $output;
                }
            }
            
        }
        throw new \RuntimeException('wtf');
    }
    
    
    /**
     * Teste si le docker est terminé ou non
     * @param int $id_user
     * @return boolean
     */
    function dockerTermine($id_user)
    {
        if(is_null($this->connection)){
            throw new \RuntimeException("Ssh connection was not initialised");
        }
        //On teste si la sortie de docker ps contient l'identifiant de l'utilisateur
        $stream = ssh2_exec($this->connection, "[[ -n $(docker ps -q -f name=lide_$id_user"."A) ]] && echo \"OK\" || echo \"FINI\"");
        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);     
        fclose($stream);       
        return (strcmp($output, "FINI\n") === 0);
    }

    public function executeCommandAndReadOutput($cmd, $blocking = false)
    {
        if(is_null($this->connection)){
            throw new \RuntimeException("Ssh connection was not initialised");
        }
        //On teste si la sortie de docker ps contient l'identifiant de l'utilisateur
        $stream = ssh2_exec($this->connection, $cmd);
        stream_set_blocking($stream, $blocking);
        $output = stream_get_contents($stream);
        fclose($stream);
        return $output;
    }

    /**
     * Exécute une commande
     * @param string $cmd la commande à executer
     */
    function execCmd($cmd)
    {
        if(is_null($this->connection)){
            throw new \RuntimeException("Ssh connection was not initialised");
        }
        $cmdSE = "echo 'beginOutput';".$cmd;
        $this->cmd = $cmdSE;
        fwrite($this->shell,$cmdSE . "\n");
    }

    /**
     * @param $cmd
     * @deprecated
     * @return string
     */
    function execAndRead($cmd)
    {
        if(is_null($this->connection)){
            throw new \RuntimeException("Ssh connection was not initialised");
        }
        $cmdSE = "echo 'beginOutput';".$cmd;
        $this->cmd = $cmdSE;
        fwrite($this->shell,$cmdSE . "\n");

        $out = "";
        while($line = fgets($this->shell)){
            $out .= $line;
        }
        return $out;
    }

    /**
     * Écrit un message dans le shell
     * @param string $msg
     */
    function write($msg)
    {
        if(is_null($this->connection)){
            throw new \RuntimeException("Ssh connection was not initialised");
        }
        flush();        
        fwrite($this->shell,$msg."\n");        
        $this->msg = $msg;
    }
    
};
