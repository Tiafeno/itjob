<?php
get_header();
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-small">
      <h4 class="m-0">LES ENTREPRISES</h4>
      <?php
      while ( have_posts() ) : the_post();
        get_template_part( 'partials/content', 'company' );
      endwhile;

      itjob_pagination();

      ?>
    </div>
  </div>
  </div>
<?php
get_footer();