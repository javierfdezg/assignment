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
use \ZMQContext;
use \ZMQ;
use JMS\SerializerBundle\Annotation\ExclusionPolicy;
use JMS\SerializerBundle\Annotation\Exclude;
use Aws\S3\S3Client;

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
            array('createdAt'=>'ASC')
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
        $serializer = $this->container->get('serializer');
        $posts = $serializer->serialize($posts, 'json');
        return new Response($posts);
      }
    }

    /**
    * @Route("/posts/from/{id}")
    * @Method("GET")
    */
    public function getFromAction($id)
    {
      $em = $this->getDoctrine()->getManager();
      
      $repository = $em->getRepository('AppBundle:Posts');
      $query = $repository->createQueryBuilder('p')
        ->where('p.id > :id')
        ->orderBy('p.id', 'ASC')
        ->setParameter('id', $id)
        ->getQuery();

      $posts = $query->getResult();
      if (!$posts) {
        if (!$id) {
          return new JsonResponse(array());
        } else {
          return new JsonResponse(JsonResponse::HTTP_NOT_FOUND);
        }
      } else {
        $serializer = $this->container->get('serializer');
        $posts = $serializer->serialize($posts, 'json');
        return new Response($posts);
      }
    }

    /**
    * @Route("/posts/count")
    * @Method("GET")
    */
    public function countAction()
    {
      // TODO: implement a common way for this method and /stats/type
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

        $post->upload();

        $em->persist($post);
        $em->flush();

        // TODO: refactor this
        $em->getConnection()->executeQuery('UPDATE statistics SET count=count+1 WHERE type="posts";');

        // TODO: refactor this
        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'onNewEvent');
        $socket->connect("tcp://localhost:5555");

        $socket->send('posts');


        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => 'eu-west-1',
            'profile' => 'insided'
          ]);

        $result = $s3->putObject([
          'Bucket' => 'insided',
          'Key'    => $post->getPath(),
          'SourceFile' => $post->getAbsolutePath()
        ]);

        $post->setImageUrl($result['ObjectURL']);

        $em->persist($post);
        $em->flush();
        
        return new JsonResponse(JsonResponse::HTTP_OK);
      } else {
        $errors = $form->getErrors(true, false);
        // TODO: check that the errors are being shown
        return new JsonResponse($errors, JsonResponse::HTTP_BAD_REQUEST);
      }
    }
}

