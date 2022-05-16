<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

//Please provide cloud_key, bv_root_folder and subject_id
$bv = new \Bazaarvoice\Connector\Model\BVSEOSDK\BV(
    [
    'bv_root_folder'   => '',
    'subject_id'       => '',
    'cloud_key'        => '',
    // either STORIES_LIST or STORIES_GRID
    'content_sub_type' => 'stories_grid',
    'staging'          => true,
    ]
);
?><!DOCTYPE html>
<html>
<head>
    <title>BV SDK PHP Example - GetContent</title>
</head>
<body>
This is a test page for Stories: getContent() <br>
GetContent() will return stories_grid content <br><br>

<div id="BVRRContainer">
    <?php echo $bv->stories->getContent(); ?>
</div>
</body>
</html>
