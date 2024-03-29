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
    'page_url'       => '',
    ]
);
?><!DOCTYPE html>
<html>
<head>
    <title>BV SDK PHP Example - Questions: GetContent</title>
</head>
<body>
This is a test page for Questions: getContent<br>
This will return questions and answers content<br><br>

<div id="BVQAContainer">
    <?php print_r($bv->questions->getContent()); ?>
</div>

</body>
</html>
