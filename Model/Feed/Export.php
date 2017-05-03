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

namespace Bazaarvoice\Connector\Model\Feed;

use Magento\Review\Model\Review;

class Export extends Feed
{
    protected $_reviewFactory;
    protected $_urlFactory;

    /**
     * Category constructor.
     *
     * @param \Bazaarvoice\Connector\Logger\Logger      $logger
     * @param \Bazaarvoice\Connector\Helper\Data        $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Review\Model\ReviewFactory       $reviewFactory
     * @param \Magento\Framework\UrlFactory             $urlFactory
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Framework\Filesystem\Io\SftpFactory $sftpFactory
    ) {
        $this->_reviewFactory = $reviewFactory;
        $this->_urlFactory = $urlFactory;
        parent::__construct($logger, $helper, $objectManager, $sftpFactory);
    }

    public function exportReviews()
    {
        $factory = $this->_reviewFactory->create();

        $reviews = $factory->getProductCollection();
        $reviews->addStatusFilter(Review::STATUS_APPROVED);

        $clientName = $this->helper->getConfig('general/client_name', 0);
        $export = $this->openFile('', $clientName);

        $export->startElement('Reviews');

        /** @var Review $review */
        foreach ($reviews as $review) {
            $export->startElement('Review');

            $export->writeElement('ExternalId', $review->getReviewId());
            $export->writeElement('Date', date('c', strtotime($review->getCreatedAt())));
            $export->writeElement('Status', $review->getStatusId());
            $export->writeElement('Title', $review->getTitle());
            $export->writeElement('Content', $review->getDetail());
            $export->writeElement('Nickname', $review->getNickname());

            $export->writeElement('ProductId', $this->helper->getProductId($review->getSku()));
            $export->writeElement('ProductName', $this->helper->getProductId($review->getName()));

            $export->endElement();
            /** Review */
        }

        $export->endElement();
        /** Reviews */
        $this->closeFile($export, BP . '/var/export/bvfeeds/magento-core-reviews.xml');
    }

}
