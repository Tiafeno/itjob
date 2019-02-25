<?php
global $works;
get_header();
wp_enqueue_style('themify-icons');
wp_enqueue_style('offers');

/**
 * Change the Yoast meta description for Android devices on the front/home page.
 */

?>
  <style type="text/css">
    .offer-top {
      border-bottom: .5px solid #888484;
      width: 100%;
    }

    .offer-footer {
      border-top: .5px solid #888484;
      width: 100%;
    }

    .offer-section .offer-content h1 {
      font-size: 20px;
      font-weight: bold;
    }

    .offer-field-title {
      color: #0C62A2;
      font-weight: bold;
    }

    .work-wrap {
      background: #0e76bc;
      color: white;
      padding: 30px 0;
      -webkit-border-radius: 30px;
      -moz-border-radius: 30px;
      border-radius: 30px;
    }
    .display-5 {
      font-size: 2.2rem;
      font-weight: 300;
      line-height: 1.1;
    }
    .work-content p,
    .work-content a,
    .work-content span,
    .work-content {
      margin-top: 0;
      margin-bottom: 1rem;
      font-size: 1rem;
      font-weight: normal;
      line-height: 1.8;
    }
  </style>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <?php
      while (have_posts()) : the_post();
        if ($works::is_wp_error()) {
          echo $works::is_wp_error();
        }
        if (!$works instanceof \includes\post\Works) continue;
        ?>
        <div class="work-wrap">
          <div class="banner banner-gradient banner-pb-90">
            <div class="container">
              <h1 class="display-5 mb-3 font-bold">
                <?= $works->title ?>
              </h1>
              <div class="banner-subtitle">
                <span><?= $works->reference ?></span>
              </div>
            </div>
          </div>
        </div>

        <div uk-grid>
          <div class="uk-width-2-3@s">
            <!--     Vérifier s'il y a une postulation en cours     -->
            <?php do_action('get_notice'); ?>
            <!--          Content here ... -->
            <div class="pt-4 pl-4 work-content">
              <?= $works->description ?>
            </div>
            <div class="ibox-body pl-4 mt-4">
              <table class="table">
                <tbody>
                  <?php if ($works->price && $works->price !== 0): ?>
                  <tr>
                    <td>Budget indicatif</td>
                    <td><?=  $works->price ?> MGA</td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                    <td>Publié le</td>
                    <td><?= $works->date_publication_format ?></td>
                  </tr>
                  <tr>
                    <td>Début du projet</td>
                    <td>Tout de suite</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="uk-width-1-3@s">
            <!--     Sidebar here ...     -->
            <?php
            if (is_active_sidebar('single-work-sidebar')) {
              dynamic_sidebar('single-work-sidebar');
            }
            ?>
          </div>

        </div>


      <?php

      endwhile;
      ?>

    </div>
  </div>
<?php
get_footer();