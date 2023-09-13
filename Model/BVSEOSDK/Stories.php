<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

/** @codingStandardsIgnoreFile */

namespace Bazaarvoice\Connector\Model\BVSEOSDK;

/**
 * Stories Class
 *
 * Base class extention for work with "stories" content type.
 */
class Stories extends Base
{
    /**
     * @var array<string, string>|array<string, mixed>
     */
    public $config;

    function __construct($params = array())
    {
        // call Base Class constructor
        parent::__construct($params);

        // since we are in the stories class
        // we need to set the content_type config
        // to stories so we get stories in our
        // SEO request
        $this->config['content_type'] = 'stories';

        // for stories subject type will always
        // need to be product
        $this->config['subject_type'] = 'product';

        // for stories we have to set content sub type
        // the sub type is configured as either STORIES_LIST or STORIES_GRID
        // the folder names are "stories" and "storiesgrid" respectively.
        if (isset($this->config['content_sub_type'])
            && $this->config['content_sub_type'] == "stories_grid") {
            $this->config['content_sub_type'] = "storiesgrid";
        } else {
            $this->config['content_sub_type'] = "stories";
        }
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
           $BV.ui("su", "show_stories", {
             productId: "'.$subject_id.'"
           });
         </script>
       ';
        }

        return $payload;
    }

}
