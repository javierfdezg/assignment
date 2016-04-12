<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity;

class StatisticsController extends Controller
{
    /**
     * @Route("/stats/{type}", requirements={ 
     *  "type": "views|posts"
     * })
     * @Method("GET")
      */
    public function getAction($type)
    {
      $em = $this->getDoctrine()->getManager();
      $stats = $em->getRepository('AppBundle:Statistics')
        ->findByType($type);

      if (!$stats) {
        return new JsonResponse(JsonResponse::HTTP_NOT_FOUND);
      } else {
        return new JsonResponse(array($type=>$stats[0]->getCount()));
      }
    }

}

