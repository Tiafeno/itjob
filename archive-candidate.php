<?php
global $wp_query;
get_header();
wp_enqueue_style( 'offers' );
?>
  <div class="uk-section uk-section-transparent">
    <div class="widget-header-cv">
      <?php
      if ( is_active_sidebar( 'cv-header' ) ) {
        dynamic_sidebar( 'cv-header' );
      }
      ?>
    </div>
    <div class="uk-container uk-container-medium">
      <div class="widget-top">
        <?php
        if ( is_active_sidebar( 'archive-cv-top' ) ) {
          dynamic_sidebar( 'archive-cv-top' );
        }
        if ($wp_query->is_search) :
          get_template_part('search', 'form');
        endif;
        ?>
      </div>

<!--      La liste des CV -->
      <div class="row">
        <div class="col-md-8">
          <!--      Un bouton pour ajouter un CV -->
          <div class="row">
            <div class="col-lg-12">

            </div>
          </div>
          <div class="container-list-posts">
            <h5 class="vc-element-title">LISTE CV</h5>
            <div class="row mb-5 ">
              <?php
              if (have_posts()) {
                while ( have_posts() ) : the_post();
                  get_template_part( 'partials/content', 'candidate' );
                endwhile;
              } else {
                if ( ! $wp_query->is_search) {
                  ?>
                  <div class="col-lg-12">
                    <div class="card mb-4">
                      <p>Il n'y a actuellement aucun candidat disponible. </p>
                    </div>
                  </div>
                  <?php
                } else {
                  ?>
                  <div class="col-lg-12">
                    <div class="card mb-4">
                      <p>Aucun candidat correspond à votre recherche. </p>
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
          if ( is_active_sidebar( 'archive-cv-sidebar' ) ) {
            dynamic_sidebar( 'archive-cv-sidebar' );
          }
          ?>
        </div>
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();