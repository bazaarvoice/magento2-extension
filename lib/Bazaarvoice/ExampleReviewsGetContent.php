<?php
//Please provide cloud_key, bv_root_folder and subject_id
require('bvseosdk.php');
$bv = new BV(array(
  'bv_root_folder' => '',
  'subject_id' => '',
  'cloud_key' => '',
  'page_url' => ''
));
?><!DOCTYPE html>
<html>
  <head>
    <title>BV SDK PHP Example - GetContent</title>
  </head>
  <body>
    This is a test page for Reviews: getContent() <br>
    GetContent() will return reviews and aggregate content <br><br>

    <div id="BVRRContainer">
      <?php echo $bv->reviews->getContent(); ?>
    </div>
  </body>
</html>
