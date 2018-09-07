<?php

namespace MainBundle\Controller;

use MainBundle\BashCommands\BashCommandBuilder;
use MainBundle\BashCommands\Docker\DockerAttachCommandBuilder;
use MainBundle\Services\SshConnectionHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use MainBundle\Entity\Execution;
use MainBundle\Form\ExecutionType;

class ConsoleController extends Controller
{
    /**
     * Build the docker container identifier for the given user id
     * @param $user_id
     * @return string
     */
    protected function getUserDockerIdentifier($user_id)
    {
        $prefix = $this->getParameter('docker_name_prefix');
        return "{$prefix}{$user_id}A";
    }

    /**
     * @return SshConnectionHandler
     */
    protected function getSshConnectionHandler()
    {
        return $this->get('ssh_connection_handler');
    }

    /**
     * @return LoggerInterface
     */
    protected function logger()
    {
        return $this->get('logger');
    }

    private function writeFilesInDir($files, $dir)
    {
        $listeFichiers = "";

        foreach ($files as $f) {
            //Écriture des fichiers dans un dossier temporaire
            if ($file_on_disk = fopen($dir . "/" . $f->name, "w")) {
                //Écriture contenu fichier
                $this->logger()->info("Écriture fichier : $f->name");

                fwrite($file_on_disk, $f->content);
                $listeFichiers .= $f->name . " ";

                fclose($file_on_disk);
            } else {
                //Echec fopen
                exec("rm -rf $dir");
                return new Response("Echec écriture fichier $f->name sur serveur");
            }
        }
        return $listeFichiers;
    }

    //Méthode appelée par la vue console
    //Première méthode appelée après l'appui sur le bouton RUN
    public function execAction(Request $request)
    {
        $logger = $this->get('logger');

        $ssh = $this->get('ssh_connection_handler');

        $ssh->connect(); //TODO graciously handle exception if connect failed

        $id_user = $this->getUser()->getId();

        if ($id_user == null) {
            return new Response(json_encode(array(
                'reponse' => "Vous êtes déconnecté",
                'fin' => 'oui'
            )), 403);
        }

        $execution = new Execution();
        $form = $this->createform(ExecutionType::class, $execution);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = "testUser";

            $logger->debug(print_r($request->get('execution'), true));
            $logger->debug("Fichier additionnels : " . print_r($execution->getAdditionalFiles(), true));

            //Écriture des fichiers sur le disques
            $tmpdir = exec("mktemp -d $user.XXXXXX");
            $logger->debug("Dossier temporaire : $tmpdir");

            //Récupération script compilation & éxecution dans la DB, ecriture sur le disk.
            $idLangage = $execution->getLanguage();

            $em = $this->getDoctrine()->getManager();

            $langage = $em->getRepository('MainBundle:Langage')->find($idLangage);
            $script = $langage->getScript();
            $logger->debug($script);

            $file_on_disk = fopen("$tmpdir/exec.sh", "w");
            fwrite($file_on_disk, $script);
            fclose($file_on_disk);

            //Copie fichier source user
            $fichiers = json_decode($execution->getFiles());
            $logger->debug(print_r($fichiers, true));

            $filesListString = $this->writeFilesInDir($fichiers, $tmpdir);

            //Copie fichier additionnels
            foreach ($execution->getAdditionalFiles() as $file) {
                $fileName = $file->getClientOriginalName();
                $file->move(
                    $tmpdir,
                    $fileName
                );
                $filesListString .= $fileName . " ";
            }


            $dockerImageName = $langage->getDockerName();

            $dockerManager = $this->get('docker_manager');
            $containerId = $this->getUserDockerIdentifier($id_user);
            $dockerManager->startContainer($dockerImageName, $containerId, $this->getDockerEntryCommandBuilder($tmpdir, $execution, $filesListString), true);
            // Pause de 1 secondes pour laisser le temps à la commande de s'exécuter
            sleep(1);

            list($output, $hasEnded) = $ssh->read($id_user);

            $response = array(
                'reponse' => $output,
                'fin' => $hasEnded
            );

            exec("rm -rf $tmpdir");

            $logger->info("Reponse", $response);
            return new Response(json_encode($response));
        }

        return new Response(json_encode(array(
            'reponse' => "Erreur formulaire",
            'fin' => 'oui'
        )));
    }

    protected function getDockerEntryCommandBuilder($tmpDir, Execution $execution, $filesListString)
    {
        $wgetAdr = $this->container->getParameter('wget_adr') . "/{$tmpDir}/";

        $compilationParameters = str_replace("\'", "\'\\\'\'", $execution->getCompilationOptions()); //Remplace tous les 'par \'
        $userProgramStartParameters = str_replace("\'", "\'\\\'\'", $execution->getLaunchParameters()); //Idem

        /*
         * Command to start the script inside the docker
         */
        $execShBuilder = (new BashCommandBuilder('./exec.sh'))
            ->addFlagArgument('-o', "'{$compilationParameters}'")
            ->addFlagArgument('-w', $wgetAdr)
            ->addFlagArgument('-a', "'{$userProgramStartParameters}'");

        if ($execution->getInputMode() == 'none') {
            $execShBuilder->addFlag('n');
        } else if ($execution->getInputMode() == 'text') { //Création d'un fichier d'entrée. Lancement du programme du type ./a.out args ... < input_file
            $inputFilename = exec("cd {$tmpDir} && mktemp XXX");

            $inputFile = fopen("{$tmpDir}/{$inputFilename}", "w");
            fwrite($inputFile, $execution->getInputs());
            fclose($inputFile);

            $filesListString .= " $inputFilename";

            $execShBuilder->addFlagArgument('-i', "'{$inputFilename}'");
        } //else Input interractive : rien à faire

        $execShBuilder->addFlagArgument('-f', "'{$filesListString}'");
        //$cmd .= " 2>/dev/null";

        //Mode compilation uniquement
        if ($execution->isCompileOnly() == 1) {
            $execShBuilder->addFlag('c');
        }

        /*
         * Command the docker execute after starting
         */
        $dockerEntryCommandBuilder = (new BashCommandBuilder('/bin/bash'))
            ->addFlag('c')
            ->addRawArgument('"' .
                (new BashCommandBuilder("wget"))// Retrieve script by wget TODO CHANGE
                ->addRawArgument("${wgetAdr}" . "exec.sh")
                    ->redirectStdout('/dev/null')
                    ->redirectErrorToStdout()
                    ->thenIfSuccess(
                        (new BashCommandBuilder('chmod'))// Give execution right on script
                        ->addRawArgument('a+x')
                            ->addRawArgument('exec.sh')
                    )
                    ->thenIfSuccess( // Remove \r in script because mysql is dumb
                        (new BashCommandBuilder('sed'))
                            ->addFlag('ie')
                            ->addRawArgument("'s/\\r$//'")
                            ->addRawArgument('exec.sh')
                    )
                    ->thenIfSuccess( // Then start the script
                        $execShBuilder
                    )->build()
                . '"'
            );
        return $dockerEntryCommandBuilder;
    }

    /**
     * Permet de répondre aux programme (pas nécessairement appelée);
     * @param Request $request
     * @return Response
     */
    public function answerAction(Request $request)
    {
        $logger = $this->get('logger');
        $ssh = $this->get('ssh_connection_handler');

        $ssh->connect(); //TODO proper exception handling

        $msg = $request->request->get('msg');
        $logger->info("Reponse : $msg");

        $userId = $this->getUser()->getId(); //getUser should never return null since we require user to be connected

        if (!$ssh->dockerTermine($userId)) {
            $ssh->execCmd(
                (new DockerAttachCommandBuilder($this->getUserDockerIdentifier($userId)))
                    ->interactive()
                    ->build()
            );
            $ssh->write($msg);
            $output = $ssh->read($this->getUser()->getId());

            $response = array(
                'reponse' => $output[0],
                'fin' => $output[1]
            );
        } else {
            $response = array(
                'reponse' => "Docker terminé",
                'fin' => "yes"
            );
        }

        $logger->info(json_encode($response));

        return new Response(json_encode($response));
    }

    /**
     * Stop le docker de l'utilisateur
     * @param Request $request
     * @return Response
     */
    public function stopAction(Request $request)
    {
        $sshConnectionHandler = $this->get('ssh_connection_handler');

        $sshConnectionHandler->connect(); //TODO proper exception handling
        $dockerManager = $this->get('docker_manager');

        $containerIdentifier = $this->getUserDockerIdentifier($this->getUser()->getId());

        $this->get("logger")->info("Stopping container {$containerIdentifier}");

        if ($dockerManager->isContainerRunning($containerIdentifier)) {
            $dockerManager->stopContainer($containerIdentifier);
            $response = array(
                'out' => '',
                'stopped' => 'ok'
            );
        } else {
            $response = array(
                'stopped' => 'already-dead'
            );
        }
        return new Response(json_encode($response));
    }
}
