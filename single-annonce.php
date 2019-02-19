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
    #slider {
      width: 100%;
      height: 404px;
      color: white;
    }
    #slider .crs-screen-item {
      background-size: contain;
      background-repeat: no-repeat;
      background-color: #FFFFFF;
      background-position: bottom;
    }
    #slider .crs-bar-roll-current {
      border: 4px solid #fff;
      top: 7px;
    }
    .container {
      max-width: 760px;
      width: 100%;
      padding: 0 20px;
    }
    .price {
      color: #f56b2a;
      font-weight: 600;
    }
    .crs-screen:before {
      content: "";
      display: block;
      bottom: 0;
      background: -webkit-gradient(linear,left top,left bottom,from(transparent),to(rgba(0,0,0,.3)));
      background: -webkit-linear-gradient(top,transparent,rgba(0,0,0,.3));
      background: linear-gradient(180deg,transparent 0,rgba(0,0,0,.3));
      height: 64px;
      width: 100%;
      position: absolute;
      z-index: 9;
    }
    .crs-bar {
      z-index: 10;
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
              continue;
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

                      <?php foreach ($annonce->gallery as $gallery): ?>
                      <div class="crs-screen-item" style="background-image: url('<?= $gallery['url'] ?>')">
                        <div class="crs-screen-item-content"></div>
                      </div>
                      <?php endforeach; ?>

                    </div>
                  </div>
                  <div class="crs-bar">
                    <div class="crs-bar-roll-current"></div>
                    <div class="crs-bar-roll-wrap">
                      <div class="crs-bar-roll">
                        <?php foreach ($annonce->gallery as $gallery): ?>
                        <div class="crs-bar-roll-item" style="background-image: url('<?= $gallery['sizes']['thumbnail'] ?>')"></div>
                        <?php endforeach; ?>
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
                    <?php if ($annonce->town): ?>
                    <div class="col-sm-4">
                      <h6 class="uppercase font-strong">Ville</h6>
                      <p><?= $annonce->town->name ?></p>
                    </div>
                    <?php endif; ?>
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
          if (is_active_sidebar('single-annonce-sidebar')) { dynamic_sidebar('single-annonce-sidebar'); }
          ?>
        </div>

      </div>
    </div>
  </div>
<?php
get_footer();