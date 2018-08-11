<?php
get_header();
?>
  <div class="uk-section uk-section-large">
    <div class="uk-container uk-container-small">
      <?php
      while (have_posts()) : the_post();
        the_content();
      endwhile;
      ?>
    </div>
  </div>

<?php
get_footer();