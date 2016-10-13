<?php
//Please provide cloud_key and bv_root_folder
require('bvseosdk.php');

$bv = new BV(array(
    'bv_root_folder' => '',
    'cloud_key' => '',
    'page_url' => '',
    'subject_id' => 'seller'
));
?><!DOCTYPE html>
<html>
<head>
    <title>BV SDK PHP Example - GetContent</title>
</head>
<body>
This is a test page for SellerRatings: getContent() <br>
GetContent() will return seller ratings content <br><br>

<div id="BVRRContainer">
    <?php echo $bv->sellerratings->getContent(); ?>
</div>
</body>
</html>
