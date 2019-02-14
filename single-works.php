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
  </style>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div uk-grid>
        <div class="uk-width-2-3@s">
          <!--     Vérifier s'il y a une postulation en cours     -->
          <?php do_action('get_notice'); ?>
          <!--          Content here ... -->
          <?php
          while (have_posts()) : the_post();
            if ($works::is_wp_error()) {
              echo $works::is_wp_error();
            }
            if (!$works instanceof \includes\post\Works) continue;

          endwhile;
          ?>
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
    </div>
  </div>
<?php
get_footer();