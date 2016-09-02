<?php

require_once 'bvseosdk.php';
require_once 'test/config.php';

/**
 * Test class for testing charset stting
 */
class CharsetTest extends PHPUnit_Framework_testCase
{
  var $params = array(
    'execution_timeout' => 5000,
    'execution_timeout_bot' => 5000,
    'bvreveal' => 'debug',
    'crawler_agent_pattern' => 'msnbot|google|teoma|bingbot|yandexbot|yahoo',
  );

  // Use reflection to test private methods
  protected static function getMethod($name) {
    $class = new ReflectionClass('Base');
    $method = $class->getMethod($name);
    $method->setAccessible(true);
    return $method;
  }

  /**
   * Test charset.
   */
  public function testCharsetEncode() {
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $this->params['charset'] = 'Windows-1251';

    $charsetEncode = self::getMethod('_charsetEncode');

    $obj = new Base($this->params);
    $a = $charsetEncode->invokeArgs($obj, array("This is the Euro symbol 'в‚¬'"));
    $this->assertEquals("This is the Euro symbol '€'", $a);

    $b = $charsetEncode->invokeArgs($obj, array("РљРёСЂРёР»Р»РёС†Р°"));
    $this->assertEquals("Кириллица", $b);
  }

  public function testCharsetCheck() {
    $_SERVER['HTTP_USER_AGENT'] = "google";
    $this->params['charset'] = 'NOT_EXISTING_CHARSET';

    $checkCharset = self::getMethod('_checkCharset');

    // Check for set to default
    $obj = new Base($this->params);
    $checkCharset->invokeArgs($obj, array("Hello world!"));
    $this->assertEquals("UTF-8", $obj->config['charset']);

    // Check correct charset
    $this->params['charset'] = 'UTF-16';
    $obj = new Base($this->params);
    $checkCharset->invokeArgs($obj, array("Hello world!"));
    $this->assertEquals("UTF-16", $obj->config['charset']);
  }

}
