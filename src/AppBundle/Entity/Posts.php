<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="posts")
 */
class Posts
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=140, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $imageUrl;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
    * @ORM\PrePersist
    */
    public function setCreatedAtValue() 
    {
      $this->createdAt = new \DateTime();
    }
}
