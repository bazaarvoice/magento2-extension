<?php

require_once 'bvseosdk.php';
require_once 'test/config.php';

/**
 * Test class to test Spotlights.
 */
class SpotlightsImplementingTest extends PHPUnit_Framework_testCase
{
  public $cloud_key = 'test';
  public $deployment_zone_id = 'test';
  public $product_id = 'test';

  /**
   * Test spotlights.
   */
  public function testSpotlights() {
    $_SERVER['HTTP_USER_AGENT'] = 'google';

    $bv = new BV(array(
      'bv_root_folder' => $this->deployment_zone_id,
      'subject_id' => $this->product_id,
      'cloud_key' => $this->cloud_key,
      'bvreveal' => 'debug'
    ));

    $content = $bv->spotlights->getContent();
    $this->assertNotNull($content, "There should be content to proceed further assertion!!");
    $this->assertContains('seo.bazaarvoice.com/test/test/spotlights/category/1/test.htm', $content);
    $this->assertContains('getContent', $content);

  }

}
