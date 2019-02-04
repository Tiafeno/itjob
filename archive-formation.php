<?php
get_header();
wp_enqueue_style( 'offers' );
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div class="widget">
        <?php
        if ( is_active_sidebar( 'archive-formation-top' ) ) {
          dynamic_sidebar( 'archive-formation-top' );
        }
        ?>
      </div>
      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <h5 class="vc-element-title">LES FORMATIONS</h5>
            <div class="row mb-5">
              <?php
              if (have_posts()) {
                while ( have_posts() ) : the_post();
                  get_template_part( 'partials/content', 'formation' );
                endwhile;
              } else {
                  ?>
                <div class="col-md-12">
                  <div class="card mb-4">
                    <p>Il n'y a actuellement aucune formation disponible. </p>
                  </div>
                </div>
                <?php
              }
              // Affiche la pagination
              itjob_pagination();

              ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <?php
          if ( is_active_sidebar( 'archive-formation-sidebar' ) ) {
            dynamic_sidebar( 'archive-formation-sidebar' );
          }
          ?>
        </div>
      </div>

    </div>
  </div>
  </div>
<?php
get_footer();