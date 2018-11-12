<?php
get_header();
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container ">
      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <?php
            $post_type = Http\Request::getValue( 'post_type' );
            echo do_shortcode( "[vc_itjob_search type='$post_type' bg_image='']" );
            ?>
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