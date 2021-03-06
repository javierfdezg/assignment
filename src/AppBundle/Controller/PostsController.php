<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Posts;
use AppBundle\Entity\Statistics;
use AppBundle\Libs\CommonUtils;
use AppBundle\Libs\PostsUtils;
use JMS\SerializerBundle\Annotation\Exclude;
use JMS\SerializerBundle\Annotation\ExclusionPolicy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostsController extends Controller
{
    /**
    * @Route("/posts/export")
    * @Method("GET")
    *
    * Returns an URI/URL with a zip file containing all the posts' images
    * and a CSV file with the posts' titles and image names
    */
    public function exportAction()
    {
      $em = $this->getDoctrine()->getManager();
      $posts = $em->getRepository('AppBundle:Posts')
        ->findBy(
          array(),
          array('createdAt'=>'ASC')
        );

      // Generate zip and upload it to S3
      $result = PostsUtils::getInstance()->generateExportResource($posts, $uplaod = true);

      if (null === $result)
      {
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
      }
      else
      {
        return new JsonResponse(array('resource'=>$result['ObjectURL']), JsonResponse::HTTP_OK);
      }
    }

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
          return new JsonResponse(array(), JsonResponse::HTTP_OK);
        } else {
          return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
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
    public function getFromAction($id = -1)
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
          return new JsonResponse(array(), JsonResponse::HTTP_OK);
        } else {
          return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }
      } else {
        $serializer = $this->container->get('serializer');
        $posts = $serializer->serialize($posts, 'json');
        return new Response($posts);
      }
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

        $query = 'UPDATE statistics SET count=count+1 WHERE type="posts"';
        CommonUtils::getInstance()->executeQuery($em->getConnection(), $query);

        // Notify the connected clients that there are new posts
        CommonUtils::getInstance()->sendWebSocketMessage('posts');


        // Upload image to S3 and store the url
        $result = CommonUtils::getInstance()->uploadToS3($post->getPath(), $post->getAbsolutePath());
        $post->setImageUrl($result['ObjectURL']);

        $em->persist($post);
        $em->flush();

        // Delete the image once is in s3
        unlink($post->getAbsolutePath());

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
      } else {
        $errors = $form->getErrors(true, false)->getForm();

        $serializer = $this->container->get('serializer');
        $errors = $serializer->serialize($errors, 'json');
        return new Response($errors, Response::HTTP_BAD_REQUEST);
      }
    }
}

