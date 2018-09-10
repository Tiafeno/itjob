<?php
get_header();
?>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-small">
      <div class="row">
        <div class="col-md-9">
          <div class="container-list-posts">

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
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();