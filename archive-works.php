<?php
global $wp_query;
get_header();
wp_enqueue_style( 'offers' );
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div class="widget">
        <?php
        if ( is_active_sidebar( 'archive-works-top' ) ) {
          dynamic_sidebar( 'archive-works-top' );
        }
        if ($wp_query->is_search) :
          get_template_part('search', 'form');
        endif;

        echo do_shortcode("[vc_itjob_ads position='position-12' size='1120x210']");
        ?>
      </div>
      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <h5 class="vc-element-title">LES TRAVAILS TEMPORAIRES</h5>
            <div class="row mb-5">
                <?php
                if (have_posts()) {
                  ?>
                  <div class="col-lg-12">
                      <div class="ibox">
                        <div class="ibox-body">
                          <ul class="media-list media-list-divider">
                            <?php
                            while ( have_posts() ) : the_post();
                              get_template_part( 'partials/content', 'works' );
                            endwhile;
                            ?>
                          </ul>
                        </div>
                      </div>
                  </div>
                <?php

                } else {
                  if ( ! $wp_query->is_search) {
                    ?>
                    <div class="col-lg-12">
                      <div class="card mb-4">
                        <p>Il n'y a actuellement aucun travail disponible. </p>
                      </div>
                    </div>
                    <?php
                  } else {
                    ?>
                    <div class="col-lg-12">
                      <div class="card mb-4">
                        <p>Aucun travail correspond à votre recherche. </p>
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
          echo do_shortcode("[vc_itjob_ads position='position-13' size='354x330']");

          if ( is_active_sidebar( 'archive-works-sidebar' ) ) {
            dynamic_sidebar( 'archive-works-sidebar' );
          }
          ?>
        </div>
      </div>

    </div>
  </div>
  </div>
<?php
get_footer();