<?php

namespace Bazaarvoice\Connector\Block;


class Header extends Reviews {

    /**
     * Bypass product checking from Product block
     *
     * @return string
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _toHtml() {
        if (!$this->getTemplate()) {
            return '';
        }
        return $this->fetchView($this->getTemplateFile());
    }

}