<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;

use AppBundle\Entity\User;
use AppBundle\Entity\Message;
use AppBundle\Form\UserUserForm;
use AppBundle\Form\UserPasswordForm;
use AppBundle\Form\UserForm;
use AppBundle\Service\MyLibrary;


class UserController extends Controller
{

    private $lang="fr";
    private $mylib;
    private $requestStack ;
    private $trans;

    private $encoderFactory;


    public function __construct( MyLibrary $mylib ,RequestStack $request_stack,EncoderFactoryInterface $encoderFactory,TranslatorInterface $translator)
    {
        $this->mylib = $mylib;
        $this->requestStack = $request_stack;
        $this->encoderFactory = $encoderFactory;
        $this->trans =$translator;
    }

    public function Showall()
    {
        $this->lang = $this->requestStack->getCurrentRequest()->getLocale();
        $fusers = $this->getDoctrine()->getRepository("AppBundle:User")->findAll();
        if (!$fusers) {
            return $this->render('person/showall.html.twig', [ 'message' =>  'People not Found',]);
        }
        foreach($fusers as $fuser)
        {
            $fuser->link = "/admin/user/".$fuser->getUserid();
        }
        return $this->render('user/showall.html.twig',
        [
        'lang' => $this->lang,
        'message' =>  '' ,
        'heading' => 'users',
        'fusers'=> $fusers,
        ]);
    }


    public function editone($uid)
    {

        $request = $this->requestStack->getCurrentRequest();
        $fuser = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $encoder = $this->encoderFactory->getEncoder($fuser);
        $form = $this->createForm(UserForm::class, $fuser);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $plainpassword = $fuser->getPlainPassword();
            $hashpassword = $encoder->encodePassword($plainpassword,null);
            $fuser->setPassword($hashpassword);
            $time = new \DateTime();
             $adminuser = $this->getUser();
            $fuser->setContributor($adminuser->getUsername());
            $fuser->setUpdate_dt($time);
            $entityManager->persist($fuser);
            $entityManager->flush();
            return $this->redirect("/admin/user/search");
        }


        return $this->render('user/adminedit.html.twig', array(
            'fuser'=> $fuser,
            'form' => $form->createView(),
            'returnlink'=>'/admin/user/search',
            ));
    }

    public function newuser()
    {

        $request = $this->requestStack->getCurrentRequest();
        $fuser = new User;
        $fuser->setRolestr('ROLE_USER;');
        $encoder = $this->encoderFactory->getEncoder($fuser);
        $form = $this->createForm(UserForm::class, $fuser);
// paul to fix username needs to be enterable and then validated
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $plainpassword = $fuser->getPlainPassword();
            $hashpassword = $encoder->encodePassword($plainpassword,null);
            $fuser->setPassword($hashpassword);
            $time = new \DateTime();
             $adminuser = $this->getUser();
            $user->setContributor($adminuser->getUsername());
            $user->setUpdate_dt($time);
            $entityManager->persist($fuser);
            $entityManager->flush();
            return $this->redirect("/admin/user/search");
        }


        return $this->render('user/adminedit.html.twig', array(
            'fuser'=> $fuser,
            'form' => $form->createView(),
            'returnlink'=>'/admin/user/search',
            ));
    }


    public function edituser($uid)
    {
        $user = $this->getUser();
        if(!$user)  return $this->redirect("/".$this->lang."/login");
        if($uid!= $user->getUserId())  return $this->redirect("/".$this->lang."/person/all");
        $request = $this->requestStack->getCurrentRequest();
        $fuser = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $encoder = $this->encoderFactory->getEncoder($fuser);
        $tpass= $fuser->getEmail();

        $form = $this->createForm(UserUserForm::class, $fuser);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $plainpassword = $fuser->getPlainPassword();
            $hashpassword = $encoder->encodePassword($plainpassword,null);
            $fuser->setPassword($hashpassword);
            $time = new \DateTime();
             $adminuser = $this->getUser();
            $fuser->setContributor($adminuser->getUsername());
            $fuser->setUpdate_dt($time);
            $entityManager->persist($fuser);
            $entityManager->flush();
            return $this->redirect("/".$this->lang."/user/".$uid);
        }

        $password = $fuser->getPassword();

        return $this->render('user/useredit.html.twig', array(
            'form' => $form->createView(),
            'password' => $fuser->getPassword(),
            'returnlink'=> "/".$this->lang."/user/".$uid,
            ));
    }

    public function remotechangepassword($uid, $code)
    {
        $user =   $this->getDoctrine()->getRepository("AppBundle:User")->findOne($uid);
        if(!$user)  return $this->redirect("/".$this->lang."/login");

        $request = $this->requestStack->getCurrentRequest();

        $encoder = $this->encoderFactory->getEncoder($user);
        $tpass= $user->getEmail();


        $usercode = $user->getRegistrationCode();
        $temp = $user->hasRole("ROLE_APWC");
        if( $temp )
        {
        if($code == $usercode )
        {
            $form = $this->createForm(UserPasswordForm::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid())
            {
                $user->updateRole("passwordchanged");
                $entityManager = $this->getDoctrine()->getManager();
                $plainpassword = $user->getPlainPassword();
                $hashpassword = $encoder->encodePassword($plainpassword,null);
                $user->setPassword($hashpassword);
                $time = new \DateTime();
                $user->setContributor("system");
                $user->setUpdate_dt($time);
                $entityManager->persist($user);
                $entityManager->flush();
                $smessage = $this->get('message_service')->sendConfidentialUserMessage('changepass.success','reset.password.success',$user);
                $message = array();
                $message[] = 'reset.password.success';
                return $this->render('registration/done.html.twig',
                array(
                    'user' => $user ,
                    'messages'=>$message,
                    'heading'=> 'changepass.success',
                    ));
            }

            return $this->render('user/userpassword.html.twig', array(
                'form' => $form->createView(),
                'password' => $user->getPassword(),
                'returnlink'=> "/".$this->lang."/user/".$uid,
                ));
        }
        return $this->redirect('/accueil/message/'."password.change.failed");
        }
        return $this->redirect('/accueil/message/'."password.already.changed");
    }

    public function userRereg($uid)
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $user->setLastlogin( new \DateTime());
        $user->updateRole("forcereregistration");
        $time = new \DateTime();
         $adminuser = $this->getUser();
        $user->setContributor($adminuser->getUsername());
        $user->setUpdate_dt($time);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $smessage = $this->get('message_service')->sendConfidentialUserMessage('reregister','rereg.notice',$user);
        return $this->redirect("/admin/user/".$uid);
    }

    public function bulkUserRereg()
    {
        $this->lang = $this->requestStack->getCurrentRequest()->getLocale();

        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();
        $destinataires = $session->get('selectedusers');
        $userlist = explode(",",$destinataires);
        $numbertosend= count($userlist) - 1;
        return $this->render('user/bulkrereg.html.twig', array(
            'lang'=>$this->lang,
            'destinataires' =>$destinataires,
            'numbertosend'=>$numbertosend,
            'returnlink'=>'/admin/user/search',
            'actionlink'=>'/admin/user/bulkrereg/send/',
            ));
    }


    public function bulkUserReregSend()
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();
        $destinataires = $session->get('selectedusers');
        $userlist = explode(",",$destinataires);

        foreach($userlist as $uid)
        {
            $user =  $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
            if($user)
            {
                $this-> SendUserRereg($user->getUserid());
            }
        }

        return $this->redirect("/admin/user/search");
    }

    public function SendUserRereg($uid)
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $user->setLastlogin( new \DateTime());
        $user->updateRole("forcereregistration");
        $time = new \DateTime();
         $adminuser = $this->getUser();
          $adminuser = $this->getUser();
        $user->setContributor($adminuser->getUsername());
        $user->setUpdate_dt($time);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $smessage = $this->get('message_service')->sendConfidentialUserMessage('reregister','rereg.notice',$user);
    }

    public function userDereg($uid)
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $user->setLastlogin( new \DateTime());
        $user->updateRole("deregistration");
        $time = new \DateTime();
        $user->setContributor($adminuser->getUsername());
        $user->setUpdate_dt($time);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $smessage = $this->get('message_service')->sendConfidentialUserMessage('deregister','dereg.notice',$user);
        return $this->redirect("/".$fuser->getLang()."/user/".$uid);
    }




    public function showuser($uid)
    {
        $user = $this->getUser();
        if(!$user) return $this->redirect("/".$this->lang."/loginx");
        if($uid!= $user->getUserId())  return $this->redirect("/".$this->lang."/person/all");
        $this->lang = $this->requestStack->getCurrentRequest()->getLocale();
        $fuser = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $email= $fuser->getEmail();
        $messages = $this->getDoctrine()->getRepository('AppBundle:Message')->findbyname($fuser->getUserName());
        return $this->render('user/show.html.twig', array(
            'lang'=>$this->lang,
            'user' => $fuser,
            'messages' =>$messages,
            'returnlink'=> "/".$this->lang."/person/all",
            ));
    }

    public function showone($uid)
    {

        $this->lang = $this->requestStack->getCurrentRequest()->getLocale();
        $fuser = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $email= $fuser->getEmail();

        $messages = $this->getDoctrine()->getRepository('AppBundle:Message')->findbyname($fuser->getUsername());
        return $this->render('user/showone.html.twig', array(
            'lang'=>$this->lang,
            'user' => $fuser,
            'messages' =>$messages,
            'returnlink'=> "/admin/user/search",
            'deletelink'=> "/admin/user/delete/".$uid,
            ));
    }


    public function deleteuser($uid)
    {
       // $this->getDoctrine()->getRepository('AppBundle:User')->deactivate($uid);
        $fuser = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $fuser->updateRole("deregistration");
        $entityManager = $this->getDoctrine()->getManager();
        $adminuser = $this->getUser();
        $time = new \DateTime();
        $fuser->setContributor($adminuser->getUsername());
        $fuser->setUpdate_dt($time);
        $entityManager->persist($fuser);
        $entityManager->flush();
        return $this->redirect("/admin/user/search");
    }


    public function viewMessage($uid,$mid)
    {
        $user = $this->getUser();
        if($uid!= $user->getUserId())  return $this->redirect("/".$this->lang."/user/".$uid);
        $this->lang = $this->requestStack->getCurrentRequest()->getLocale();
        $fuser = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $email= $fuser->getEmail();

        $message = $this->getDoctrine()->getRepository('AppBundle:Message')->find($mid);
        return $this->render('user/showmessage.html.twig', array(
            'lang'=>$this->lang,
            'user' => $fuser,
            'message' =>$message,
            'returnlink'=> "/".$this->lang."/user/".$uid,
            ));
    }

    public function deletemessage($uid,$mid)
    {
        $user = $this->getUser();
        if($uid!= $user->getUserId())  return $this->redirect("/".$this->lang."/person/all");
        $this->getDoctrine()->getRepository('AppBundle:Message')->delete($mid);
        return $this->redirect("/".$this->lang."/user/".$uid);
    }

    public function deleteallmessages($uid)
    {
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOne($uid);
        $this->getDoctrine()->getRepository('AppBundle:Message')->deleteallusermessages($user->getUsername());
        return $this->redirect("/admin/user/".$uid);
    }

    public function admindeletemessage($uid,$mid)
    {
        $user = $this->getUser();
        $this->getDoctrine()->getRepository('AppBundle:Message')->delete($mid);
        return $this->redirect("/admin/user/".$uid);
    }

    public function UserSearch($search, Request $request)
    {
        $selectedusers="";
        $message="";
        $this->lang = $this->requestStack->getCurrentRequest()->getLocale();
        $usersforapproval = $this->getDoctrine()->getRepository("AppBundle:User")->findSearch("%ROLE_AADA%");
         $pfield = null;
        if(isset($_GET['searchfield']))
        {
            $pfield = $_GET['searchfield'];
        //   $this->mylib->setCookieFilter("user",$pfield);
        }
        else
        {
            if(strcmp($search, "=") == 0)
            {
        //        $pfield = $this->mylib->getCookieFilter("user");
            }
            else
            {
                $pfield="*";
      //         $this->mylib->clearCookieFilter("user");
            }
        }
        if (is_null($pfield) || $pfield=="" || !$pfield || $pfield=="*")
        {
            $users = $this->getDoctrine()->getRepository("AppBundle:User")->findAll();
            $subheading =  'found.all';
        }
        else
        {
            $sfield = "%".$pfield."%";
            $users = $this->getDoctrine()->getRepository("AppBundle:User")->findSearch($sfield);
            $subheading =  'found.with';
        }
        if (count($users)<1)
        {
            $subheading = 'nothing.found.for';
        }
        else
        {
            foreach($users as $user)
            {
                $user->link = "/admin/user/".$user->getUserid();
                $selectedusers .= $user->getUserid().", ";
            }
        }
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();
        $session->set('selectedusers', $selectedusers);
        return $this->render('user/usersearch.html.twig',
        [
        'message' => $message,
        'heading' =>  'Gestion des Utilisateurs',
        'subheading' =>  $subheading,
        'searchfield' =>$pfield,
        'users'=> $users,
        'adapusers'=> $usersforapproval,

        ]);
    }

}

