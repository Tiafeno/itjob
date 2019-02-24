<?php
global $wp_query;
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
        if ($wp_query->is_search) :
          get_template_part('search', 'form');
        endif;
        ?>
      </div>
      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <h5 class="vc-element-title">LES OFFRES D’EMPLOI</h5>
            <div class="row mb-5">
              <?php
              if (have_posts()) {
                while ( have_posts() ) : the_post();
                  get_template_part( 'partials/content', 'offers' );
                endwhile;
              } else {
                if ( ! $wp_query->is_search) {
                  ?>
                  <div class="col-lg-12">
                    <div class="card mb-4">
                      <p>Il n'y a actuellement aucune offre disponible. </p>
                    </div>
                  </div>
                  <?php
                } else {
                  ?>
                  <div class="col-lg-12">
                    <div class="card mb-4">
                      <p>Aucune offre correspond à votre recherche. </p>
                    </div>
                  </div>
                  <?php
                }
              }
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