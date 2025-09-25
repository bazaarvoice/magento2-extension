<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */
use Magento\Framework\Escaper;

$escaper = new Escaper();

//Please provide cloud_key, bv_root_folder and product_id
$bv = new \Bazaarvoice\Connector\Model\BVSEOSDK\BV(
    [
    'bv_root_folder' => '',
    'subject_id'     => '',
    'cloud_key'      => '',
    'page_url'       => '',
    ]
);
?><!DOCTYPE html>
<html>
<head>
    <title>BV SDK PHP Example - GetContent</title>
</head>
<body>
This is a test page for Spotlights: getContent() <br>
GetContent() will return spotlights content <br><br>

<div id="BVRRContainer">
    <?= $escaper->escapeHtml($bv->spotlights->getContent()); ?>
</div>
</body>
</html>
