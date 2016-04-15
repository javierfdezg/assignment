<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Posts;
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
        ->add('title')
        ->add('file')
        ->setAction($this->generateUrl('form_handler'))
        ->setMethod('POST')
        ->getForm();

      // TODO: refactor this
      $em->getConnection()->executeQuery('UPDATE statistics SET count=count+1 WHERE type="views";');

      return $this->render('default/index.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        'form' => $form->createView()
      ]);
    }
}
