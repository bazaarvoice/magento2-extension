<?php

/**
 * Tick function for execTimer.
 *
 * @param int ($start) - start time in ms
 * @param int ($exec_time_ms) - execution time in ms
 * @param bool ($is_bot) - shows the mode in which script was run
 */
function tick_timer($start, $exec_time, $is_bot) {
  static $once = true;
  if ((microtime(1) - $start) > $exec_time) {
    if ($once) {
      $once = false;
      throw new Exception('Execution timed out' . ($is_bot ? ' for search bot' : '') . ', exceeded ' . $exec_time * 1000 . 'ms');
    }
  }
}

/**
 * BV PHP SEO SDK Utilities.
 */
class BVUtility {
  public static $supportedContentTypes = array(
    'r' => 'REVIEWS',
    'q' => 'QUESTIONS',
    's' => 'STORIES',
    'u' => 'UNIVERSAL',
    'sp'=> 'SPOTLIGHTS'
  );
  private static $supportedSubjectTypes = array(
    'p' => 'PRODUCT',
    'c' => 'CATEGORY',
    'e' => 'ENTRY',
    'd' => 'DETAIL',
    's' => 'SELLER'
  );

  /**
   * Method used to limit execution time of the script.
   *
   * @access public
   * @param int ($exec_time_ms) - execution time in ms
   * @param bool ($is_bot) - shows the mode in which script was run
   */
  public static function execTimer($exec_time_ms, $is_bot = false, $start = 0) {
    $exec_time = $exec_time_ms / 1000;
    declare(ticks = 1); // or more if 1 takes too much time
    if (empty($start)) {
      $start = microtime(1);
    }
    register_tick_function('tick_timer', $start, $exec_time, $is_bot);
  }

  /**
   * Method used to stop execution time checker.
   *
   * @access public
   */
  public static function stopTimer() {
    unregister_tick_function('tick_timer');
  }

  /**
   * Parse the provided "bvstate" parameter value.
   *
   * @access public
   * @param string $bvstate - Value of the bvstate parameter.
   * @return array - parsed "bvstate" parameters.
   */
  public static function getBVStateHash($bvstate) {
    $bvStateHash = array();
    $bvp = mb_split("/", $bvstate);
    foreach ($bvp as $param) {
      $key = static::mb_trim(mb_substr($param, 0, mb_strpos($param, ':')));
      $bvStateHash[$key] = static::mb_trim(mb_substr($param, mb_strpos($param, ':') + 1));
    }
    return $bvStateHash;
  }

  /**
   * Checks content type or subject type is supported.
   * If type is not supported throw exception.
   *
   * @access public
   * @param string ($type) - content type or subject type which have to be checked.
   * @param string ($typeType) - default 'ct', mark of type 'ct' - content type, 'st' - subject type
   * @return boolean True if type is correct and no exception was thrown.
   */
  public static function checkType($type, $typeType = 'ct') {
    if ($typeType == 'st') {
      $typeName = 'subject type';
      $typeArray = static::$supportedSubjectTypes;
    } else {
      $typeName = 'content type';
      $typeArray = static::$supportedContentTypes;
    }
    if (!array_key_exists(mb_strtolower($type), $typeArray)) {
      foreach ($typeArray as $key => $value) {
        $supportList[] = $key . '=' . $value;
      }
      throw new Exception('Obtained not supported ' . $typeName
      . '. BV Class supports following ' . $typeName . ': '
      . implode(', ', $supportList));
    }

    return true;
  }

  /**
   * Generates an array of parameters from the bvstate parameter value.
   *
   * @access public
   * @param string $bvstate - "bvstate" parameter value.
   * @return array - array of parameters that are ready to use in script.
   */
  public static function getBVStateParams($bvstate) {
    $bvStateHash = self::getBVStateHash($bvstate);
    $params = array();

    // If the content type 'ct' parameter is not present, then ignore bvstate.
    if (empty($bvStateHash['ct'])) {
      return $params;
    }

    if (!empty($bvStateHash)) {
      if (!empty($bvStateHash['id'])) {
        $params['subject_id'] = $bvStateHash['id'];
      }
      if (!empty($bvStateHash['pg'])) {
        $params['page'] = $bvStateHash['pg'];
      }
      if (!empty($bvStateHash['ct'])) {
        $cType = $bvStateHash['ct'];
        self::checkType($cType, 'ct');
        $params['content_type'] = mb_strtolower(self::$supportedContentTypes[$cType]);
      }
      if (!empty($bvStateHash['st'])) {
        $sType = $bvStateHash['st'];
        self::checkType($sType, 'st');
        $params['subject_type'] = mb_strtolower(self::$supportedSubjectTypes[$sType]);
      }
      if (!empty($bvStateHash['reveal'])) {
        $params['bvreveal'] = $bvStateHash['reveal'];
      }
    }

    if (!empty($params)) {
      // This acts as a flag to tell us that a useful bvstate value was in fact
      // extracted from the URL.
      $params['base_url_bvstate'] = TRUE;
    }
    if (empty($params['page'])) {
      $params['page'] = '1';
    }

    return $params;
  }

  /**
   * Parse name=value parameters from the URL query string, fragment, and
   * _escaped_fragment_.
   *
   * @access public
   * @param string ($url) - The URL.
   * @return array - An array of parameters values indexed by parameter names.
   */
  public static function parseUrlParameters($url) {
    // Why are we doing things in this devious way? The answer is to be as
    // multibyte-supportive as possible. Most of the URL-parsing tools in the
    // toolbox appear to be only varying degrees of multibyte-supportive; good
    // for UTF-8 but not so great if you venture beyond that.

    // Break down the URL into a mix of things, some of which are name=value
    // pairs.
    $params = array();
    $chunks = mb_split('\?|&amp;|&|#!|#|_escaped_fragment_=|%26', $url);
    foreach ($chunks as $chunk) {
      // If this is name=value, then there will be two items.
      $values = mb_split('=', $chunk);
      if (sizeof($values) == 2) {
        // Since we're moving left to right in the URL, and we want query string
        // to win over fragment if there are the same parameters in both, then
        // only add if not already there.
        if (!isset($params[$values[0]])) {
          $params[$values[0]] = $values[1];
        }
      }
    }
    return $params;
  }

  /**
   * Remove a parameter from the provided URL.
   *
   * This will remove the named parameter wherever it occurs as name=value in
   * the URL via a simple regex replacement. This is crude but the most
   * straightforward way of going about this in PHP.
   *
   * If there is a query string delimeter following the name=value parameter
   * then that will also be removed.
   *
   * E.g. we're expecting to remove the bvstate from URLs such as:
   *
   * http://example.com/product/123?bvstate=pg:4/ct:r
   * http://example.com/product/123#!bvstate=pg:4/ct:r
   *
   * This will only be used for Bazaarvoice SEO parameters, so apologies in
   * advance to the one person in the universe for whom bvstate=xyz is a vital
   * part of the URL path.
   *
   * Note that the fragment isn't passed to the server, so we're not really
   * going to see that in practice. Attention is given to that here for the
   * sake of completeness.
   *
   * @access public
   * @param string ($url) - The URL.
   * @param string ($paramName) - Name of the parameter to be removed.
   * @return string - The updated URL.
   */
  public static function removeUrlParam($url, $paramName) {
    // The ereg POSIX regex functions are all greedy all the time, which makes
    // this harder than it has to be.
    //
    // Big assumption: our seo link values will never contain the % character.
    //
    // http://example.com/product/123?bvstate=pg:4/ct:r&amp;a=b
    $url = mb_ereg_replace($paramName . '=[^&#%]*&amp;', '', $url);
    // http://example.com/product/123?bvstate=pg:4/ct:r&a=b
    // http://example.com/product/123?#!bvstate=pg:4/ct:r&a=b
    // http://example.com/product/123?_escaped_fragment_=bvstate=pg:4/ct:r%26a=b
    $url = mb_ereg_replace($paramName . '=[^&#%]*(&|%26)', '', $url);
    // http://example.com/product/123?bvstate=pg:4/ct:r#!x/y/z
    $url = mb_ereg_replace($paramName . '=[^&#]*#', '#', $url);
    // This one last as it will break everything if we haven't already dealt
    // with all of the cases, since .* is always greedy in POSIX regex.
    // http://example.com/product/123?bvstate=pg:4/ct:r
    $url = mb_ereg_replace($paramName . '=[^&#%]*$', '', $url);
    return $url;
  }

  /**
   * A multibyte-safe trim.
   * (http://stackoverflow.com/questions/10066647/multibyte-trim-in-php/10067670#10067670)
   *
   * @access public
   * @param string ($str) - The string that will be trimmed.
   * @return string -  The trimmed string.
   */
  public static function mb_trim($str) {
    return mb_ereg_replace('(^\s+)|(\s+$)', '', $str);
  }

}
