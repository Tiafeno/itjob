<?php
namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}
use Http;
use includes\post\Offers;

if ( ! class_exists('jePostule')):
  final class vcJePostule {
    public function __construct() {
      add_action('init', [&$this, 'jepostule_mapping']);
      add_shortcode('vc_jepostule', [&$this, 'jepostule_html']);
    }

    public function jepostule_mapping() {
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
      global $Engine;

      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => null
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      $offerId = Http\Request::getValue('offerId', 0);
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