<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 13/08/2018
 * Time: 11:05
 */
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  die( 'WPBakery plugins missing!' );
}
if ( ! class_exists( 'vcSearch' ) ):
  class vcSearch {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_search_mapping' ] );
      add_shortcode( 'vc_itjob_search', [ $this, 'vc_search_template' ] );
    }

    public function vc_search_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      // Map the block with vc_map()
      vc_map(
        array(
          'name'        => 'VC Search Element',
          'base'        => 'vc_itjob_search',
          'description' => 'Effectuer une recherche sur l\'emplois ou sur les candidats',
          'category'    => 'itJob',
          'params'      => array(
            array(
              'type'        => 'dropdown',
              'class'       => 'vc-ij-type',
              'heading'     => '',
              'param_name'  => 'type',
              'value'       => [
                'Par default' => 'default',
                'Entreprise'  => 'company',
                'Offres'      => 'offers',
                'CV'          => 'candidate'
              ],
              'std'         => 'default',
              'description' => "Modifier le mode d'affichage",
              'admin_label' => true
            ),
          )
        )
      );
    }

    public function vc_search_template( $attrs ) {
      global $Engine;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => null,
            'type'  => 'default'
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      // Twig template variables
      /** @var string $type */
      /** @var string $title */
      $data = [
        'title' => $title
      ];

      if ( $type === 'default' ) {
        try {
          return $Engine->render( '@VC/search/search.html.twig', $data );
        } catch ( Twig_Error_Loader $e ) {
        } catch ( Twig_Error_Runtime $e ) {
        } catch ( Twig_Error_Syntax $e ) {
          echo $e->getRawMessage();
        }
      } else {
        return $this->{"vc_search_" . $type . "_tpls"}( $data );
      }
    }

    // Invoked in vc_search_template methode
    private function vc_search_company_tpls( $args ) {
      global $Engine;
      try {
        return $Engine->render( '@VC/search/search-company.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }

    // Invoked in vc_search_template methode
    private function vc_search_offers_tpls( $args ) {
      global $Engine;
      try {
        return $Engine->render( '@VC/search/search-offers.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }

    // Invoked in vc_search_template methode
    private function vc_search_candidate_tpls( $args ) {
      global $Engine;
      try {
        return $Engine->render( '@VC/search/search-cv.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }
  }
endif;

return new vcSearch();