<?php
//Please provide cloud_key, bv_root_folder and subject_id
require('bvseosdk.php');
$bv = new BV(array(
  'bv_root_folder' => '',
  'subject_id' => '',
  'cloud_key' => '',
  // either STORIES_LIST or STORIES_GRID
  'content_sub_type' => 'stories_grid',
  'staging' => TRUE
));
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
