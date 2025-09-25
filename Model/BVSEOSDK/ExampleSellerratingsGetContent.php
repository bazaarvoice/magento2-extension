<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */
use Magento\Framework\Escaper;

$escaper = new Escaper();

//Please provide cloud_key and bv_root_folder
$bv = new \Bazaarvoice\Connector\Model\BVSEOSDK\BV(
    [
    'bv_root_folder' => '',
    'cloud_key'      => '',
    'page_url'       => '',
    'subject_id'     => 'seller',
    ]
);
?><!DOCTYPE html>
<html>
<head>
    <title>BV SDK PHP Example - GetContent</title>
</head>
<body>
This is a test page for SellerRatings: getContent() <br>
GetContent() will return seller ratings content <br><br>

<div id="BVRRContainer">
    <?= $escaper->escapeHtml($bv->sellerratings->getContent()); ?>
</div>
</body>
</html>
