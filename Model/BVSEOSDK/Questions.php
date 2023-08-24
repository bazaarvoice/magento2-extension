<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

/** @codingStandardsIgnoreFile */

namespace Bazaarvoice\Connector\Model\BVSEOSDK;

/**
 * Questions Class
 *
 * Base class extention for work with "questions" content type.
 */
class Questions extends Base
{
    /**
     * @var array<string, string>|array<string, mixed>
     */
    public $confi

    function __construct($params = array())
    {
        // call Base Class constructor
        parent::__construct($params);

        // since we are in the questions class
        // we need to set the content_type config
        // to questions so we get questions in our
        // SEO request
        $this->config['content_type'] = 'questions';
    }

    public function getContent()
    {
        $payload = $this->_renderSEO('getContent');
        if (!empty($this->config['page_params']['subject_id']) && $this->_checkBVStateContentType()) {
            $subject_id = $this->config['page_params']['subject_id'];
        } else {
            $subject_id = $this->config['subject_id'];
        }
        // if they want to power display integration as well
        // then we need to include the JS integration code
        if ($this->config['include_display_integration_code']) {

            $payload .= '
         <script>
           $BV.ui("qa", "show_questions", {
             productId: "'.$subject_id.'"
           });
         </script>
       ';
        }

        return $payload;
    }

}
