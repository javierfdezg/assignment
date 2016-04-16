<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Posts;
use AppBundle\Libs\CommonUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

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

      $query = 'UPDATE statistics SET count=count+1 WHERE type="views";';
      CommonUtils::getInstance()->executeQuery($this->getDoctrine()->getManager()->getConnection(), $query);

      // Notify connected clients that there is an update in the views
      CommonUtils::getInstance()->sendWebSocketMessage('views');

      return $this->render('default/index.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        'form' => $form->createView()
      ]);
    }
}
