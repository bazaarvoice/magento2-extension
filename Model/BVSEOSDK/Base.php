<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

/** @codingStandardsIgnoreFile */

namespace Bazaarvoice\Connector\Model\BVSEOSDK;

/**
 * Base Class containing most shared functionality. So when we add support for
 * questions and answers it should be minimal changes. Just need to create an
 * answers class which inherits from Base.
 *
 * Configuration array is required for creation class object.
 *
 */
class Base
{
    private $msg = '';

    /** 
     * @var array $config
     */
    private $config;
    private mixed $bv_config;
    private string $seo_url;
    private $start_time;
    private float $response_time;

    public function __construct($params = array())
    {

        $this->validateParams($params);

        $this->config = $params;

        // setup bv (internal) defaults
        $this->bv_config['seo-domain']['staging'] = 'seo-stg.bazaarvoice.com';
        $this->bv_config['seo-domain']['production'] = 'seo.bazaarvoice.com';
        $this->bv_config['seo-domain']['testing_staging'] = 'seo-qa-stg.bazaarvoice.com';
        $this->bv_config['seo-domain']['testing_production'] = 'seo-qa.bazaarvoice.com';

        // seller rating display is a special snowflake
        $this->bv_config['srd-domain'] = 'srd.bazaarvoice.com';
        $this->bv_config['srd-prefix-staging'] = 'stg';
        $this->bv_config['srd-prefix-production'] = 'prod';
        $this->bv_config['srd-prefix-testing_staging'] = 'qa-stg';
        $this->bv_config['srd-prefix-testing_production'] = 'qa';

        $this->config['latency_timeout'] = $this->_isBot()
            ? $this->config['execution_timeout_bot']
            : $this->config['execution_timeout'];

        // set up combined user agent to be passed to cloud storage (if needed)
        $this->config['user_agent'] = "bv_php_sdk/3.2.1;".$_SERVER['HTTP_USER_AGENT'];
    }

    protected function validateParams($params)
    {
        if (!is_array($params)) {
            throw new Exception('BV Base Class missing config array.');
        }
    }

    /**
     * A check on the bvstate parameter content type value.
     */
    protected function _checkBVStateContentType()
    {
        if (empty($this->config['page_params']['content_type'])) {
            return true;
        }

        if (
            !empty($this->config['page_params']['content_type'])
            && $this->config['page_params']['content_type'] == $this->config['content_type']
        ) {
            return true;
        }

        return false;
    }

    /**
     * Function for collecting messages.
     *
     * @param $msg
     */
    protected function _setBuildMessage($msg)
    {
        $msg = rtrim($msg, ";");
        $this->msg .= ' '.$msg.';';
    }

    /**
     * Is this SDK enabled?
     *
     * Return true if either seo_sdk_enabled is set truthy or bvreveal flags are
     * set.
     */
    private function _isSdkEnabled()
    {
        return $this->config['seo_sdk_enabled'] || $this->_getBVReveal();
    }

    /**
     * Check if charset is correct, if not set to default
     *
     * @param $seo_content
     */
    private function _checkCharset($seo_content)
    {
        if (isset($this->config['charset'])) {
            $supportedCharsets = mb_list_encodings();
            if (!in_array($this->config['charset'], $supportedCharsets)) {
                $this->config['charset'] = DEFAULT_CHARSET;
                $this->_setBuildMessage("Charset is not configured properly. "
                    ."BV-SEO-SDK will load default charset and continue.");
            }
        } else {
            $this->config['charset'] = DEFAULT_CHARSET;
        }
    }

    /**
     * Return encoded content with set charset
     * @param $seo_content
     * @return string
     */
    private function _charsetEncode($seo_content)
    {
        if (isset($this->config['charset'])) {
            $enc = mb_detect_encoding($seo_content);
            $seo_content = mb_convert_encoding($seo_content, $this->config['charset'], $enc);
        }

        return $seo_content;
    }

    /**
     * Return full SEO content.
     */
    private function _getFullSeoContents()
    {
        $seo_content = '';

        // get the page number of SEO content to load
        $page_number = $this->_getPageNumber();

        // build the URL to access the SEO content for
        // this product / page combination
        $this->seo_url = $this->_buildSeoUrl($page_number);

        // make call to get SEO payload from cloud unless seo_sdk_enabled is false
        // make call if bvreveal param in query string is set to 'debug'
        if ($this->_isSdkEnabled()) {
            $seo_content = $this->_fetchSeoContent($this->seo_url);

            $this->_checkCharset($seo_content);
            $seo_content = $this->_charsetEncode($seo_content);

            // replace tokens for pagination URLs with page_url
            $seo_content = $this->_replaceTokens($seo_content);
        } // show footer even if seo_sdk_enabled flag is false
        else {
            $this->_setBuildMessage(
                'SEO SDK is disabled. Enable by setting seo.sdk.enabled to true.'
            );
        }

        $payload = $seo_content;

        return $payload;
    }

    /**
     * Remove predefined section from a string.
     * @param $str
     * @param $search_str_begin
     * @param $search_str_end
     * @return string
     */
    private function _replaceSection($str, $search_str_begin, $search_str_end)
    {
        $result = $str;
        $start_index = mb_strrpos($str, $search_str_begin);

        if ($start_index !== false) {
            $end_index = mb_strrpos($str, $search_str_end);

            if ($end_index !== false) {
                $end_index += mb_strlen($search_str_end);
                $str_begin = mb_substr($str, 0, $start_index);
                $str_end = mb_substr($str, $end_index);

                $result = $str_begin.$str_end;
            }
        }

        return $result;
    }

    /**
     * Get only aggregate rating from SEO content.
     */
    protected function _renderAggregateRating()
    {
        $payload = $this->_renderSEO('getAggregateRating');

        // remove reviews section from full_contents
        $payload = $this->_replaceSection($payload, '<!--begin-reviews-->', '<!--end-reviews-->');

        // remove pagination section from full contents
        $payload = $this->_replaceSection($payload, '<!--begin-pagination-->', '<!--end-pagination-->');

        return $payload;
    }

    /**
     * Get only reviews from SEO content.
     */
    protected function _renderReviews()
    {
        $payload = $this->_renderSEO('getReviews');

        // remove aggregate rating section from full_contents
        $payload = $this->_replaceSection($payload, '<!--begin-aggregate-rating-->', '<!--end-aggregate-rating-->');

        // Remove schema.org product text from reviews if it exists
        $schema_org_text = "itemscope itemtype=\"http://schema.org/Product\"";
        $payload = mb_ereg_replace($schema_org_text, '', $payload);

        return $payload;
    }

    /**
     * Render SEO
     *
     * Method used to do all the work to fetch, parse, and then return
     * the SEO payload. This is set as protected so classes inheriting
     * from the base class can invoke it or replace it if needed.
     *
     * @access protected
     *
     * @param $access_method
     *
     * @return string
     */
    protected function _renderSEO($access_method)
    {
        $payload = '';
        $this->start_time = microtime(1);

        $isBot = $this->_isBot();

        if (!$isBot && $this->config['latency_timeout'] == 0) {
            $this->_setBuildMessage("EXECUTION_TIMEOUT is set to 0 ms; JavaScript-only Display.");
        } else {

            if ($isBot && $this->config['latency_timeout'] < 100) {
                $this->config['latency_timeout'] = 100;
                $this->_setBuildMessage("EXECUTION_TIMEOUT_BOT is less than the minimum value allowed. Minimum value of 100ms used.");
            }

            try {
                $payload = $this->_getFullSeoContents($access_method);
            } catch (Exception $e) {
                $this->_setBuildMessage($e->getMessage());
            }
        }

        $payload .= $this->_buildComment($access_method);

        return $payload;
    }

    // -------------------------------------------------------------------
    //  Private methods. Internal workings of SDK.
    //--------------------------------------------------------------------

    /**
     * isBot
     *
     * Helper method to determine if current request is a bot or not. Will
     * use the configured regex string which can be overridden with params.
     *
     * @access private
     * @return bool
     */
    private function _isBot()
    {
        $bvreveal = $this->_getBVReveal();
        if ($bvreveal) {
            return true;
        }

        // search the user agent string for an indication if this is a search bot or not
        return mb_eregi('('.$this->config['crawler_agent_pattern'].')', $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * getBVReveal
     *
     * Return true if bvreveal flags are set, either via reveal:debug in the
     * bvstate query parameter or in the old bvreveal query parameter, or is
     * passed in via the configuration of the main class.
     */
    private function _getBVReveal()
    {
        // Passed in as configuration override?
        if (
            !empty($this->config['bvreveal'])
            && $this->config['bvreveal'] == 'debug'
        ) {
            return true;
        } // Set via bvstate query parameter?
        else {
            if (
                !empty($this->config['page_params']['bvreveal'])
                && $this->config['page_params']['bvreveal'] == 'debug'
            ) {
                return true;
            } // Set via bvreveal query parameter?
            else {
                if (
                    !empty($this->config['bv_page_data']['bvreveal'])
                    && $this->config['bv_page_data']['bvreveal'] == 'debug'
                ) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * getPageNumber
     *
     * Helper method to pull from the URL the page of SEO we need to view.
     *
     * @access private
     * @return int
     */
    private function _getPageNumber()
    {
        $page_number = 1;

        // Override from config.
        if (isset($this->config['page']) && $this->config['page'] != $page_number) {
            $page_number = (int)$this->config['page'];
        } // Check the bvstate parameter if one was found and successfully parsed.
        else {
            if (isset($this->config['page_params']['base_url_bvstate'])) {
                // We only apply the bvstate page number parameter if the content type
                // specified matches the content type being generated here. E.g. if
                // someone calls up a page with bvstate=ct:r/pg:2 and loads stories rather
                // than reviews, show page 1 for stories. Only show page 2 if they are in
                // fact displaying review content.
                if ($this->config['content_type'] == $this->config['page_params']['content_type']) {
                    $page_number = $this->config['page_params']['page'];
                }
            }
            // other implementations use the bvrrp, bvqap, or bvsyp parameter
            // ?bvrrp=1234-en_us/reviews/product/2/ASF234.htm
            //
            // Note that unlike bvstate, we don't actually check for the content type
            // to match the parameter type for the legacy page parameters bvrrp, bvqap,
            // and bvsyp. This is consistent with the behavior of the other SDKs, even
            // if it doesn't really make much sense.
            //
            // Note that there is a bug in the SEO-CPS content generation where it uses
            // the bvrrp parameter in place of bvqap, so this may all be sort of
            // deliberate, if not sensible.
            else {
                if (isset($this->config['bv_page_data']['bvrrp'])) {
                    $bvparam = $this->config['bv_page_data']['bvrrp'];
                } else {
                    if (isset($this->config['bv_page_data']['bvqap'])) {
                        $bvparam = $this->config['bv_page_data']['bvqap'];
                    } else {
                        if (isset($this->config['bv_page_data']['bvsyp'])) {
                            $bvparam = $this->config['bv_page_data']['bvsyp'];
                        }
                    }
                }
            }
        }

        if (!empty($bvparam)) {
            $match = array();
            mb_ereg('\/(\d+)\/', $bvparam, $match);
            $page_number = max(1, (int)$match[1]);
        }

        return $page_number;
    }

    /**
     * buildSeoUrl
     *
     * Helper method to that builds the URL to the SEO payload
     *
     * @access private
     *
     * @param int (page number)
     *
     * @return string
     */
    private function _buildSeoUrl($page_number)
    {
        $primary_selector = 'seo-domain';

        // calculate, which environment should we be using
        if ($this->config['testing']) {
            if ($this->config['staging']) {
                $env_selector = 'testing_staging';
            } else {
                $env_selector = 'testing_production';
            }
        } else {
            if ($this->config['staging']) {
                $env_selector = 'staging';
            } else {
                $env_selector = 'production';
            }
        }

        $url_scheme = $this->config['ssl_enabled'] ? 'https://' : 'http://';

        if ($this->config['content_type'] == 'reviews'
            && $this->config['subject_type'] == 'seller') {
            // when content type is reviews and subject type is seller,
            // we're dealing with seller rating, so use different primary selector
            $primary_selector = 'srd-domain';
            // for seller rating we use different selector for prefix
            $hostname = $this->bv_config[$primary_selector].'/'.$this->bv_config['srd-prefix-'.$env_selector];
        } else {
            $hostname = $this->bv_config[$primary_selector][$env_selector];
        };

        // dictates order of URL
        $url_parts = array(
            $url_scheme.$hostname,
            $this->config['cloud_key'],
            $this->config['bv_root_folder'],
            $this->config['content_type'],
            $this->config['subject_type'],
            $page_number,
        );

        if (isset($this->config['content_sub_type']) && !empty($this->config['content_sub_type'])) {
            $url_parts[] = $this->config['content_sub_type'];
        }

        if (!empty($this->config['page_params']['subject_id']) && $this->_checkBVStateContentType()) {
            $url_parts[] = urlencode($this->config['page_params']['subject_id']).'.htm';
        } else {
            $url_parts[] = urlencode($this->config['subject_id']).'.htm';
        }

        // if our SEO content source is a file path
        // we need to remove the first two sections
        // and prepend the passed in file path
        if (!empty($this->config['load_seo_files_locally']) && !empty($this->config['local_seo_file_root'])) {
            unset($url_parts[0]);
            unset($url_parts[1]);

            return $this->config['local_seo_file_root'].implode("/", $url_parts);
        }

        // implode will convert array to a string with / in between each value in array
        return implode("/", $url_parts);
    }

    /**
     * Return a SEO content from local or distant sourse.
     * @param $resource
     * @return string
     */
    private function _fetchSeoContent($resource)
    {
        if ($this->config['load_seo_files_locally']) {
            return $this->_fetchFileContent($resource);
        } else {
            return $this->_fetchCloudContent($resource);
        }
    }

    /**
     * fetchFileContent
     *
     * Helper method that will take in a file path and return it's payload while
     * handling the possible errors or exceptions that can happen.
     *
     * @access private
     *
     * @param string (valid file path)
     *
     * @return string (content of file)
     */
    private function _fetchFileContent($path)
    {
        $file = file_get_contents($path);
        if ($file === false) {
            $this->_setBuildMessage('Trying to get content from "'.$path
                .'". The resource file is currently unavailable');
        } else {
            $this->_setBuildMessage('Local file content was uploaded');
        }

        return $file;
    }

    public function curlExecute($ch)
    {
        return curl_exec($ch);
    }

    public function curlInfo($ch)
    {
        return curl_getinfo($ch);
    }

    public function curlErrorNo($ch)
    {
        return curl_errno($ch);
    }

    public function curlError($ch)
    {
        return curl_error($ch);
    }

    /**
     * fetchCloudContent
     *
     * Helper method that will take in a URL and return it's payload while
     * handling the possible errors or exceptions that can happen.
     *
     * @access private
     *
     * @param string (valid url)
     *
     * @return string
     */
    private function _fetchCloudContent($url)
    {

        // is cURL installed yet?
        // if ( ! function_exists('curl_init')){
        //  return '<!-- curl library is not installed -->';
        // }
        // create a new cURL resource handle
        $ch = curl_init();

        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, $url);
        // Set a referer as coming from the current page url
        curl_setopt($ch, CURLOPT_REFERER, $this->config['page_url']);
        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->config['latency_timeout']);
        // Enable decoding of the response
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        // Enable following of redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // set user agent if needed
        if ($this->config['user_agent'] != '') {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->config['user_agent']);
        }

        if ($this->config['proxy_host'] != '') {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['proxy_host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['proxy_port']);
        }

        // make the request to the given URL and then store the response,
        // request info, and error number
        // so we can use them later
        $request = array(
            'response'      => $this->curlExecute($ch),
            'info'          => $this->curlInfo($ch),
            'error_number'  => $this->curlErrorNo($ch),
            'error_message' => $this->curlError($ch),
        );

        // Close the cURL resource, and free system resources
        curl_close($ch);

        // see if we got any errors with the connection
        if ($request['error_number'] != 0) {
            $this->_setBuildMessage('Error - '.$request['error_message']);
        }

        // see if we got a status code of something other than 200
        if ($request['info']['http_code'] != 200) {
            $this->_setBuildMessage('HTTP status code of '
                .$request['info']['http_code'].' was returned');

            return '';
        }

        // if we are here we got a response so let's return it
        $this->response_time = round($request['info']['total_time'] * 1000);

        return $request['response'];
    }

    /**
     * replaceTokens
     *
     * After we have an SEO payload we need to replace the {INSERT_PAGE_URI}
     * tokens with the current page url so pagination works.
     *
     * @access private
     *
     * @param string (valid url)
     *
     * @return string
     */
    private function _replaceTokens($content)
    {
        $page_url_query_prefix = '';

        // Attach a suitable ending to the base URL if it doesn't already end with
        // either ? or &. This is complicated by the _escaped_fragment_ case.
        //
        // We're assuming that the base URL can't have a fragment or be a hashbang
        // URL - that just won't work in conjunction with the assumption that we
        // always postfix the SEO query parameters to the end of the URL.
        //
        // If the base url ends with an empty _escaped_fragment_ property.
        if (mb_ereg('_escaped_fragment_=$', $this->config['base_url'])) {
            // Append nothing for this annoying edge case.
        }
        // Otherwise if there is something in the _escaped_fragment_ then append
        // the escaped ampersand.
        else {
            if (mb_ereg('_escaped_fragment_=.+$', $this->config['base_url'])) {
                $page_url_query_prefix = '%26';
            } // Otherwise we're back to thinking about query strings.
            else {
                if (!mb_ereg('[\?&]$', $this->config['base_url'])) {
                    if (mb_ereg('\?', $this->config['base_url'])) {
                        $page_url_query_prefix = '&';
                    } else {
                        $page_url_query_prefix = '?';
                    }
                }
            }
        }

        $content = mb_ereg_replace(
            '{INSERT_PAGE_URI}',
            // Make sure someone doesn't sneak in "><script>...<script> in the URL
            // contents.
            htmlspecialchars(
                $this->config['base_url'].$page_url_query_prefix,
                ENT_QUOTES | ENT_HTML5,
                $this->config['charset'],
                // Don't double-encode.
                false
            ),
            $content
        );

        return $content;
    }

    /**
     * Return hidden metadata for adding to SEO content.
     * @param $access_method
     * @return string
     */
    private function _buildComment($access_method)
    {
        $bvf = new BVFooter($this, $access_method, $this->msg);
        $footer = $bvf->buildSDKFooter();
        $reveal = $this->_getBVReveal();
        if ($reveal) {
            $footer .= $bvf->buildSDKDebugFooter();
        }

        return $footer;
    }

    public function getBVMessages()
    {
        return $this->msg;
    }

    public function getContent()
    {
        $this->_setBuildMessage('Content Type "'.$this->config['content_type'].'" is not supported by getContent().');
        $pay_load = $this->_buildComment('', 'getContent');

        return $pay_load;
    }

    public function getAggregateRating()
    {
        $this->_setBuildMessage('Content Type "'.$this->config['content_type']
            .'" is not supported by getAggregateRating().');
        $pay_load = $this->_buildComment('', 'getAggregateRating');

        return $pay_load;
    }

    public function getReviews()
    {
        $this->_setBuildMessage('Content Type "'.$this->config['content_type'].'" is not supported by getReviews().');
        $pay_load = $this->_buildComment('', 'getReviews');

        return $pay_load;
    }

}
