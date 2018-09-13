<?php
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  die( 'WPBakery plugins missing!' );
}
if ( ! class_exists( 'vcSearch' ) ):
  class vcSearch {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_search_mapping' ] );
      add_shortcode( 'vc_itjob_search', [ $this, 'vc_search_template' ] );
      add_action( 'wp_enqueue_scripts', function () {
        global $itJob;
        //wp_enqueue_script('multi-select', get_template_directory_uri() . "/assets/vendors/multiselect/js/jquery.multi-select.js", ['jquery'], $itJob->version);
        wp_enqueue_script( 'select-2', get_template_directory_uri() . "/assets/vendors/select2/dist/js/select2.full.min.js", [ 'jquery' ], $itJob->version );
      } );
    }

    public function vc_search_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      // Map the block with vc_map()
      vc_map(
        array(
          'name'                    => 'La récherche',
          'base'                    => 'vc_itjob_search',
          'content_element'         => true,
          'show_settings_on_create' => true,
          "js_view"                 => 'VcColumnView',
          'description'             => 'Effectuer une recherche sur l\'emplois ou sur les candidats',
          'category'                => 'itJob',
          'params'                  => array(
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
            array(
              "type"        => "attach_image",
              "class"       => "vc-ij-image",
              "heading"     => "Une image de fond",
              "param_name"  => "bg_image",
              "value"       => '',
              "description" => '',
              'admin_label' => true
            )
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
            'title'    => null,
            'bg_image' => '',
            'type'     => 'default'
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      $abranchs = get_terms( 'branch_activity', [
        'hide_empty' => false,
        'fields'     => 'all'
      ] );
      $regions  = get_terms( 'region', [
        'hide_empty' => false,
        'fields'     => 'all'
      ] );
      // Twig template variables
      /** @var string $type */
      /** @var string $title */
      /** @var int $bg_image */

      $data = [
        'title'    => $title,
        'bg_image' => $bg_image,
        'abranchs' => $abranchs,
        'regions'  => $regions
      ];

      if ( $type === 'default' ) {
        try {
          $langage         = get_terms( 'language', [
            'hide_empty' => false,
            'fields'     => 'all'
          ] );
          $master_software = get_terms( 'master_software', [
            'hide_empty' => false,
            'fields'     => 'all'
          ] );
          $sub_data        = [
            'languages' => $langage,
            'softwares' => $master_software
          ];

          $data = array_merge( $data, $sub_data );
          return $Engine->render( '@VC/search/search.html.twig', $data );
        } catch ( Twig_Error_Loader $e ) {
        } catch ( Twig_Error_Runtime $e ) {
        } catch ( Twig_Error_Syntax $e ) {
          echo $e->getRawMessage();
        }
      } else {
        $func = "vc_search_" . $type . "_tpls";
        //return $this->$func( $data );
        return call_user_func_array(array($this, $func), array($data));
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
        $langage         = get_terms( 'language', [
          'hide_empty' => false,
          'fields'     => 'all'
        ] );
        $master_software = get_terms( 'master_software', [
          'hide_empty' => false,
          'fields'     => 'all'
        ] );
        $sub_data        = [
          'languages' => $langage,
          'softwares' => $master_software
        ];

        $data = array_merge( $args, $sub_data );
        return $Engine->render( '@VC/search/search-cv.html.twig', $data );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }
  }
endif;

class WPBakeryShortCode_Vc_itjob_search extends \WPBakeryShortCodesContainer {
}

return new vcSearch();