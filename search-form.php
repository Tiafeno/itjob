<?php
global $wp_query;
$s = $_GET['s'];
$search_count = $wp_query->found_posts;
?>
<div>
  <?php
  $post_type = get_query_var("post_type");
  echo do_shortcode("[vc_itjob_search type='$post_type' bg_image='']");
  ?>
</div>
<div class="mb-4">
  <h3 class="mt-1"><?= $search_count ?> résultats trouvés pour:
    <span class="text-primary">“<?= $s ?>”</span>
  </h3>
  <!--              <small class="text-muted">About 1,370 result ( 0.13 seconds)</small>-->
</div>