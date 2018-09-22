<?php
get_header();
wp_enqueue_style( 'offers' );
?>
  <div class="uk-section uk-section-transparent">
    <?php
    if ( is_active_sidebar( 'cv-header' ) ) {
      dynamic_sidebar( 'cv-header' );
    }
    ?>
    <div class="uk-container uk-container-medium">
      <div class="widget-top">
        <?php
        if ( is_active_sidebar( 'archive-cv-top' ) ) {
          dynamic_sidebar( 'archive-cv-top' );
        }
        ?>
      </div>
      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <h5 class="vc-element-title">LES CANDIDATES</h5>
            <div class="row mb-5 ">
              <?php
              if (have_posts()) {
                while ( have_posts() ) : the_post();
                  get_template_part( 'partials/content', 'candidate' );
                endwhile;
              } else {
                ?>
                  <div class="col-md-12">
                    <div class="card mb-4">
                      <p>Pour le moment, aucune CV disponible n'est proposée. </p>
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