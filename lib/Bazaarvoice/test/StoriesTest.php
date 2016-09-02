<?php

require_once 'bvseosdk.php';
require_once 'test/config.php';

/**
 * Test class to test Stories.
 */
class StoriesImplementingTest extends PHPUnit_Framework_testCase
{
  var $params = array(
    'bv_root_folder' => 'test',
    'subject_id' => 'test',
    'cloud_key' => 'test',
    'staging' => FALSE,
    'testing' => FALSE,
    'crawler_agent_pattern' => 'msnbot|google|teoma|bingbot|yandexbot|yahoo',
    'ssl_enabled' => FALSE,
    'content_type' => 'product',
    'subject_type' => 'category',
    'execution_timeout' => 500,
    'execution_timeout_bot' => 2000,
    'load_seo_files_locally' => FALSE,
    'seo_sdk_enabled' => TRUE,
    'proxy_host' => '',
    'proxy_port' => '',
    'charset' => 'UTF-8',
    'base_url' => '/base/url',
    'page_url' => '/page/url?debug=true',
    'include_display_integration_code' => TRUE,
  );

  public function testStories() {
    // to force is_bot mode
    $_SERVER['HTTP_USER_AGENT'] = 'google';

    $obj = $this->getMockBuilder('Stories')
        ->setConstructorArgs(array($this->params))
        ->setMethods(['curlError', 'curlErrorNo', 'curlExecute', 'curlInfo'])
        ->getMock();
    $obj->expects($this->any())
        ->method('curlError')
        ->will($this->returnValue('No errors'));
    $obj->expects($this->any())
        ->method('curlErrorNo')
        ->will($this->returnValue(0));
    $obj->expects($this->any())
        ->method('curlExecute')
        ->will($this->returnValue('<div id="BVRRContainer">
            <p>Mock content for unit tests.</p>
          </div>'));
    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 200, 'total_time' => 100)));

    $res = $obj->getContent();

    $this->assertNotNull($res);
    $this->assertContains('Mock content for unit tests', $res);
    $this->assertContains('STORIES, PRODUCT', $res);
    $this->assertNotContains('Error - ', $res);
  }

}
