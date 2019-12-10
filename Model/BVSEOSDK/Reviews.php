<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

/** @codingStandardsIgnoreFile */

namespace Bazaarvoice\Connector\Model\BVSEOSDK;

/**
 * Reviews Class
 *
 * Base class extention for work with "reviews" content type.
 */
class Reviews extends Base
{

    function __construct($params = array())
    {
        // call Base Class constructor
        parent::__construct($params);

        // since we are in the reviews class
        // we need to set the content_type config
        // to reviews so we get reviews in our
        // SEO request
        $this->config['content_type'] = 'reviews';

        // for reviews subject type will always
        // need to be product
        $this->config['subject_type'] = 'product';
    }

    public function getAggregateRating()
    {
        return $this->_renderAggregateRating();
    }

    public function getReviews()
    {
        return $this->_renderReviews();
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
           $BV.ui("rr", "show_reviews", {
             productId: "'.$subject_id.'"
           });
         </script>
       ';
        }

        return $payload;
    }

}
