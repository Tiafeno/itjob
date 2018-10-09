<?php

function itjob_pagination() {
  global $wp_query;
  $total = $wp_query->max_num_pages;

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
}

/**
 * Vérifier si un client est connecter et s'il est une entreprise ou pas.
 * @return bool|null
 */
function itjob_current_user_is_company() {
  if ( ! is_user_logged_in() ) {
    return false;
  }
  $User = wp_get_current_user();
  $Comp = \includes\post\Company::get_company_by($User->ID);
  return $Comp->is_company();
}

/**
 * Vérifier si l'utilisateur est un particulier ou pas.
 * @return bool|null
 */
function itjob_current_user_is_particular() {
  if ( ! is_user_logged_in() ) {
    return false;
  }
  $User = wp_get_current_user();
  $Cand = \includes\post\Candidate::get_candidate_by($User->ID);
  return $Cand->is_candidate();
}

// Ajouter une notification
add_action('add_notice', 'itjob_add_notice', 10, 2);
function itjob_add_notice($msg, $type = "info") {
  global $it_alerts;
  $it_alerts[] = [
    'message' => $msg,
    'type'    => $type
  ];
}

// Vérifier s'il existe au moins un notification dans le variable
function itjob_has_notice() {
  global $it_alerts;
  return empty($it_alerts);
}

// Récupérer la notification
add_action('get_notice', 'itjob_get_notice');
function itjob_get_notice() {
  global $it_alerts;
  $notice = '';
  if ( ! empty($it_alerts) && is_array($it_alerts)) {
    foreach ($it_alerts as $alert) {
      $alert = (object)$alert;
      $notice .= "<div class=\"alert alert-{$alert->type} alert-dismissable fade show uk-width-1-2 uk-margin-auto mt-5\">";
      $notice .= "<button class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"></button>";
      $notice .= $alert->message;
      $notice .= "</div>";
    }
    echo $notice;
  }
}

/**
 * Envoyer une candidature
 * Call in single-offers.php line 32
 */
add_action('send_apply_offer', function () {
  $action = Http\Request::getValue('action');
  if (trim($action) === 'send_apply') {
    $pId = (int)Http\Request::getValue('post_id', 0);
    $User = wp_get_current_user();
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    // Let WordPress handle the upload.
    // Remember, 'file' is the name of our file input in our form above.
    // @wordpress: https://codex.wordpress.org/Function_Reference/media_handle_upload
    $attachment_id = media_handle_upload( 'motivation', $pId );

    if ( is_wp_error( $attachment_id ) ) {
      // There was an error uploading the image.
      do_action('add_notice', 'Une erreur s\'est produit', 'danger');
    } else {
      // The image was uploaded successfully!
      $apply = get_field('itjob_offer_users', $pId);
      if ( ! is_array($apply)) {
        $apply = [];
      }
      // Verifier l'utilisateur s'il a déja postuler
      if ( in_array($User->ID, $apply)) {
        $apply[] = $User->ID;
        update_field('itjob_users_apply', $apply, $pId);
      } else {

        do_action('add_notice', 'Vous avez déjà postuler sur cette offre', 'warning');
        return true;
      }

      unset($apply);

      $Candidate = \includes\post\Candidate::get_candidate_by($User->ID);
      $offer_apply = get_field('itjob_cv_offer_apply', $Candidate->getId());
      if (! is_array($offer_apply)) {
        $offer_apply = [];
      }

      if (in_array($pId, $offer_apply)) {
        return true;
      }
      $offer_apply[] = $pId;
      update_field('itjob_cv_offer_apply', $offer_apply, $Candidate->getId());
      do_action('add_notice', 'Votre candidature à bien êtes soumis', 'info');

    }
  }

}, 12);