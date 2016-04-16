<?php

namespace AppBundle\Libs;

use Aws\S3\S3Client;

/**
 * Common Utility class
 */
class CommonUtils
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
   * Upload a $sourceFile to predefined S3 bucket and with the
   * provided $fileName.
   *
   * @param $fileName string  Name of the file
   * @param $sourceFile string Path of the file
   *
   * @return array  Result object of the S3 client
   */
  public function uploadToS3($fileName, $sourceFile) {

    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => 'eu-west-1',
        'profile' => 'insided'
      ]);

    $result = $s3->putObject([
      'Bucket' => 'insided',
      'Key'    => $fileName,
      'SourceFile' => $sourceFile
    ]);

    return $result;
  }

  /**
   * Execute provided query using the provided connection
   *
   * @param $connection Object Entity Manager connection
   * @param $query string Query to execute
   *
   * @return executeQuery result
   */
  public function executeQuery($connection, $query) {
    $connection->executeQuery($query);
  }

}
