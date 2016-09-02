<?php

require_once 'bvseosdk.php';
require_once 'test/config.php';

/**
 * Test BVFooter class.
 */
class BVFooterTest extends PHPUnit_Framework_testCase {

  protected function getParams() {
    $params = array(
      'bv_root_folder' => 'test',
      'subject_id' => 'test',
      'cloud_key' => 'test',
      'staging' => FALSE,
      'testing' => TRUE,
      'seo_sdk_enabled' => TRUE,
      'ssl_enabled' => TRUE,
      'crawler_agent_pattern' => 'msnbot|google|teoma|bingbot|yandexbot|yahoo',
      'bvreveal' => 'debug',
      'content_type' => 'product',
      'subject_type' => 'category',
      'execution_timeout' => 300,
      'execution_timeout_bot' => 400,
      'charset' => 'UTF-8',
      'load_seo_files_locally' => FALSE,
      'local_seo_file_root' => '/test/local/file',
      'base_url' => '/base/url',
      'page_url' => '/page/url'
    );
    return $params;
  }

  /**
   * Test footer.
   */
  public function testBuildSDKFooter() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";

    $base = new Base($params);

    $access_method = 'getContent';
    $msg = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit,'
        . ' sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
    $base->start_time = "500";
    $bvFooter = new BVFooter($base, $access_method, $msg);
    $res = $bvFooter->buildSDKFooter();

    $this->assertContains('li data-bvseo="ms">bvseo-msg:', $res);
    $this->assertContains('data-bvseo="sdk">bvseo_sdk, p_sdk,', $res);
    $this->assertContains('<li data-bvseo="sp_mt">CLOUD, getContent,', $res);
    $this->assertContains('<li data-bvseo="ct_st">PRODUCT, CATEGORY</li>', $res);
  }

  /**
   * Test debug footer.
   */
  public function testBuildSDKDebugFooter() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";

    $base = new Base($params);

    $access_method = 'getContent';
    $msg = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit,'
        . ' sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
    $base->seo_url = "test.com";
    $base->config['page'] = "2";
    $base->config['base_url'] = "test.bv";
    $base->config['page_url'] = "test.bv&debug=true";

    $bvFooter = new BVFooter($base, $access_method, $msg);
    $res = $bvFooter->buildSDKDebugFooter();

    $this->assertContains('<li data-bvseo="staging">FALSE</li>', $res);
    $this->assertContains('<li data-bvseo="testing">TRUE</li>', $res);
    $this->assertContains('<li data-bvseo="seo.sdk.enabled">TRUE</li>', $res);
    $this->assertContains('<li data-bvseo="seo.sdk.ssl.enabled">TRUE</li>', $res);
    $this->assertContains('<li data-bvseo="proxyHost">none</li>', $res);
    $this->assertContains('<li data-bvseo="proxyPort">0</li>', $res);
    $this->assertContains('<li data-bvseo="seo.sdk.charset">UTF-8</li>', $res);
    $this->assertContains('<li data-bvseo="en">TRUE</li>', $res);
    $this->assertContains('<li data-bvseo="pn">bvseo-2</li>', $res);
    $this->assertContains('<li data-bvseo="userAgent">google</li>', $res);
    $this->assertContains('<li data-bvseo="pageURI">test.bv&debug=true</li>', $res);
    $this->assertContains('<li data-bvseo="baseURI">test.bv</li>', $res);
    $this->assertContains('<li data-bvseo="contentURL">test.com</li>', $res);
  }

}
