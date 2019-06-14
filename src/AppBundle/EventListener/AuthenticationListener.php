<?php

#src/AppBundle/EventListener/AuthenticationListener.php

namespace AppBundle\EventListener;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthenticationListener
{

    private $doctrine;

    private $request;


    /**
     * @param $doctrine
     * @param $request
     */
    public function __construct($doctrine, $request)
    {
        $this->doctrine = $doctrine;
        $this->request = $request;
    }

    /**
     * onAuthenticationFailure
     *
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $username = $event->getAuthenticationToken()->getUsername();
        $this->saveLogin($username, false);
    }

    /**
     * onAuthenticationSuccess
     *
     * @param InteractiveLoginEvent $event
     */
    public function onAuthenticationSuccess(InteractiveLoginEvent $event)
    {
        $username = $event->getAuthenticationToken()->getUsername();
        $this->saveLogin($username, true);
    }

    /**
     * onAuthenticationSuccess
     *
     * @param $success
     * @param $username
     */
    private function saveLogin($username, $success) 
    {
         $user =   $this->doctrine->getRepository("AppBundle:User")->loadUserByUsername($username);
        $date =  new \DateTime();
    
        $user->setLastLogin( $date);
        //  dump($user);
        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();
    }
}
