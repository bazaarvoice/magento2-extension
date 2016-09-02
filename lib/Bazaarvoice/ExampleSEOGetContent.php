<?php
//Please provide cloud_key, bv_root_folder and subject_id
require('bvseosdk.php');
$bv = new BV(array(
  'bv_root_folder' => '',
  'subject_id' => '',
  'cloud_key' => '',
  'content_type' => 'questions',
  'subject_type' => 'category',
  'staging' => TRUE
));
?><!DOCTYPE html>
<html>
  <head>
    <title>BV SDK PHP Example - SEO: GetContent</title>
  </head>
  <body>
    This is a test page for SEO getContent<br>
    This will return questions and answers content<br><br>

    <div id="BVQAContainer">
      <?php echo $bv->SEO->getContent(); ?>
    </div>

  </body>
</html>
