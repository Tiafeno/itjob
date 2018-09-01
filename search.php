<?php
get_header();
?>
  <div class="uk-section uk-section-transparent">

    <div class="uk-container uk-container-small">
      <?php
      while ( have_posts() ) : the_post();
        get_template_part( 'partials/content', get_post_type() );
      endwhile;
      ?>
    </div>
  </div>
  </div>
<?php
get_footer();