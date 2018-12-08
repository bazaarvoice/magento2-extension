<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Model\Index;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Store\Model\Store;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection\Factory;

class Product extends Generic {
    /** @var  XMLWriter $_writer */
    protected $_writer;
    protected $_index;
    protected $_indexFactory;
    protected $_iterator;

    /**
     * Product constructor.
     *
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param StoreManagerInterface $storeManager
     * @param Iterator $iterator
     * @param Index $index
     * @param Factory $factory
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        StoreManagerInterface $storeManager,
        Iterator $iterator,
        Index $index,
        Factory $factory
    ) {
        $this->_iterator;
        $this->_index        = $index;
        $this->_indexFactory = $factory;
        parent::__construct( $logger, $helper, $storeManager );
    }

    /**
     * @param XMLWriter $writer
     * @param $store
     */
    public function processProducts( XMLWriter $writer, Store $store ) {
        $this->_writer = $writer;
        $this->_writer->startElement( 'Products' );
        $indexCollection = $this->getIndexCollection();

        if ( $store->getId() ) {
            $indexCollection->setStore( $store );
        }

        foreach ( $indexCollection as $product ) {
            $this->writeProduct( $product );
        }

        $this->_logger->info( $indexCollection->count() . ' products found to export.' );

        $this->_writer->endElement();
        /** Products */
    }

    /**
     * @param Index $product
     */
    public function writeProduct( $product ) {
        $this->_logger->debug( 'Write product ' . $product->getData( 'product_id' ) );

        /** Load parent value if product value is blank */
        if ( $product->getData( 'family' ) !== null
             && $this->_helper->getConfig( 'feeds/bvfamilies_inherit' )
        ) {
            $this->_logger->debug( 'inherit family values' );
            $children = $this->_indexFactory->create();
            $children
                ->addFieldToFilter( 'family', $product->getData( 'family' ) )
                ->addFieldToFilter( 'store_id', $product->getData( 'store_id' ) );
            $childrenValues = [];
            foreach ( $children as $child ) {
                $this->_logger->debug( $child->getExternalId() );
                foreach ( $product->customAttributes as $attribute ) {
                    $this->_logger->debug( $attribute );
                    $attribute = strtolower( $attribute ) . 's';
                    if ( $child->getData( $attribute ) ) {
                        $value = $child->getData( $attribute );
                        if ( is_string( $value ) && strpos( $value, ',' ) ) {
                            $values                       = explode( ',', $value );
                            if(!empty($childrenValues[ $attribute ]))
                                $childrenValues[ $attribute ] = $values;
                            else
                                $childrenValues[ $attribute ] = array_merge( $childrenValues[ $attribute ], $values );
                        } else {
                            $childrenValues[ $attribute ][] = $value;
                        }
                    }
                }
            }
            $this->_logger->debug( $childrenValues );
            foreach ( $childrenValues as $attribute => $values ) {
                if ( ! is_array( $values ) || empty( $values ) ) {
                    continue;
                }
                $product->setData( $attribute, $values );
            }
        }

        foreach ( $product->getData() as $key => $value ) {
            if ( is_string( $value )
                 && ( substr( $value, 0, 1 ) == '[' || substr( $value, 0, 1 ) == '{' ) ) {
                $product->setData( $key, $this->_helper->jsonDecode( $value ) );
            }
        }

        $this->_writer->startElement( 'Product' );

        $this->_writer->writeElement( 'ExternalId', $product->getData( 'external_id' ) );
        $this->_writer->writeElement( 'Name', $product->getData( 'name' ), true );
        $localeName = $product->getData( 'locale_name' );
        if ( is_array( $localeName ) && count( $localeName ) ) {
            $this->_writer->startElement( 'Names' );
            foreach ( $localeName as $locale => $name ) {
                $this->_writer->startElement( 'Name' );
                $this->_writer->writeAttribute( 'locale', $locale );
                $this->_writer->writeRaw( $name, true );
                $this->_writer->endElement();
                /** Name */
            }
            $this->_writer->endElement();
            /** Names */
        }

        $this->_writer->writeElement( 'Description', $product->getData( 'description' ), true );
        $localeDescription = $product->getData( 'locale_description' );
        if ( is_array( $localeDescription ) && count( $localeDescription ) ) {
            $this->_writer->startElement( 'Descriptions' );
            foreach ( $localeDescription as $locale => $description ) {
                $this->_writer->startElement( 'Description' );
                $this->_writer->writeAttribute( 'locale', $locale );
                $this->_writer->writeRaw( $description, true );
                $this->_writer->endElement();
                /** Description */
            }
            $this->_writer->endElement();
            /** Descriptions */
        }

        $this->_writer->writeElement( 'CategoryExternalId', $product->getData( 'category_external_id' ) );

        $this->_writer->writeElement( 'ProductPageUrl', $product->getData( 'product_page_url' ), true );
        $localeUrls = $product->getData( 'locale_product_page_url' );
        if ( is_array( $localeUrls ) && count( $localeUrls ) ) {
            $this->_writer->startElement( 'ProductPageUrls' );
            foreach ( $localeUrls as $locale => $url ) {
                $this->_writer->startElement( 'ProductPageUrl' );
                $this->_writer->writeAttribute( 'locale', $locale );
                $this->_writer->writeRaw( $url, true );
                $this->_writer->endElement();
                /** ProductPageUrl */
            }
            $this->_writer->endElement();
            /** ProductPageUrls */
        }

        $this->_writer->writeElement( 'ImageUrl', $product->getData( 'image_url' ), true );
        $localeImage = $product->getData( 'locale_image_url' );
        if ( is_array( $localeImage ) && count( $localeImage ) ) {
            $this->_writer->startElement( 'ImageUrls' );
            foreach ( $localeImage as $locale => $image ) {
                $this->_writer->startElement( 'ImageUrl' );
                $this->_writer->writeAttribute( 'locale', $locale );
                $this->_writer->writeRaw( $image, true );
                $this->_writer->endElement();
                /** ImageUrl */
            }
            $this->_writer->endElement();
            /** ImageUrls */
        }

        if ( $product->getData( 'brand_external_id' ) ) {
            $this->_writer->writeElement( 'BrandExternalId', $product->getData( 'brand_external_id' ) );
        }

        foreach ( $product->customAttributes as $label ) {
            $code   = strtolower( $label ) . 's';
            $values = $product->getData( $code );
            if ( ! empty( $values ) ) {
                $this->_writer->startElement( $label . 's' );
                if ( is_string( $values ) && strpos( $values, ',' ) ) {
                    $values = explode( ',', $values );
                }
                if ( is_array( $values ) ) {
                    foreach ( $values as $value ) {
                        $this->_writer->writeElement( $label, $value, true );
                    }
                } else {
                    $this->_writer->writeElement( $label, $values, true );
                }
                $this->_writer->endElement();
            }
        }

        if ( $this->_helper->getConfig( 'general/families' ) ) {
            if ( $product->getData( 'family' ) && count( $product->getData( 'family' ) ) ) {
                $this->_writer->startElement( 'Attributes' );

                foreach ( $product->getData( 'family' ) as $familyId ) {
                    if ( $familyId ) {
                        $this->_writer->startElement( 'Attribute' );
                        $this->_writer->writeAttribute( 'id', 'BV_FE_FAMILY' );
                        $this->_writer->writeElement( 'Value', $familyId );
                        $this->_writer->endElement();
                        /** Attribute */

                        if($product->getData( 'product_type' ) != 'simple' ||
                            $this->_helper->getConfig( 'feeds/bvfamilies_expand' )
                        ) {
                            $this->_writer->startElement( 'Attribute' );
                            $this->_writer->writeAttribute( 'id', 'BV_FE_EXPAND' );
                            $this->_writer->writeElement( 'Value', 'BV_FE_FAMILY:' . $familyId );
                            $this->_writer->endElement();
                            /** Attribute */
                        }
                    }
                }
                $this->_writer->endElement();
                /** Attributes */
            }
        }

        $this->_writer->endElement();
        /** Product */
    }

    /**
     * @return Collection
     */
    protected function getIndexCollection() {
        $collection = $this->_indexFactory->create();
        $collection->addFieldToFilter( 'status', Status::STATUS_ENABLED );

        return $collection;
    }


}
