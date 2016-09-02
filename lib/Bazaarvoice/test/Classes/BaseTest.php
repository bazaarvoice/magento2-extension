<?php

require_once 'bvseosdk.php';
require_once 'test/config.php';

/**
 * Test Base class.
 */
class BaseTest extends PHPUnit_Framework_testCase {

  protected function getParams() {
    $params = array(
      'bv_root_folder' => 'test',
      'subject_id' => 'test',
      'cloud_key' => 'test',
      'staging' => FALSE,
      'testing' => FALSE,
      'crawler_agent_pattern' => 'msnbot|google|teoma|bingbot|yandexbot|yahoo',
      'ssl_enabled' => FALSE,
      'content_type' => 'reviews',
      'subject_type' => 'category',
      'execution_timeout' => 500,
      'execution_timeout_bot' => 2000,
      'local_seo_file_root' => '/load/seo/files/locally/',
      'load_seo_files_locally' => FALSE,
      'seo_sdk_enabled' => TRUE,
      'proxy_host' => '',
      'proxy_port' => '',
      'charset' => 'UTF-8',
      'base_url' => '/base/url',
      'page_url' => '/page/url&debug=true',
      'include_display_integration_code' => TRUE,
    );
    return $params;
  }

  protected static function getMethod($obj, $name) {
    $class = new ReflectionClass($obj);
    $method = $class->getMethod($name);
    $method->setAccessible(true);
    return $method;
  }

  public function test_buildComment() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $params['bvreveal'] = 'debug';
    $params['page'] = 5;
    $obj = new Base($params);
    $buildComment = self::getMethod($obj, '_buildComment');
    $res = $buildComment->invokeArgs($obj, array("getContent"));

    $this->assertContains('<li data-bvseo="staging">FALSE</li>', $res);
    $this->assertContains('<li data-bvseo="testing">FALSE</li>', $res);
    $this->assertContains('<li data-bvseo="seo.sdk.enabled">TRUE</li>', $res);
    $this->assertContains('<li data-bvseo="seo.sdk.ssl.enabled">FALSE</li>', $res);
    $this->assertContains('<li data-bvseo="proxyHost">none</li>', $res);
    $this->assertContains('<li data-bvseo="proxyPort">0</li>', $res);
    $this->assertContains('<li data-bvseo="seo.sdk.charset">UTF-8</li>', $res);
    $this->assertContains('<li data-bvseo="en">TRUE</li>', $res);
    $this->assertContains('<li data-bvseo="pn">bvseo-5</li>', $res);
    $this->assertContains('<li data-bvseo="userAgent">google</li>', $res);
    $this->assertContains('<li data-bvseo="pageURI">/page/url&debug=true</li>', $res);
    $this->assertContains('<li data-bvseo="baseURI">/base/url</li>', $res);
  }

  public function test_buildSeoUrl() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $page_number = 5;

    $params['testing'] = FALSE;
    $params['staging'] = TRUE;
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('http://seo-stg.bazaarvoice.com/test/test/reviews/category/5/test.htm', $res);

    $params['testing'] = FALSE;
    $params['staging'] = FALSE;
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('http://seo.bazaarvoice.com/test/test/reviews/category/5/test.htm', $res);

    $params['testing'] = TRUE;
    $params['staging'] = TRUE;
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('http://seo-qa-stg.bazaarvoice.com/test/test/reviews/category/5/test.htm', $res);
    $params['testing'] = TRUE;
    $params['staging'] = FALSE;
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('http://seo-qa.bazaarvoice.com/test/test/reviews/category/5/test.htm', $res);

    $params['testing'] = FALSE;
    $params['staging'] = FALSE;
    $params['ssl_enabled'] = FALSE;
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('http://seo.bazaarvoice.com/test/test/reviews/category/5/test.htm', $res);

    $params['ssl_enabled'] = TRUE;
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('https://seo.bazaarvoice.com/test/test/reviews/category/5/test.htm', $res);

    $params['ssl_enabled'] = FALSE;
    $params['content_sub_type'] = "stories";
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('http://seo.bazaarvoice.com/test/test/reviews/category/5/stories/test.htm', $res);

    $params['content_sub_type'] = "storiesgrid";
    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('http://seo.bazaarvoice.com/test/test/reviews/category/5/storiesgrid/test.htm', $res);

    unset($params['content_sub_type']);
    $params['load_seo_files_locally'] = TRUE;
    $params['local_seo_file_root'] = "/var/www/html/";

    $obj = new Base($params);
    $buildSeoUrl = self::getMethod($obj, '_buildSeoUrl');
    $res = $buildSeoUrl->invokeArgs($obj, array($page_number));

    $this->assertContains('/var/www/html/test/reviews/category/5/test.htm', $res);
  }

  public function test_charsetEncode() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $params['charset'] = 'Windows-1251';

    $obj = new Base($params);
    $charsetEncode = self::getMethod($obj, '_charsetEncode');

    $res = $charsetEncode->invokeArgs($obj, array("This is the Euro symbol 'в‚¬'"));
    $this->assertEquals("This is the Euro symbol '€'", $res);

    $res = $charsetEncode->invokeArgs($obj, array("РљРёСЂРёР»Р»РёС†Р°"));
    $this->assertEquals("Кириллица", $res);
  }

  public function test_checkCharset() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $params['charset'] = 'NOT_EXISTING_CHARSET';
    $obj = new Base($params);
    $checkCharset = self::getMethod($obj, '_checkCharset');

    $res = $checkCharset->invokeArgs($obj, array("Lorem ipsum dolor sit amet"));

    //should be set UTF-8 as default charset
    $this->assertEquals("UTF-8", $obj->config['charset']);

    $params['charset'] = 'SJIS';
    $obj = new Base($params);
    $checkCharset = self::getMethod($obj, '_checkCharset');

    $res = $checkCharset->invokeArgs($obj, array("Lorem ipsum dolor sit amet"));

    //should be set SJIS as predefined
    $this->assertEquals("SJIS", $obj->config['charset']);
  }

  public function test_fetchCloudContent() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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
        ->will($this->returnValue('<div id="BVRRContainer">Mock content for unit tests.</div>'));
    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 403, 'total_time' => 100)));


    $path = 'test/data/universalSEO.html';
    $res = '';
    $fetchCloudContent = self::getMethod($obj, '_fetchCloudContent');
    $res = $fetchCloudContent->invokeArgs($obj, array($path));
    $this->assertEmpty($res);
    $this->assertContains("HTTP status code of 403 was returned", $obj->getBVMessages());

    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 200, 'total_time' => 100)));


    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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
        ->will($this->returnValue('<div id="BVRRContainer">Mock content for unit tests.</div>'));
    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 200, 'total_time' => 100)));

    $res = '';
    $fetchCloudContent = self::getMethod($obj, '_fetchCloudContent');
    $res = $fetchCloudContent->invokeArgs($obj, array($path));
    $this->assertNotEmpty($res);
    $this->assertContains("Mock content for unit tests", $res);
  }

  public function test_fetchFileContent() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $path = 'test/data/universalSEO.html';

    $obj = new Base($params);
    $fetchFileContent = self::getMethod($obj, '_fetchFileContent');

    $res = $fetchFileContent->invokeArgs($obj, array($path));

    $this->assertContains("Content for unit tests", $res);

    $path = 'unexisting/test/path/universalSEO.html';

    $obj = new Base($params);
    $fetchFileContent = self::getMethod($obj, '_fetchFileContent');

    $res = $fetchFileContent->invokeArgs($obj, array($path));

    $this->assertFalse($res);
    $this->assertContains('Trying to get content from "unexisting/test/path/universalSEO.html". The resource file is currently unavailable;', $obj->getBVMessages());
  }

  public function test_fetchSeoContent() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $params['load_seo_files_locally'] = FALSE;

    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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
        ->will($this->returnValue('<div id="BVRRContainer">Mock content for unit tests.</div>'));
    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 200, 'total_time' => 100)));


    $path = 'test/data/universalSEO.html';
    $fetchSeoContent = self::getMethod($obj, '_fetchSeoContent');
    $res = $fetchSeoContent->invokeArgs($obj, array($path));

    $this->assertContains("Mock content for unit tests.", $res);

    $params['load_seo_files_locally'] = TRUE;
    $obj = new Base($params);
    $fetchSeoContent = self::getMethod($obj, '_fetchSeoContent');
    $res = $fetchSeoContent->invokeArgs($obj, array($path));

    $this->assertContains("Content for unit tests.", $res);
  }

  public function test_getFullSeoContents() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $params['seo_sdk_enabled'] = TRUE;
    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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
        ->will($this->returnValue('<div id="BVRRContainer">Mock content for unit tests.</div>'));
    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 200, 'total_time' => 100)));


    $getFullSeoContents = self::getMethod($obj, '_getFullSeoContents');

    $res = $getFullSeoContents->invokeArgs($obj, array("getContent"));

    $this->assertNotEmpty($res);
    $this->assertContains("Mock content for unit tests", $res);

    $params['seo_sdk_enabled'] = FALSE;
    $obj = new Base($params);
    $getFullSeoContents = self::getMethod($obj, '_getFullSeoContents');
    $res = $getFullSeoContents->invokeArgs($obj, array("getContent"));
    $this->assertEmpty($res);
    $this->assertContains("SEO SDK is disabled", $obj->getBVMessages());
  }

  public function test_getPageNumber() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $params['page_url'] = "/";

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(1, $res);


    // Page overrides all consideration of content types.
    $params['page'] = 5;

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(5, $res);

    // ----------------------------------------------------------------------
    // Test match between content type and parameter.
    // ----------------------------------------------------------------------

    unset($params['page']);
    $params['bv_page_data']['bvrrp'] = 'Main_Site-en_US/reviews/product/2/00636.htm';
    $params['content_type'] = 'reviews';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(2, $res);

    unset($params['bv_page_data']['bvrrp']);
    $params['bv_page_data']['bvqap'] = '/Main_Site-en_US/questions/product/3/00636.htm';
    $params['content_type'] = 'questions';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(3, $res);

    unset($params['bv_page_data']['bvqap']);
    $params['bv_page_data']['bvsyp'] = '/Main_Site-en_US/stories/product/3/00636.htm';
    $params['content_type'] = 'stories';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(3, $res);

    $params['page_params']['base_url_bvstate'] = TRUE;
    $params['page_params']['content_type'] = 'reviews';
    $params['page_params']['page'] = 7;
    $params['content_type'] = 'reviews';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(7, $res);

    // ----------------------------------------------------------------------
    // Now test mismatched content types.
    // ----------------------------------------------------------------------

    // It should still use the legacy parameter page number - only bvstate cares
    // about matching content type.

    $params['page_params'] = array();
    $params['bv_page_data'] = array();

    $params['bv_page_data']['bvrrp'] = 'Main_Site-en_US/reviews/product/2/00636.htm';
    $params['content_type'] = 'questions';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(2, $res);

    unset($params['bv_page_data']['bvrrp']);
    $params['bv_page_data']['bvqap'] = '/Main_Site-en_US/questions/product/3/00636.htm';
    $params['content_type'] = 'stories';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(3, $res);

    unset($params['bv_page_data']['bvqap']);
    $params['bv_page_data']['bvsyp'] = '/Main_Site-en_US/stories/product/3/00636.htm';
    $params['content_type'] = 'reviews';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(3, $res);

    // bvstate cares about matching content type - it'll default to page 1 if
    // there is no match.
    $params['page_params']['base_url_bvstate'] = TRUE;
    $params['page_params']['content_type'] = 'reviews';
    $params['page_params']['page'] = 7;
    $params['content_type'] = 'stories';

    $obj = new Base($params);
    $getPageNumber = self::getMethod($obj, '_getPageNumber');
    $res = $getPageNumber->invokeArgs($obj, array());
    $this->assertEquals(1, $res);
  }

  public function test_isBot() {
    $params = $this->getParams();

    $_SERVER['HTTP_USER_AGENT'] = "NON_EXISTING";
    $params['bvreveal'] = 'something';

    $obj = new Base($params);
    $isBot = self::getMethod($obj, '_isBot');
    $res = $isBot->invokeArgs($obj, array());
    $this->assertFalse($res);

    $_SERVER['HTTP_USER_AGENT'] = "NON_EXISTING";
    $params['bvreveal'] = 'debug';

    $obj = new Base($params);
    $isBot = self::getMethod($obj, '_isBot');
    $res = $isBot->invokeArgs($obj, array());
    $this->assertTrue($res);

    $_SERVER['HTTP_USER_AGENT'] = "NON_EXISTING";
    unset($params['bvreveal']);

    $obj = new Base($params);
    $isBot = self::getMethod($obj, '_isBot');
    $res = $isBot->invokeArgs($obj, array());
    $res = !empty($res);
    $this->assertFalse($res);

    $_SERVER['HTTP_USER_AGENT'] = "google";
    unset($params['bvreveal']);

    $obj = new Base($params);
    $isBot = self::getMethod($obj, '_isBot');
    $res = $isBot->invokeArgs($obj, array());
    $res = !empty($res);
    $this->assertTrue($res);
  }

  public function test_isSdkEnabled() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $params['seo_sdk_enabled'] = TRUE;
    $obj = new Base($params);
    $isSdkEnabled = self::getMethod($obj, '_isSdkEnabled');
    $res = $isSdkEnabled->invokeArgs($obj, array());
    $this->assertTrue($res);

    $params['seo_sdk_enabled'] = FALSE;
    $params['bvreveal'] = 'debug';
    $obj = new Base($params);
    $isSdkEnabled = self::getMethod($obj, '_isSdkEnabled');
    $res = $isSdkEnabled->invokeArgs($obj, array());
    $this->assertTrue($res);

    $params['seo_sdk_enabled'] = FALSE;
    $params['bvreveal'] = 'not_debug';
    $obj = new Base($params);
    $isSdkEnabled = self::getMethod($obj, '_isSdkEnabled');
    $res = $isSdkEnabled->invokeArgs($obj, array());
    $this->assertFalse($res);
  }

  public function test_getBVReveal() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";

    $params['bvreveal'] = 'debug';
    $obj = new Base($params);
    $bvReveal = self::getMethod($obj, '_getBVReveal');
    $res = $bvReveal->invokeArgs($obj, array());
    $this->assertTrue($res);

    $params['bvreveal'] = 'not_debug';
    $obj = new Base($params);
    $bvReveal = self::getMethod($obj, '_getBVReveal');
    $res = $bvReveal->invokeArgs($obj, array());
    $this->assertFalse($res);

    unset($params['bvreveal']);

    $params['page_params']['bvreveal'] = 'debug';
    $obj = new Base($params);
    $bvReveal = self::getMethod($obj, '_getBVReveal');
    $res = $bvReveal->invokeArgs($obj, array());
    $this->assertTrue($res);

    $params['page_params']['bvreveal'] = 'not_debug';
    $obj = new Base($params);
    $bvReveal = self::getMethod($obj, '_getBVReveal');
    $res = $bvReveal->invokeArgs($obj, array());
    $this->assertFalse($res);

    unset($params['page_params']['bvreveal']);

    $params['bv_page_data']['bvreveal'] = 'debug';
    $obj = new Base($params);
    $bvReveal = self::getMethod($obj, '_getBVReveal');
    $res = $bvReveal->invokeArgs($obj, array());
    $this->assertTrue($res);

    $params['bv_page_data']['bvreveal'] = 'not_debug';
    $obj = new Base($params);
    $bvReveal = self::getMethod($obj, '_getBVReveal');
    $res = $bvReveal->invokeArgs($obj, array());
    $this->assertFalse($res);
  }

  public function test_renderAggregateRating() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";

    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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
            <!--begin-aggregate-rating--> Mock aggeagate rating  <!--end-aggregate-rating-->
            <!--begin-reviews--> Mock reviews <!--end-reviews-->
            <!--begin-pagination--> Mock pagination  <!--end-pagination-->
            <p>Mock content for unit tests.</p>
          </div>'));
    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 200, 'total_time' => 100)));


    $renderAggregateRating = self::getMethod($obj, '_renderAggregateRating');

    $res = $renderAggregateRating->invokeArgs($obj, array());

    $this->assertNotEmpty($res);
    $this->assertContains("Mock aggeagate rating", $res);
    $this->assertNotContains("Mock reviews", $res);
    $this->assertNotContains("Mock pagination", $res);
  }

  public function test_renderReviews() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";

    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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
            <!--begin-aggregate-rating--> Mock aggeagate rating  <!--end-aggregate-rating-->
            <!--begin-reviews--> Mock reviews <!--end-reviews-->
            <!--begin-pagination--> Mock pagination  <!--end-pagination-->
            <p>Mock content for unit tests.</p>
          </div>'));
    $obj->expects($this->any())
        ->method('curlInfo')
        ->will($this->returnValue(array('http_code' => 200, 'total_time' => 100)));


    $renderReviews = self::getMethod($obj, '_renderReviews');

    $res = $renderReviews->invokeArgs($obj, array());

    $this->assertNotEmpty($res);
    $this->assertNotContains("Mock aggeagate rating", $res);
    $this->assertContains("Mock reviews", $res);
    $this->assertContains("Mock pagination", $res);
  }

  public function test_renderSEO() {
    $params = $this->getParams();
    //is bot
    $_SERVER['HTTP_USER_AGENT'] = "google";

    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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

    $renderSEO = self::getMethod($obj, '_renderSEO');

    $res = $renderSEO->invokeArgs($obj, array('getContent'));

    $this->assertNotEmpty($res);
    $this->assertContains("Mock content for unit tests", $res);

    //is not bot
    $_SERVER['HTTP_USER_AGENT'] = "NOT_BOT";
    $params['execution_timeout'] = 0;
    $obj = $this->getMockBuilder('Base')
        ->setConstructorArgs(array($params))
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

    $renderSEO = self::getMethod($obj, '_renderSEO');

    $res = $renderSEO->invokeArgs($obj, array('getContent'));

    $this->assertNotEmpty($res);
    $this->assertContains("JavaScript-only Display", $res);
  }

  public function test_replaceSection() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $obj = new Base($params);
    $replaceSection = self::getMethod($obj, '_replaceSection');
    $str = '<div id="BVRRContainer"><!--begin-reviews--> Mock reviews <!--end-reviews--></div>';
    $search_str_begin = '<!--begin-reviews-->';
    $search_str_end = '<!--end-reviews-->';
    $res = $replaceSection->invokeArgs($obj, array($str, $search_str_begin, $search_str_end));

    $this->assertNotContains("<!--begin-reviews--> Mock reviews <!--end-reviews-->", $res);
    $this->assertEquals('<div id="BVRRContainer"></div>', $res);
  }

  public function test_replaceTokens() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $str = 'Base url: {INSERT_PAGE_URI}';

    $base_url = 'http://www.base.url';
    $params['base_url'] = $base_url;
    $obj = new Base($params);
    $replaceTokens = self::getMethod($obj, '_replaceTokens');
    $res = $replaceTokens->invokeArgs($obj, array($str));
    $this->assertEquals('Base url: http://www.base.url?', $res);

    $base_url = 'http://www.base.url?debug=true';
    $params['base_url'] = $base_url;
    $obj = new Base($params);
    $replaceTokens = self::getMethod($obj, '_replaceTokens');
    $res = $replaceTokens->invokeArgs($obj, array($str));
    $this->assertEquals('Base url: http://www.base.url?debug=true&amp;', $res);

    $base_url = 'http://www.base.url?_escaped_fragment_=';
    $params['base_url'] = $base_url;
    $obj = new Base($params);
    $replaceTokens = self::getMethod($obj, '_replaceTokens');
    $res = $replaceTokens->invokeArgs($obj, array($str));
    $this->assertEquals('Base url: http://www.base.url?_escaped_fragment_=', $res);

    $base_url = 'http://www.base.url?_escaped_fragment_=a=b';
    $params['base_url'] = $base_url;
    $obj = new Base($params);
    $replaceTokens = self::getMethod($obj, '_replaceTokens');
    $res = $replaceTokens->invokeArgs($obj, array($str));
    $this->assertEquals('Base url: http://www.base.url?_escaped_fragment_=a=b%26', $res);

    // Escaping things.
    $base_url = 'http://www.base.url?<>&"\'';
    $params['base_url'] = $base_url;
    $obj = new Base($params);
    $replaceTokens = self::getMethod($obj, '_replaceTokens');
    $res = $replaceTokens->invokeArgs($obj, array($str));
    $this->assertEquals('Base url: http://www.base.url?&lt;&gt;&amp;&quot;&apos;&amp;', $res);

    // Don't double-escape.
    $base_url = 'http://www.base.url?&lt;&gt;&amp;&quot;&apos;';
    $params['base_url'] = $base_url;
    $obj = new Base($params);
    $replaceTokens = self::getMethod($obj, '_replaceTokens');
    $res = $replaceTokens->invokeArgs($obj, array($str));
    $this->assertEquals('Base url: http://www.base.url?&lt;&gt;&amp;&quot;&apos;&amp;', $res);
  }

  public function test_setBuildMessage() {
    $params = $this->getParams();
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $msg1 = 'The message1;';
    $msg2 = 'The message2';
    $obj = new Base($params);
    $setBuildMessage = self::getMethod($obj, '_setBuildMessage');

    $setBuildMessage->invokeArgs($obj, array($msg1));
    $setBuildMessage->invokeArgs($obj, array($msg2));
    $res = $obj->getBVMessages();

    $this->assertEquals(' The message1; The message2;', $res);
  }

}
