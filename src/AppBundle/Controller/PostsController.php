<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity;

class PostsController extends Controller
{

    /**
    * @Route("/posts")
    * @Route("/posts/{id}")
    * @Method("GET")
    */
    public function getAction($id = null)
    {
      $em = $this->getDoctrine()->getManager();
      
      if(!$id) 
      {
        $posts = $em->getRepository('AppBundle:Posts')
          ->findBy(
            array(),
            array('createdAt'=>'DESC')
          );
      } else {
        $posts = $em->getRepository('AppBundle:Posts')
          ->find($id);
      }

      if (!$posts) {
        if (!$id) {
          return new JsonResponse(array());
        } else {
          return new JsonResponse(JsonResponse::HTTP_NOT_FOUND);
        }
      } else {
        return new JsonResponse(array('posts'=>$posts));
      }
    }

    /**
    * @Route("/posts/count")
    * @Method("GET")
    */
    public function countAction($id = null)
    {
    }

    /**
    * @Route("/posts")
    * @Method("POST")
    */
    public function postAction($id = null)
    {
    }

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }
}

