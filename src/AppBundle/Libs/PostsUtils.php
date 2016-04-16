<?php

namespace AppBundle\Libs;

use AppBundle\Entity\Posts;
use AppBundle\Libs\CommonUtils;

/**
 * Utility class for posts
 */
class PostsUtils
{

  private static $instance;

  protected function _construct()
  {
    // Make sure no one instantiates this class
    ;;
  }

  private function __clone()
  {
    // Make sure no one clones this class
    ;;
  }

  public static function getInstance()
  {
      if (null === static::$instance) {
          static::$instance = new static();
      }
      
      return static::$instance;
  }

  /**
   * Creates an unique file name in a given 
   * path
   *
   * @param $path string  Path where the file is unique. Defaults to /tmp
   * @param $extension string Extension of the file. Defaults to .zip
   *
   * @return string name of the file (does not include the $path)
   */
  private function getUniqueFileName($path = '/tmp', $extension = '.zip') {
      $id = uniqId();
      $fileName = $id.$extension;
      while (file_exists("$path/$fileName")) {
        $id = uniqId();
        $fileName = $id.$extension;
      };

      return $fileName;
  }

  /**
   * Creates a zip file containing
   *
   * - All the posts' images
   * - CSV file with posts' title and image name
   *
   * and uploads it to AWS S3 bucket
   *
   * @param $posts Posts Posts resource to iterate
   * @param $upload boolean Upload to S3. Defaults to true
   *
   * @return array resource URI/URL 
   */
  public function generateExportResource($posts, $upload = true)
  {
      if (!$posts) 
      {
        return null;
      } 
      else 
      {

        // Prepare ZIP file
        $zipFileName = $this->getUniqueFileName('/tmp', '.zip');
        $zip = new \ZipArchive();
        $zip->open("/tmp/$zipFileName", \ZipArchive::CREATE);

        // Prepare CSV file
        $csv = "/tmp/".baseName($zipFileName, '.zip').".csv";
        $header = '"Title","Filename"'.PHP_EOL;
        file_put_contents($csv, $header, FILE_APPEND);

        // Process each post
        foreach ($posts as $post) 
        {
          if ($post->getImageUrl()) 
          {
            $image = file_get_contents($post->getImageUrl());
            $imageFileName = urldecode(baseName($post->getImageUrl()));

            $zip->addFromString($imageFileName, $image);
          }
          else
          {
            $imageFileName = '';
          } 

          $row = '"' . $post->getTitle() . '","'.$imageFileName;
          $row .= '"'.PHP_EOL;
          file_put_contents($csv, $row, FILE_APPEND);
        }

        // Pack the csv file into the zip
        $zip->addFromString('posts.csv', file_get_contents($csv));

        // We're done!
        $zip->close();

        if ($upload === true) 
        {
          $result = CommonUtils::getInstance()->uploadToS3($zipFileName, "/tmp/$zipFileName");
          unlink("/tmp/$zipFileName");
        } 
        else 
        {
          $result = array('ObjectURL'=>"/tmp/$zipFileName");
        }

        unlink($csv);

        return $result;
      }
  }
}
