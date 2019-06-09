<?php
get_header();
?>
  <div class="main-section uk-section uk-section-transparent pt-0 pb-0">

    <div class="uk-container uk-container-medium">
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