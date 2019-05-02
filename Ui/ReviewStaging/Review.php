<?php

namespace Bazaarvoice\Connector\Ui\ReviewStaging;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;

class Review extends AbstractModifier {

	/**
	 * @param array $meta
	 *
	 * @return array
	 */
	public function modifyMeta( array $meta ) {
		return $meta;
	}

    /**
     * @param array $data
     *
     * @return array
     */
    public function modifyData( array $data ) {
        return $data;
    }


}