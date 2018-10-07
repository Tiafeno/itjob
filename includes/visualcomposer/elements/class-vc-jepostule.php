<?php
namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}
use Http;
use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Offers;

if ( ! class_exists('jePostule')):
  final class vcJePostule {
    public function __construct() {
      add_action('init', [&$this, 'jepostule_mapping'], 10, 0);
      add_shortcode('vc_jepostule', [&$this, 'jepostule_html']);
    }

    public function jepostule_mapping() {

      // TODO: Crée une page "Je postule"
      $jePostule =  jobServices::page_exists('Je postule');
      if ( $jePostule !== 0 ) {
        add_rewrite_tag( '%oId%', '([^&]+)' );
        add_rewrite_rule( '^apply/([^/]*)/?', 'index.php?page_id=' . $jePostule . '&oId=$matches[1]', 'top' );
      }

      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      \vc_map(
        array(
          'name'        => 'Je Postule Form',
          'base'        => 'vc_jepostule',
          'description' => 'Postulé à une offre',
          'category'    => 'itJob'
        )
      );
    }

    public function jepostule_html( $attrs ) {
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
        )
        , EXTR_OVERWRITE );

      $current_uri = $_SERVER['REQUEST_URI'];
      if ( ! is_user_logged_in()) {
        // Le client est non connecter
        return do_shortcode("[itjob_login role='candidate' redir='{$current_uri}']");
      } else {
        // Le client est connecter
        $User = wp_get_current_user();
        $Candidate = Candidate::get_candidate_by($User->ID);
        if ( ! $Candidate || ! $Candidate->is_candidate()) return $message_access_refused;
        if ( ! $Candidate->hasCV() || !$Candidate->is_publish() || !$Candidate->is_activated()) {
          do_action('add_notice', "Vous devez crée un CV avant de postuler", "warning");
          return do_shortcode("[vc_register_candidate redir='{$current_uri}']");
        }
      }

      $offerId = $wp_query->query_vars['oId'];
      $redirection = Http\Request::getValue('redir');
      if ( ! (int)$offerId) {
        do_action('add_notice', 'Bad link', 'warning');
        itjob_get_notice();
        return;
      }
      $Offer = new Offers((int)$offerId);

      wp_enqueue_script('jquery-validate');
      wp_enqueue_script('jquery-additional-methods');
      try {
        do_action('get_notice');
        /** @var STRING $title - Titre de l'element VC */
        return $Engine->render( '@VC/je-postule.html.twig', [
          'offer' => $Offer,
          'redir' => $redirection
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }
  }
endif;

return new vcJePostule();