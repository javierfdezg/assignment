<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Posts;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use \ZMQContext;
use \ZMQ;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {

      $post = new Posts();
      $form = $this->createFormBuilder($post)
        ->add('title', null, array(
          'label' => false
        ))
        ->add('file', null, array(
          'label' => false
        ))
        ->setAction($this->generateUrl('form_handler'))
        ->setMethod('POST')
        ->getForm();

      // TODO: refactor this
      $em = $this->getDoctrine()->getManager();
      $em->getConnection()->executeQuery('UPDATE statistics SET count=count+1 WHERE type="views";');

      // TODO: refactor this
      $context = new ZMQContext();
      $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'onNewEvent');
      $socket->connect("tcp://localhost:5555");

      $socket->send('views');

      return $this->render('default/index.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        'form' => $form->createView()
      ]);
    }
}
