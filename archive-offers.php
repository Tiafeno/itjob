<?php
get_header();
wp_enqueue_style( 'offers' );
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div class="widget">
        <?php
        if ( is_active_sidebar( 'archive-offer-top' ) ) {
          dynamic_sidebar( 'archive-offer-top' );
        }
        ?>
      </div>
      <div class="row">
        <div class="col-md-9">
          <div class="container-list-posts">
            <h5 class="vc-element-title">LES OFFRES D’EMPLOI</h5>
            <div class="row mb-5">
              <?php
              while ( have_posts() ) : the_post();
                get_template_part( 'partials/content', 'offers' );
              endwhile;

              // Affiche la pagination
              itjob_pagination();

              ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
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