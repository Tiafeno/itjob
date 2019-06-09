<?php
/**
 * Template Name: Template FAQ
 */

$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = str_replace('/', '', $request_uri);
$path_array = explode('-', $request_uri);

if (is_user_logged_in()) {
  $User = wp_get_current_user();
  $role = $User->roles[0];
  $current_role = $role === 'candidate' ? 'particular' : 'company';

  if ($current_role !== $path_array[1] && $role !== 'administrator') {
    wp_redirect(home_url('/faq-'.$current_role));
    exit;
  }
}

get_header();

?>
  <div class="main-section uk-section uk-section-transparent pt-4 pb-0">

    <div class="uk-container uk-container-medium">
      <?php
      while (have_posts()) : the_post();
        if ( ! is_user_logged_in()) {
          echo '<div class="alert alert-danger text-center"><i class="fa fa-warning mr-2"></i>' .
            'Vous devez disposer d\'une autorisation pour accéder à cette page. Merci</div>';
          break;
        }

        the_content();

      endwhile;
      ?>
    </div>
  </div>
  </div>
<?php
get_footer();