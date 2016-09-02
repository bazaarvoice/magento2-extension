<?php

require_once 'BVUtility.php';
require_once 'test/config.php';

/**
 * Test BVUtility class.
 */
class BVUtilityTest extends PHPUnit_Framework_testCase {

  /**
   * Test parseUrlParameters.
   */
  public function testParseUrlParameters() {
    $url = 'http://example.com';
    $expected = array();
    $actual = BVUtility::parseUrlParameters($url);
    $this->assertEquals($expected, $actual);

    $url = 'http://example.com?a=b&c=d&amp;e=f#!x/y/z?g=h&i=j';
    $expected = array(
      'a' => 'b',
      'c' => 'd',
      'e' => 'f',
      'g' => 'h',
      'i' => 'j'
    );
    $actual = BVUtility::parseUrlParameters($url);
    $this->assertEquals($expected, $actual);

    $url = 'http://example.com?a=b&c=d&amp;e=f#x/y/z?g=h&i=j';
    $expected = array(
      'a' => 'b',
      'c' => 'd',
      'e' => 'f',
      'g' => 'h',
      'i' => 'j'
    );
    $actual = BVUtility::parseUrlParameters($url);
    $this->assertEquals($expected, $actual);

    $url = 'http://example.com?a=b&c=d&amp;e=f&_escaped_fragment_=x/y/z?g=h%26i=j';
    $expected = array(
      'a' => 'b',
      'c' => 'd',
      'e' => 'f',
      'g' => 'h',
      'i' => 'j'
    );
    $actual = BVUtility::parseUrlParameters($url);
    $this->assertEquals($expected, $actual);

    $url = 'http://example.com?a=b1&c=d1&amp;e=f1#x/y/z?a=b2&c=d1';
    $expected = array(
      'a' => 'b1',
      'c' => 'd1',
      'e' => 'f1',
    );
    $actual = BVUtility::parseUrlParameters($url);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test removeUrlParam.
   */
  public function testRemoveUrlParam() {
    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?a=b&c=d#x/y/z';
    $expected = $url;
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);

    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?bvstate=pg:4/ct:r&a=b&c=d';
    $expected = 'http://example.com/product/123?a=b&c=d';
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);

    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?bvstate=pg:4/ct:r&amp;a=b&c=d';
    $expected = 'http://example.com/product/123?a=b&c=d';
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);

    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?bvstate=pg:4/ct:r&';
    $expected = 'http://example.com/product/123?';
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);

    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?a=b&c=d&bvstate=pg:4/ct:r';
    $expected = 'http://example.com/product/123?a=b&c=d&';
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);

    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?a=b&c=d&bvstate=pg:4/ct:r#!x/y/z';
    $expected = 'http://example.com/product/123?a=b&c=d&#!x/y/z';
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);

    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?a=b#!x/y/z?bvstate=pg:4/ct:r&c=d&e=f';
    $expected = 'http://example.com/product/123?a=b#!x/y/z?c=d&e=f';
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);

    $parameter = 'bvstate';
    $url = 'http://example.com/product/123?a=b&_escaped_fragment_=x/y/z?bvstate=pg:4/ct:r%26c=d%26e=f';
    $expected = 'http://example.com/product/123?a=b&_escaped_fragment_=x/y/z?c=d%26e=f';
    $actual = BVUtility::removeUrlParam($url, $parameter);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test getBVStateParams.
   */
  public function testGetBVStateParams() {
    // Everything.
    $bvstate = 'ct:r/st:p/pg:3/id:xyz/reveal:debug';
    $expected = array(
      'content_type' => 'reviews',
      'subject_type' => 'product',
      'page' => '3',
      'subject_id' => 'xyz',
      'bvreveal' => 'debug',
      'base_url_bvstate' => true,
    );
    $actual = BVUtility::getBVStateParams($bvstate);
    $this->assertEquals($expected, $actual);

    // Ignore bvstate if no ct property.
    $bvstate = 'st:p/pg:3/id:xyz/reveal:debug';
    $expected = array();
    $actual = BVUtility::getBVStateParams($bvstate);
    $this->assertEquals($expected, $actual);

    // Bare minimum with default page.
    $bvstate = 'ct:r';
    $expected = array(
      'content_type' => 'reviews',
      'page' => '1',
      'base_url_bvstate' => true,
    );
    $actual = BVUtility::getBVStateParams($bvstate);
    $this->assertEquals($expected, $actual);

    // Junk value.
    $bvstate = 'not a real value';
    $expected = array();
    $actual = BVUtility::getBVStateParams($bvstate);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test for exception in getBVStateParams.
   *
   * @expectedException Exception
   */
  public function testExceptionInGetBVStateParams_1() {
    // Invalid ct value.
    $bvstate = 'ct:x';
    $actual = BVUtility::getBVStateParams($bvstate);
  }

  /**
   * Test for exception in getBVStateParams.
   *
   * @expectedException Exception
   */
  public function testExceptionInGetBVStateParams_2() {
    // Invalid st value.
    $bvstate = 'ct:r/st:x';
    $actual = BVUtility::getBVStateParams($bvstate);
  }

  /**
   * Test mb_trim.
   */
  public function testMbTrim() {
    $str = " \n x x x \n ";
    $expected = 'x x x';
    $actual = BVUtility::mb_trim($str);
    $this->assertEquals($expected, $actual);
  }

}
