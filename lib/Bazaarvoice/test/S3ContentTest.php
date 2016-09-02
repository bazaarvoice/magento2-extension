<?php

require_once 'bvseosdk.php';
require_once 'test/config.php';

/**
 * Test testing S3 content.
 */
class S3ContentTest extends PHPUnit_Framework_testCase
{
  public $cloud_key = 'test';
  public $deployment_zone_id = 'test';
  public $product_id = 'test';

  // Use reflection to test private methods
  protected static function getMethod($name) {
    $class = new ReflectionClass('Base');
    $method = $class->getMethod($name);
    $method->setAccessible(true);
    return $method;
  }

  var $params = array(
    'bv_root_folder' => 'test',
    'subject_id' => 'test',
    'cloud_key' => 'test',
    'staging' => TRUE,
    'testing' => TRUE,
    'crawler_agent_pattern' => 'msnbot|google|teoma|bingbot|yandexbot|yahoo',
    'ssl_enabled' => FALSE,
    'content_type' => 'reviews',
    'subject_type' => 'product',
    'execution_timeout' => 500,
    'execution_timeout_bot' => 2000
  );

  public function testS3InSeoUrl_seo_qa_stg() {
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $this->params['staging'] = TRUE;
    $this->params['bvreveal'] = 'sdk_disabled';
    $buildSeoUrl = self::getMethod('_buildSeoUrl');

    $obj = new Base($this->params);
    $res = $buildSeoUrl->invokeArgs($obj, array(1));

    //echo $res;
    $this->assertContains("seo-qa-stg.bazaarvoice.com", $res);
  }

  public function testS3InSeoUrl_seo_qa() {
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $this->params['staging'] = FALSE;
    $this->params['bvreveal'] = 'sdk_disabled';

    $buildSeoUrl = self::getMethod('_buildSeoUrl');

    $obj = new Base($this->params);
    $res = $buildSeoUrl->invokeArgs($obj, array(1));

    //echo $res;
    $this->assertContains("seo-qa.bazaarvoice.com", $res);
  }

}
