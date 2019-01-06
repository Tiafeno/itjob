<?php
namespace includes\vc;

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('WPBakeryShortCode')) {
  new \WP_Error('WPBakery', 'WPBakery plugins missing!');
}

use Http;
use includes\model\itModel;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;

if (!class_exists('jePostule')) :
  final class vcJePostule
{
  public function __construct()
  {
    add_action('init', [&$this, 'jepostule_mapping'], 10, 0);
    add_action('je_postule', [&$this, 'je_postule_Fn']);
    add_shortcode('vc_jepostule', [&$this, 'jepostule_html']);

    /**
     * Envoyer une candidature
     * Call in single-offers.php line 32
     */
    add_action('send_apply_offer', function () {
      $action = Http\Request::getValue('action');
      if (trim($action) === 'send_apply') {
        $pId = (int)Http\Request::getValue('post_id', 0);
        $id_offer = &$pId;
        $User = wp_get_current_user();
        if (!$User->ID) {
          do_action("add_notice", 'Une erreur s\'est produite', 'danger');

          return false;
        }

        $itModel = new itModel();

          // FEATURED: Vérifier si l'entreprise s'est déja interesser pour ce candidat
        if (in_array('candidate', $User->roles)) {
          $Candidate = Candidate::get_candidate_by($User->ID);
          if ($itModel->exist_interest($Candidate->getId(), $id_offer)) {
            do_action('add_notice', "L'entreprise s'intéresse déjà à votre CV pour cette offre. Veuillez patienter", 'info');
            return true;
          }
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

          // Let WordPress handle the upload.
          // Remember, 'file' is the name of our file input in our form above.
          // @wordpress: https://codex.wordpress.org/Function_Reference/media_handle_upload
        $attachment_id = media_handle_upload('motivation', $id_offer);

        if (is_wp_error($attachment_id)) {
            // There was an error uploading the file.
          do_action('add_notice', 'Une erreur s\'est produite', 'danger');
        } else {
            // The file was uploaded successfully!

            // Enregistrer la requete dans la base de donnée
          $Offer = new Offers($id_offer);
          $offer_author = $Offer->getAuthor();
          $Company = Company::get_company_by($offer_author->ID);
          $Candidate = Candidate::get_candidate_by($User->ID);
          $result = $itModel->added_interest($Candidate->getId(), $id_offer, $Company->getId(), 'pending', 'apply', $attachment_id);
          if (!$result) {
            do_action('add_notice', 'Une erreur s\'est produite pendant la requête. Veuillez réessayer plus tard', 'warning');

            return false;
          }
            // Récuperer les offres que le candidat a déja postulé
          $offer_apply = get_field('itjob_cv_offer_apply', $Candidate->getId());
          if (!is_array($offer_apply)) {
            $offer_apply = [];
          }
            // On verifie si l'offre est déja dans sa liste
          if (in_array($id_offer, $offer_apply)) {
            return true;
          }
            // Ajouter l'offre dans le champ pour les offres postulé par le candidat
          $offer_apply[] = $id_offer;
          update_field('itjob_cv_offer_apply', $offer_apply, $Candidate->getId());

          do_action('alert_admin_postuled_offer', $id_offer);
          do_action('notice-candidate-postuled', $Candidate->getId(), $id_offer); // Ajouter une notification
          do_action('add_notice', 'Votre candidature à bien êtes soumis', 'info');
        }
      }
    }, 12);


  }

  public function jepostule_mapping()
  {
      // Featured: Crée une page "Je postule"
    $jePostule = jobServices::page_exists('Je postule');
    if ($jePostule !== 0) {
      add_rewrite_tag('%oId%', '([^&]+)');
      add_rewrite_rule('^apply/([^/]*)/?', 'index.php?page_id=' . $jePostule . '&oId=$matches[1]', 'top');
    }
      // Stop all if VC is not enabled
    if (!defined('WPB_VC_VERSION')) {
      return;
    }
    \vc_map(
      array(
        'name' => 'Je Postule Form',
        'base' => 'vc_jepostule',
        'description' => 'Postulé à une offre',
        'category' => 'itJob'
      )
    );
  }

  public function jepostule_html($attrs)
  {
    global $Engine, $wp_query;
    $message_access_refused = '<div class="d-flex align-items-center">';
    $message_access_refused .= '<div class="uk-margin-large-top uk-margin-auto-left uk-margin-auto-right text-uppercase">Access refuser</div></div>';
      // Params extraction
    extract(
      shortcode_atts(
        array(
          'title' => null
        ),
        $attrs
      ),
      EXTR_OVERWRITE
    );

    $current_uri = $_SERVER['REQUEST_URI'];
    if (!is_user_logged_in()) {
        // Le client est non connecter
      do_action('add_notice', '<i class="la la-warning alert-icon"></i> Pour pouvoir postuler à cette offre, vous devez vous connecter ou créer un compte', 'info');

      return do_shortcode("[itjob_login role='candidate' redir='{$current_uri}']");
    } else {
        // Le client est connecter
      $User = wp_get_current_user();
      $Candidate = Candidate::get_candidate_by($User->ID);
      if (!$Candidate || !$Candidate->is_candidate()) {
        return $message_access_refused;
      }
      if (!$Candidate->hasCV()) {
        do_action('add_notice', "Vous devez créer un CV avant de postuler", "warning");

        return do_shortcode("[vc_register_candidate redir='{$current_uri}']");
      }

      if (!$Candidate->is_publish() && !$Candidate->is_activated()) {
        do_action('add_notice', 'Votre CV est en cours de validation. Veuillez réessayer plus tard s\'il vous plaît', 'warning');
        itjob_get_notice();
        return;
      }
    }

    $offerId = $wp_query->query_vars['oId'];
    $redirection = Http\Request::getValue('redir');
    if (!(int)$offerId) {
      do_action('add_notice', 'Une erreur s\'est produite.', 'warning');
      itjob_get_notice();

      return;
    }
    $Offer = new Offers((int)$offerId);

    wp_enqueue_script('jquery-validate');
    wp_enqueue_script('jquery-additional-methods');
    try {
      do_action('get_notice');

      /** @var STRING $title - Titre de l'element VC */
      return $Engine->render('@VC/je-postule.html.twig', [
        'offer' => $Offer,
        'redir' => $redirection
      ]);
    } catch (\Twig_Error_Loader $e) {
    } catch (\Twig_Error_Runtime $e) {
    } catch (\Twig_Error_Syntax $e) {
      return $e->getRawMessage();
    }
  }

  /**
   * Cette action permet d'afficher le bouton "je postule'
   */
  public function je_postule_Fn()
  {
    global $itJob;
    if ($itJob->services->isClient() === 'company') {
      return;
    }
    $offer_id = get_the_ID();
    $offer_url = get_the_permalink($offer_id);
    $href = home_url("/apply/{$offer_id}?redir={$offer_url}");
    $button = "<div class=\"float-right ml-3\">
                  <a href=\"$href\">
                    <button class=\"btn btn-blue btn-fix\">
                      <span class=\"btn-icon\">Je postule </span>
                    </button>
                  </a>
                 </div>";
    echo $button;
  }

}
endif;

return new vcJePostule();