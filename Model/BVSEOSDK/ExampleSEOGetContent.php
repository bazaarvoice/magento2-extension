<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

//Please provide cloud_key, bv_root_folder and subject_id
$bv = new \Bazaarvoice\Connector\Model\BVSEOSDK\BV(
    [
    'bv_root_folder' => '',
    'subject_id'     => '',
    'cloud_key'      => '',
    'content_type'   => 'questions',
    'subject_type'   => 'category',
    'staging'        => true,
    ]
);
?><!DOCTYPE html>
<html>
<head>
    <title>BV SDK PHP Example - SEO: GetContent</title>
</head>
<body>
This is a test page for SEO getContent<br>
This will return questions and answers content<br><br>

<div id="BVQAContainer">
    <?php print_r($bv->SEO->getContent()); ?>
</div>

</body>
</html>
