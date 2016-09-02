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
    <title>BV SDK PHP Example - Questions: GetContent</title>
  </head>
  <body>
    This is a test page for Questions: getContent<br>
    This will return questions and answers content<br><br>

    <div id="BVQAContainer">
      <?php echo $bv->questions->getContent(); ?>
    </div>

  </body>
</html>
