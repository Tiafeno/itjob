<?php
global $wp_query;
$total = $wp_query->max_num_pages;

get_header();
wp_enqueue_style( 'offers' );
?>
  <style type="text/css">
    .navigation {
      list-style: none;
      font-size: 12px;
    }

    .navigation li {
      display: inline;
    }

    .navigation li a {
      display: block;
      float: left;
      padding: 4px 9px;
      margin-right: 7px;
      border: 1px solid #efefef;
    }

    .navigation li a:hover {
      background-color: #e9ecef;
      border-radius: 50%;
    }

    .navigation li span.current {
      display: block;
      float: left;
      padding: 5px 11px;
      margin-right: 7px;
      border: 1px solid #efefef;
      background-color: #004786;
      color: aliceblue;
      border-radius: 26px;
    }

    .navigation li span.dots {
      display: block;
      float: left;
      padding: 4px 9px;
      margin-right: 7px;
    }

  </style>
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
              echo '<div class="navigation">';
              echo paginate_links( array(
                'base'     => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                'format'   => '?paged=%#%',
                'current'  => max( 1, get_query_var( 'paged' ) ),
                'total'    => $total,
                'mid_size' => 4,
                'type'     => 'list'
              ) );
              echo '</div>';

              ?>
            </div>
          </div>
        </div>
        <div class="cold-md-3">
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