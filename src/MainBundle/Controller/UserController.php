<?php
/**
 * Created by PhpStorm.
 * User: etudiant
 * Date: 07/09/18
 * Time: 14:17
 */

namespace MainBundle\Controller;

use MainBundle\Form\OptionsInterfaceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class UserController
 * Controller for User related action in the ide (saving configuration, code...)
 * @package MainBundle\Controller
 */
class UserController extends Controller
{
    /**
     * Save the user's files in session
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function saveCodeAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $userID = $this->getUser()->getId();
            $jsonFiles = $request->request->get('files');
            $request->getSession()->set('files'.$userID, json_decode($jsonFiles));

            $langage = $request->request->get('langage');
            $request->getSession()->set('langage'.$userID, $langage);


            return new JsonResponse("OK");
        }
        return new Response('This is not ajax!', 400);
    }

    /**
     * Save the user interface configuration in database
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function updateInterfaceAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {

            $user = $this->getUser();
            $form= $this->createform(OptionsInterfaceType::class, $user);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $rep = "OK";
            } else {
                $rep = "Formulaire non valide";
            }

            return new JsonResponse($rep);
        }
        return new Response('This is not ajax!', 400);
    }
}