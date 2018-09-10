<?php
get_header();
wp_enqueue_style( 'offers' );
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">

      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <h5 class="vc-element-title">LES CANDIDATES</h5>
            <div class="row mb-5 ">
              <?php
              while ( have_posts() ) : the_post();
                get_template_part( 'partials/content', 'candidate' );
              endwhile;

              // Affiche la pagination
              itjob_pagination();

              ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <?php
          if ( is_active_sidebar( 'archive-offer-sidebar' ) ) {
            dynamic_sidebar( 'archive-offer-sidebar' );
          }
          ?>
        </div>
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();