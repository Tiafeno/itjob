<?php
global $w_query;

get_header();
?>
  <script type="text/javascript">
    (function ($) {
      $(document).ready(function () {
        var media = $('.media');
        media.each(function (index, element) {
          var mediaImg = $(element).find(".media-img");
          var bgImg = mediaImg.data('bg-image');
          mediaImg.css({
            'background': `#cad1d9 url(${bgImg}}) no-repeat center center`,
            'background-size': 'contain',
            width: mediaImg.data('width'),
            height: mediaImg.data('height'),
            cursor: 'pointer'
          });
          mediaImg.on('click', function (ev) {
            var url = mediaImg.data('url');
            if (_.isUndefined(url) || _.isEmpty(url)) return false;
            window.location.href = url;
          });
        });

        $('.price').each(function (index, el) {
          var priceValue = $(el).text().trim();
          $(el).text(new Intl.NumberFormat('de-DE', {
            style: "currency",
            minimumFractionDigits: 0,
            currency: 'MGA'
          }).format(priceValue));
        });
      });
    })(jQuery)
  </script>
  <style type="text/css">
    .price {
      color: #f56b2a;
      font-weight: 600;
    }
  </style>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div class="widget">
        <?php
        if ( is_active_sidebar( 'archive-annonce-top' ) ) {
          dynamic_sidebar( 'archive-annonce-top' );
        }
        if ($wp_query->is_search) :
          get_template_part('search', 'form');
        endif;
        ?>
      </div>
      <div class="row">
        <div class="col-md-8">
          <div class="container-list-posts">
            <h5 class="vc-element-title">LES ANNONCES</h5>
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
                          get_template_part( 'partials/content', 'annonce' );
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
                      <p>Il n'y a actuellement aucune annonce disponible. </p>
                    </div>
                  </div>
                  <?php
                } else {
                  ?>
                  <div class="col-lg-12">
                    <div class="card mb-4">
                      <p>Aucune annonce correspond à votre recherche. </p>
                    </div>
                  </div>
                <?php
                }
              }
              itjob_pagination();
              ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <?php
          if ( is_active_sidebar( 'archive-annonce-sidebar' ) ) {
            dynamic_sidebar( 'archive-annonce-sidebar' );
          }
          ?>
        </div>
      </div>

    </div>
  </div>
  </div>
<?php
get_footer();