<?php
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new WP_Error('WPBakery', 'WPBakery plugins missing!' );
}
if ( ! class_exists( 'vcSearch' ) ):
  class vcSearch {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_search_mapping' ] );
      add_shortcode( 'vc_itjob_search', [ $this, 'vc_search_template' ] );
      add_action( 'wp_enqueue_scripts', function () {
        global $itJob;
        //wp_enqueue_script('multi-select', get_template_directory_uri() . "/assets/vendors/multiselect/js/jquery.multi-select.js", ['jquery'], $itJob->version);
        wp_enqueue_script( 'select-2', VENDOR_URL . "/select2/dist/js/select2.full.min.js", [ 'jquery' ], $itJob->version );
        wp_enqueue_script( 'select-2-it8n-fr', VENDOR_URL . "/select2/dist/js/i18n/fr.js", [ 'select-2' ], $itJob->version );
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
              'heading'     => 'Recherche pour:',
              'param_name'  => 'type',
              'value'       => [
                'Par default' => 'default',
                'Offres'      => 'offers',
                'CV'          => 'candidate',
                'Formations'  => 'formation',
                'Travail Temporaire'  => 'works',
                'Petit Annonce'  => 'annonce'
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
        'regions'  => $regions,
        'home_url' => home_url( '/' ),
        'post_type' => $type
      ];

      if ( $type === 'default' || empty( $type ) ) {
        try {
          $langage  = get_terms( 'language', [
            'hide_empty' => false,
            'fields'     => 'all'
          ] );

          $software = get_terms( 'software', [
            'hide_empty' => false,
            'fields'     => 'all'
          ] );

          $sub_data = [
            'languages' => $langage,
            'softwares' => $software
          ];

          $add_cv_link = get_the_permalink( REGISTER_CANDIDATE_PAGE_ID );
          $add_offer_link = get_the_permalink( ADD_OFFER_PAGE );
          
          $data['add_cv_link'] = $add_cv_link;
          $data['add_offer_link'] = $add_offer_link;

          $data = array_merge( $data, $sub_data );

          return $Engine->render( '@VC/search/search.html.twig', $data );
        } catch ( Twig_Error_Loader $e ) {
        } catch ( Twig_Error_Runtime $e ) {
        } catch ( Twig_Error_Syntax $e ) {
          return  $e->getRawMessage();
        }
      } else {
        $func = "vc_search_" . $type . "_tpls";
        //return $this->$func( $data );
        return call_user_func_array( array( $this, $func ), array( $data ) );
      }
    }

    /**
     * Compter les nombre de secteur d'activité dans le resultat de recherche
     *
     * @param $posts
     *
     * @return int
     */
    private function activity_results( $posts ) {
      $results = 0;
      if ( empty( $posts ) ) {
        return $results;
      }
      $post_type       = get_post_type( $posts[0] );
      $branch_activity = [];
      if ( $post_type === 'offers' ) {
        foreach ( $posts as $post ) {
          // Return object term
          $term              = get_field( 'itjob_offer_abranch', $post->ID );
          if ( !empty($term) || !is_null($term) || $term) {
            $branch_activity[] = $term->term_id;
          }
        }
        $branch_activity = array_unique( $branch_activity );

        return count( $branch_activity );
      }
      if ( $post_type === 'candidate' ) {
        foreach ( $posts as $post ) {
          $ab = wp_get_post_terms( $post->ID, 'branch_activity', [ "fields" => "ids" ] );
          if ( ! empty( $ab ) && is_array( $ab ) ) {
            $branch_activity[] = $ab[0];
          }
        }
        $branch_activity = array_unique( $branch_activity );

        return count( $branch_activity );
      }

      return $results;
    }

    private function vc_search_offers_tpls( $args ) {
      global $Engine;
      try {
        global $posts;
        $activity_count = $this->activity_results( $posts );
        $search_query   = Http\Request::getValue( 's' );
        $args           = array_merge( $args, [
          's'              => $search_query,
          'search_count'   => count( $posts ),
          'activity_count' => $activity_count
        ] );

        return $Engine->render( '@VC/search/search-offers.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }

    private function vc_search_candidate_tpls( $args ) {
      global $Engine, $posts;
      try {
        $langage   = get_terms( 'language', [
          'hide_empty' => false,
          'fields'     => 'all'
        ] );
        $softwares = get_terms( 'software', [
          'hide_empty' => false,
          'fields'     => 'all'
        ] );
        $sub_data  = [
          'languages' => $langage,
          'softwares' => $softwares
        ];

        $data = array_merge( $args, $sub_data );

        $activity_count = $this->activity_results( $posts );
        $search_query   = Http\Request::getValue( 's' );
        $data           = array_merge( $data, [
          's'              => $search_query,
          'search_count'   => count( $posts ),
          'activity_count' => $activity_count
        ] );

        return $Engine->render( '@VC/search/search-cv.html.twig', $data );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }

    private function vc_search_formation_tpls( $args ) {
      global $Engine;
      try {
        global $posts;
        wp_enqueue_script( 'jquery-validate' );
        wp_enqueue_script( 'sweetalert' );
        wp_enqueue_style( 'sweetalert' );
        wp_enqueue_script( 'tinymce', "https://cloud.tinymce.com/stable/tinymce.min.js", [], null, true );
        
        $search_query = Http\Request::getValue( 's' );
        $publish_formation_link = get_the_permalink( ADD_FORMATION_PAGE );
        $args = array_merge( $args, [
          's'              => $search_query,
          'publish_formation_link' => $publish_formation_link,
          'admin_ajax'      => admin_url('admin-ajax.php')
        ] );

        return $Engine->render( '@VC/search/search-formation.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }

    private function vc_search_annonce_tpls ($args) {
      unset($args['abranchs']);
      $categories = get_terms( 'categorie', [
        'hide_empty' => false,
        'fields'     => 'all'
      ] );
      $args['categories'] = $categories;
      return $this->vc_search_works_tpls($args);
    }
    private function vc_search_works_tpls ($args) {
      global $Engine;
      try {
        global $posts;
        $search_query   = Http\Request::getValue( 's' );
        $publish_ad_link = get_the_permalink( ADD_ANNONCE_PAGE );
        $type = $args['post_type'] === 'annonce' ? 2 : 1;
        $btn_msg = $type === 1 ? "Deposer un travail temporaire" : "Deposer une petite annonce";
        $args           = array_merge( $args, [
          's'              => $search_query,
          'ab'             => Http\Request::getValue('ab', ''),
          'rg'             => Http\Request::getValue('rg', ''),
          'ctg'             => Http\Request::getValue('ctg', ''),
          'search_count'   => count( $posts ),
          'publish_ad_link' => $publish_ad_link . '?type=' . $type,
          'BTN_MSG' => $btn_msg
        ] );
        return $Engine->render( '@VC/search/search-annonce.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }
  }
endif;

class WPBakeryShortCode_Vc_itjob_search extends \WPBakeryShortCodesContainer {
}

return new vcSearch();