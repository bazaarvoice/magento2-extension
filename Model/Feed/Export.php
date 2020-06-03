<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Feed;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Framework\UrlFactory;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;

/**
 * Class Export
 *
 * @package Bazaarvoice\Connector\Model\Feed
 */
class Export extends Feed
{
    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $reviewFactory;

    /**
     * @var \Magento\Framework\UrlFactory
     */
    protected $urlFactory;

    /**
     * Export constructor.
     *
     * @param Logger $logger
     * @param ReviewFactory $reviewFactory
     * @param UrlFactory $urlFactory
     * @param StringFormatterInterface $stringFormatter
     * @param ConfigProviderInterface $configProvider
     * @param XMLWriter $XMLWriter
     */
    public function __construct(
        Logger $logger,
        ReviewFactory $reviewFactory,
        UrlFactory $urlFactory,
        StringFormatterInterface $stringFormatter,
        ConfigProviderInterface $configProvider,
        XMLWriter $XMLWriter
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->urlFactory = $urlFactory;
        $this->stringFormatter = $stringFormatter;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->xmlWriter = $XMLWriter;
    }

    public function exportReviews()
    {
        $factory = $this->reviewFactory->create();

        $reviews = $factory->getProductCollection();
        $reviews->addStatusFilter(Review::STATUS_APPROVED);

        $clientName = $this->configProvider->getClientName(0);
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

            $export->writeElement('ProductId', $this->stringFormatter->getFormattedProductSku($review->getSku()));
            $export->writeElement('ProductName', $this->stringFormatter->getFormattedProductSku($review->getName()));

            $export->endElement(); /** Review */
        }

        $export->endElement(); /** Reviews */
        $this->closeFile($export, BP . '/var/export/bvfeeds/magento-core-reviews.xml');
    }
}
