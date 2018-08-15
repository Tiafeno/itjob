<?php
/**
 * Class vcOffers
 * @method vc_our_offer - Récuperer les offres à la une
 * @method vc_offer_recently - Récuperer les offres recements ajouter
 */
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  die( 'WPBakery plugins missing!' );
}
if ( ! class_exists( 'vcOffers' ) ):
  class vcOffers {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_offers_mapping' ] );

      add_shortcode( 'vc_offers', [ $this, 'vc_offers_render' ] );
      add_shortcode( 'vc_featured_offers', [ $this, 'vc_featured_offers_render' ] );
    }

    public function vc_offers_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      // Map the block with vc_map()

      // Les offres à la une
      vc_map(
        array(
          'name'        => 'Featured Offers',
          'base'        => 'vc_featured_offers',
          'description' => 'Afficher les offres à la une.',
          'category'    => 'itJob',
          'params'      => array(
            array(
              'type'        => 'textfield',
              'holder'      => 'h3',
              'class'       => 'vc-ij-title',
              'heading'     => 'Titre',
              'param_name'  => 'title',
              'value'       => '',
              'description' => "Une titre pour les offres à la une",
              'admin_label' => false,
              'weight'      => 0
            ),
            array(
              'type'        => 'dropdown',
              'class'       => 'vc-ij-style',
              'heading'     => 'Style',
              'param_name'  => 'style',
              'value'       => [
                'Sur le côté'  => 'sidebar',
                'Sur le large' => 'content'
              ],
              'description' => "Modifier le mode d'affichage",
              'admin_label' => false,
              'weight'      => 0
            ),
          )
        )
      );

      // Les offres

    }

    public function vc_featured_offers_render( $attrs ) {
      global $Engine;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => 'Offres à la une',
            'style' => ''
          ),
          $attrs
        )
        , EXTR_OVERWRITE );
    }

    public static function vc_our_offers() {

    }

    public static function vc_offer_recently() {

    }
  }
endif;

return new vcOffers();