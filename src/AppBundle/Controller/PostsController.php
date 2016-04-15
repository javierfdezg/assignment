<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Posts;
use AppBundle\Entity\Statistics;

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
    * @Route("/posts", name="form_handler")
    * @Method("POST")
    */
    public function postAction(Request $request)
    {

      $post = new Posts();
      $form = $this->createFormBuilder($post)
        ->add('title')
        ->add('file')
        ->getForm();

      $form->handleRequest($request);

      if ($form->isValid()) {
          $em = $this->getDoctrine()->getManager();
          $post->setImageUrl('http://test.example.com');

          var_dump($post->getFile()->getClientOriginalName());
          $em->persist($post);
          $em->flush();
          
          // TODO: refactor this
          $em->getConnection()->executeQuery('UPDATE statistics SET count=count+1 WHERE type="posts";');

          return new JsonResponse(JsonResponse::HTTP_OK);
      } else {
        $errors = $form->getErrors(true, false);
        // TODO: check that the errors are being shown
        return new JsonResponse($errors, JsonResponse::HTTP_BAD_REQUEST);
      }
    }
}

