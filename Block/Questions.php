<?php

namespace Bazaarvoice\Connector\Block;

use \Bazaarvoice\Connector\Helper\Seosdk;

class Questions extends Reviews
{

    /**
     * Get complete BV SEO Content
     * @return string
     * @throws \Exception
     */
    public function getSEOContent()
    {
        $this->bvLogger->debug( __CLASS__ . ' getSEOContent');
        if ($this->getIsEnabled()) {
            $params = $this->_getParams();
            $bv = new Seosdk($params);
            $seoContent = $bv->questions->getContent();
            if($this->getConfig('general/environment') == 'staging')
                $seoContent .= '<!-- BV Reviews SEO Parameters: ' . json_encode($params) . '-->';
            return $seoContent;
        }
        return '';
    }

}