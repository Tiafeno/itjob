<?php
get_header();
?>
  <div class="uk-section uk-section-transparent">

    <div class="uk-container uk-container-small" style="min-height: 250px;">
      <?php
      while (have_posts()) : the_post();
        the_content();
      endwhile;
      ?>
    </div>
  </div>
  </div>
<?php
get_footer();