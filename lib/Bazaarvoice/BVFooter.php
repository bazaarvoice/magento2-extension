<?php

/**
 * BV PHP SEO SDK Footer
 */
class BVFooter {
  const VERSION = '3.2.0';

  private $base;
  private $url;
  private $access_method;
  private $msg;

  /**
   * BVFooter Class Constructor
   *
   * @access public
   * @param array ($base) - base class parameters
   * @param string ($url) - SEO url
   * @param string ($access_method) - access method
   * @param string ($msg) - build message
   * @return object
   */
  public function __construct($base, $access_method, $msg) {
    $this->base = $base;
    $this->access_method = $access_method;
    $this->msg = $msg;
  }

  /**
   * buildSDKFooter
   *
   * Returns hidden SDK footer.
   *
   * @access public
   * @return string Html formatted footer.
   */
  public function buildSDKFooter() {
    $method_type = !empty($this->base->config['internal_file_path']) ? 'LOCAL' : 'CLOUD';
    $access_method = $this->access_method;
    $time_end = microtime(true);

    if (!empty($this->base->start_time)) {
      $exec_time = round(($time_end - $this->base->start_time) * 1000, 2);
    } else {
      $exec_time = 0;
    }
    $content_type = mb_strtoupper($this->base->config['content_type']);
    $subject_type = mb_strtoupper($this->base->config['subject_type']);

    $footer = "\n" . '<ul id="BVSEOSDK_meta" style="display:none !important;">';
    $footer .= "\n" . '   <li data-bvseo="sdk">bvseo_sdk, p_sdk, ' . self::VERSION . '</li>';
    $footer .= "\n" . '   <li data-bvseo="sp_mt">' . $method_type . ', ' . $access_method . ', ' . $exec_time . 'ms</li>';
    $footer .= "\n" . '   <li data-bvseo="ct_st">' . $content_type . ', ' . $subject_type . '</li>';
    if (!empty($this->msg)) {
      $footer .= "\n" . '   <li data-bvseo="ms">bvseo-msg: ' . $this->msg . '</li>';
    }
    $footer .= "\n" . '</ul>';

    return $footer;
  }

  /**
   * buildSDKDebugFooter
   *
   * Returns hidden SDK debug footer.
   *
   * @access public
   * @return string Html formatted debug footer.
   */
  public function buildSDKDebugFooter() {
    $staging = !empty($this->base->config['staging']) ? 'TRUE' : 'FALSE';
    $testing = !empty($this->base->config['testing']) ? 'TRUE' : 'FALSE';
    $sdk_enabled = !empty($this->base->config['seo_sdk_enabled']) ? 'TRUE' : 'FALSE';
    $ssl_enabled = !empty($this->base->config['ssl_enabled']) ? 'TRUE' : 'FALSE';
    $proxy_host = !empty($this->base->config['proxy_host']) ? $this->base->config['proxy_host'] : 'none';
    $proxy_port = !empty($this->base->config['proxy_port']) ? $this->base->config['proxy_port'] : '0';
    $local_seo_file_root = (!empty($this->base->config['load_seo_files_locally'])) ? $this->base->config['local_seo_file_root'] : 'FALSE';
    $content_type = mb_strtoupper($this->base->config['content_type']);
    $subject_type = mb_strtoupper($this->base->config['subject_type']);
    if (!empty($this->base->config['page_params']['subject_id'])
        && !empty($this->base->config['page_params']['content_type'])
        && $this->base->config['page_params']['content_type'] == $this->base->config['content_type']) {
      $subject_id = $this->base->config['page_params']['subject_id'];
    } else {
      $subject_id = $this->base->config['subject_id'];
    }

    $footer = "\n" . '<ul id="BVSEOSDK_DEBUG" style="display:none;">';

    $footer .= "\n" . '   <li data-bvseo="staging">' . $staging . '</li>';
    $footer .= "\n" . '   <li data-bvseo="testing">' . $testing . '</li>';
    $footer .= "\n" . '   <li data-bvseo="seo.sdk.enabled">' . $sdk_enabled . '</li>';
    if (!isset($this->base->config['subject_type']) || $this->base->config['subject_type'] != 'seller') {
      $footer .= "\n" . '   <li data-bvseo="stagingS3Hostname">' . $this->base->bv_config['seo-domain']['staging'] . '</li>';
      $footer .= "\n" . '   <li data-bvseo="productionS3Hostname">' . $this->base->bv_config['seo-domain']['production'] . '</li>';
      $footer .= "\n" . '   <li data-bvseo="testingStagingS3Hostname">' . $this->base->bv_config['seo-domain']['testing_staging'] . '</li>';
      $footer .= "\n" . '   <li data-bvseo="testingProductionS3Hostname">' . $this->base->bv_config['seo-domain']['testing_production'] . '</li>';
    }
    $footer .= "\n" . '   <li data-bvseo="proxyHost">' . $proxy_host . '</li>';
    $footer .= "\n" . '   <li data-bvseo="proxyPort">' . $proxy_port . '</li>';
    $footer .= "\n" . '   <li data-bvseo="seo.sdk.execution.timeout.bot">' . $this->base->config['execution_timeout_bot'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="seo.sdk.execution.timeout">' . $this->base->config['execution_timeout'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="localSEOFileRoot">' . $local_seo_file_root . '</li>';
    $footer .= "\n" . '   <li data-bvseo="cloudKey">' . $this->base->config['cloud_key'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="bv.root.folder">' . $this->base->config['bv_root_folder'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="seo.sdk.charset">' . $this->base->config['charset'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="seo.sdk.ssl.enabled">' . $ssl_enabled . '</li>';
    $footer .= "\n" . '   <li data-bvseo="crawlerAgentPattern">' . $this->base->config['crawler_agent_pattern'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="subjectID">' . urlencode($subject_id) . '</li>';


    $footer .= "\n" . '   <li data-bvseo="en">' . $sdk_enabled . '</li>';
    $footer .= "\n" . '   <li data-bvseo="pn">bvseo-' . $this->base->config['page'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="userAgent">' . $_SERVER['HTTP_USER_AGENT'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="pageURI">' . $this->base->config['page_url'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="baseURI">' . $this->base->config['base_url'] . '</li>';
    $footer .= "\n" . '   <li data-bvseo="contentType">' . $content_type . '</li>';
    $footer .= "\n" . '   <li data-bvseo="subjectType">' . $subject_type . '</li>';
    if (!empty($this->base->seo_url)) {
      $footer .= "\n" . '   <li data-bvseo="contentURL">' . $this->base->seo_url . '</li>';
    }

    $footer .= "\n" . '</ul>';

    return $footer;
  }

}
