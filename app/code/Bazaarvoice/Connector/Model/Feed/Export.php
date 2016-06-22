<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package      Bazaarvoice_Connector
 * @author       Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model\Feed;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlFactory;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;

class Export extends Feed
{
    protected $reviewFactory;
    protected $urlFactory;

    /**
     * Category constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     * @param ReviewFactory $reviewFactory
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        ObjectManagerInterface $objectManager,
        ReviewFactory $reviewFactory,
        UrlFactory $urlFactory
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->urlFactory = $urlFactory;
        parent::__construct($logger, $helper, $objectManager);
    }

    public function exportReviews()
    {
        $factory = $this->reviewFactory->create();

        $reviews = $factory->getProductCollection();
        $reviews->addStatusFilter(Review::STATUS_APPROVED);

        $clientName = $this->helper->getConfig('general/client_name', 0);
        $export = $this->openFile('', $clientName);

        $export->startElement('Reviews');

        /** @var Review $review */
        foreach($reviews as $review) {
            $export->startElement('Review');

            $export->writeElement('ExternalId', $review->getReviewId());
            $export->writeElement('Date', date('c', strtotime($review->getCreatedAt())));
            $export->writeElement('Status', $review->getStatusId());
            $export->writeElement('Title', $review->getTitle());
            $export->writeElement('Content', $review->getDetail());
            $export->writeElement('Nickname', $review->getNickname());

            $export->writeElement('ProductId', $this->helper->getProductId($review->getSku()));
            $export->writeElement('ProductName', $this->helper->getProductId($review->getName()));

            $export->endElement(); // Review
        }

        $export->endElement(); // Reviews
        $this->closeFile($export, BP . '/var/export/bvfeeds/magento-core-reviews.xml');

    }

}