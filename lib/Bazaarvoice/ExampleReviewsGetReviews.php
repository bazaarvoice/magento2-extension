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
    <title>BV SDK PHP Example - getReviews</title>
  </head>
  <body>
    This is a test page for Reviews: getReviews()<br>
    This will return review content<br><br>

    <div id="BVRRContainer">
      <?php echo $bv->reviews->getReviews(); ?>
    </div>
  </body>
</html>
