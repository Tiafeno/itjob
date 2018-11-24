<?php
get_header();
global $posts, $wp_query;
$s              = $_GET['s'];
$search_results = $posts;
$search_count   = $wp_query->found_posts;
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container ">
      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <div>
              <?php
              $post_type = Http\Request::getValue( 'post_type' );
              echo do_shortcode( "[vc_itjob_search type='$post_type' bg_image='']" );
              ?>
            </div>
            <div class="mb-4">
              <h3 class="mt-1"><?= $search_count ?> résultats trouvés pour:
                <span class="text-primary">“<?= $s ?>”</span>
              </h3>
              <!--              <small class="text-muted">About 1,370 result ( 0.13 seconds)</small>-->
            </div>

            <div class="row mb-5">
              <?php
              while ( have_posts() ) : the_post();
                get_template_part( 'partials/content', get_post_type() );
              endwhile;

              itjob_pagination();
              ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
        </div>
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();