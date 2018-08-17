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
          'name'        => 'Offers à la une',
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
      vc_map(
        [
          'name'        => 'Les offres d\'emploi',
          'base'        => 'vc_offers',
          'description' => 'Afficher la liste de tous les offres',
          'category'    => 'itJob',
          'params'      => [
            [
              'type'        => 'textfield',
              'holder'      => 'h3',
              'class'       => 'vc-ij-title',
              'heading'     => 'Ajouter un titre',
              'param_name'  => 'title',
              'value'       => '',
              'admin_label' => false,
              'weight'      => 0
            ],
            [
              'type'        => 'dropdown',
              'class'       => 'vc-ij-orderby',
              'heading'     => 'Désigne l\'ascendant ou descendant',
              'param_name'  => 'orderby',
              'value'       => [
                'Date'  => 'date',
                'Titre' => 'title'
              ],
              'admin_label' => false,
              'weight'      => 0
            ],
            [
              'type'        => 'dropdown',
              'class'       => 'vc-ij-order',
              'heading'     => 'Trier',
              'param_name'  => 'order',
              'value'       => [
                'Ascendant'  => 'ASC',
                'Descendant' => 'DESC'
              ],
              'admin_label' => false,
              'weight'      => 0
            ]
          ]
        ]
      );
    }

    public function vc_offers_render( $attrs ) {
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title'   => null,
            'orderby' => 'DATE',
            'order'   => 'DESC'
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

    }

    public function vc_featured_offers_render( $attrs ) {
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
      /** @var string $style */
      /** @var string $title */
      if ( trim( $title ) === 'sidebar' ) {
        return $this->getPositionSidebar( $title );
      } else {
        return $this->getPositionContent( $title );
      }

    }

    /**
     * Position sidebar
     *
     * @param string $title
     *
     * @return mixed
     */
    public function getPositionSidebar( $title ) {
      global $Engine;
      try {
        return $Engine->render( '@VC/offers/sidebar.html.twig', [
          'title' => $title,
        ] );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }

    }

    /**
     * Position content
     *
     * @param string $title
     *
     * @return mixed
     */
    public function getPositionContent( $title ) {
      global $Engine;
      try {
        return $Engine->render( '@VC/offers/content.html.twig', [
          'title' => $title,
        ] );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

    public static function vc_our_offers() {

    }

    public static function vc_offer_recently() {

    }
  }
endif;

return new vcOffers();