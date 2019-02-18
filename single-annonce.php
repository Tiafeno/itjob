<?php
global $annonce;
get_header();
wp_enqueue_style('themify-icons');
wp_enqueue_style('camroll-slider');
wp_enqueue_script('camroll-slider');

/**
 * Change the Yoast meta description for Android devices on the front/home page.
 */

?>
  <style type="text/css">
    .container {
      max-width: 760px;
      width: 100%;
      padding: 0 20px;
    }
    #slider {
      width: 100%;
      height: 404px;
      color: white;
    }
    .price {
      color: #f56b2a;
      font-weight: 600;
    }

    @media (max-width: 640px) {

      #slider .crs-bar-roll-current {
        width: 38px;
        height: 38px;
      }

      #slider .crs-bar-roll-item {
        width: 30px;
        height: 30px;
      }
    }

  </style>
<script>
  (function ($) {
    $(document).ready(function () {
      $("#slider").camRollSlider();
    });

  })(jQuery)
</script>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div uk-grid>
        <div class="uk-width-2-3@s">
          <!--     Vérifier s'il y a une postulation en cours     -->
          <?php do_action('get_notice'); ?>
          <!--          Content here ... -->
          <?php
          while (have_posts()) : the_post();
            if ($annonce::is_wp_error()) {
              echo $annonce::is_wp_error();
            }
            if (!$annonce instanceof \includes\post\Annonce) continue;
            $author = $annonce->get_author();

            ?>
          <div class="ibox">
            <div class="ibox-body">
              <div class="container">
                <div id="slider" class="crs-wrap">
                  <div class="crs-screen">
                    <div class="crs-screen-roll">
                      <div class="crs-screen-item" style="background-image: url('https://picsum.photos/1440/810?image=680')">
                        <div class="crs-screen-item-content"></div>
                      </div>
                      <div class="crs-screen-item" style="background-image: url('https://picsum.photos/1440/810?image=676')">
                        <div class="crs-screen-item-content"><h1>Lorem...</h1></div>
                      </div>
                      <div class="crs-screen-item" style="background-image: url('https://picsum.photos/1440/810?image=660')">
                      </div>
                      <div class="crs-screen-item" style="background-image: url('https://picsum.photos/1440/810?image=646')">
                      </div>
                      <div class="crs-screen-item" style="background-image: url('https://picsum.photos/1440/810?image=633')">
                      </div>
                      <div class="crs-screen-item" style="background-image: url('https://picsum.photos/1440/810?image=28')">
                      </div>
                    </div>
                  </div>
                  <div class="crs-bar">
                    <div class="crs-bar-roll-current"></div>
                    <div class="crs-bar-roll-wrap">
                      <div class="crs-bar-roll">
                        <div class="crs-bar-roll-item" style="background-image: url('https://picsum.photos/1440/810?image=680')"></div>
                        <div class="crs-bar-roll-item" style="background-image: url('https://picsum.photos/1440/810?image=676')"></div>
                        <div class="crs-bar-roll-item" style="background-image: url('https://picsum.photos/1440/810?image=660')"></div>
                        <div class="crs-bar-roll-item" style="background-image: url('https://picsum.photos/1440/810?image=646')"></div>
                        <div class="crs-bar-roll-item" style="background-image: url('https://picsum.photos/1440/810?image=633')"></div>
                        <div class="crs-bar-roll-item" style="background-image: url('https://picsum.photos/1440/810?image=628')"></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mt-3"></div>
                <h2 class="page-title font-strong font-19"><?= $annonce->title ?></h2>

                <?php if ($annonce->price && !empty($annonce->price)) : ?>
                  <div class="price font-15"><span><?= $annonce->price ?> AR</span></div>
                <?php endif; ?>

                <div>Déposer le <?= $annonce->date_publication_format ?> par <?= $author->user_nicename ?></div>
                <hr class="mt-5">
                <div>
                  <div class="row mt-4">
                    <div class="col-sm-4">
                      <h6 class="uppercase font-strong">Région</h6>
                      <p><?= $annonce->region->name ?></p>
                    </div>
                    <div class="col-sm-4">
                      <h6 class="uppercase font-strong">Adresse</h6>
                      <p><?= $annonce->address ?></p>
                    </div>
                    <div class="col-sm-4">
                      <h6 class="uppercase font-strong">Ville</h6>
                      <p><?= $annonce->town->name ?></p>
                    </div>
                  </div>
                </div>
                <hr>
                <div class="mt-5">
                  <h5 class="font-strong font-15">Description</h5>
                  <div>
                    <?= $annonce->description ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="ibox-footer">
              <div class="text-right">
                <a href="<?= get_post_type_archive_link('annonce') ?>" class="btn btn-outline-blue">Retour</a>
              </div>
            </div>
          </div>
          <?php

          endwhile;
          ?>
        </div>
        <div class="uk-width-1-3@s">
          <div class="ibox">
            <div class="ibox-body">
              <button type="button" class="btn btn-danger btn-fix btn-block">
                <span class="btn-icon"><i class="la la-phone"></i>Voir le numéro</span>
              </button>
              <a href="?action=contact" data-annonce-id="<?= $annonce->ID ?>" class="btn btn-blue btn-fix d-block mt-2" id="view-number-phone">
                <span class="btn-icon"><i class="la la-envelope-o"></i>Evoyer un message</span>
              </a>
            </div>

          </div>
          <!--     Sidebar here ...     -->
          <?php
          if (is_active_sidebar('single-annonce-sidebar')) {
            dynamic_sidebar('single-annonce-sidebar');
          }
          ?>
        </div>

      </div>
    </div>
  </div>
<?php
get_footer();