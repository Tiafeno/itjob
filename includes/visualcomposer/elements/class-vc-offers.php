<?php

namespace includes\vc;

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

use Http;
use includes\post\Company;
use includes\post\Offers;

if ( ! class_exists( 'vcOffers' ) ):
  class vcOffers extends \WPBakeryShortCode {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_offers_mapping' ] );
      add_action( 'acf/update_value/name=itjob_offer_abranch', [ &$this, 'update_offer_reference' ], 10, 3 );

      add_shortcode( 'vc_offers', [ $this, 'vc_offers_render' ] );
      add_shortcode( 'vc_featured_offers', [ $this, 'vc_featured_offers_render' ] );
      // Shortcode pour ajouter un offre
      add_shortcode( 'vc_added_offer', [ &$this, 'vc_added_offer_render' ] );

      add_action( 'wp_ajax_ajx_insert_offers', [ &$this, 'ajx_insert_offers' ] );
      add_action( 'wp_ajax_nopriv_ajx_insert_offers', [ &$this, 'ajx_insert_offers' ] );

    }

    public function vc_offers_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      // Map the block with vc_map()

      // Les offres à la une
      \vc_map(
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
              'class'       => 'vc-ij-position',
              'heading'     => 'Position',
              'param_name'  => 'position',
              'value'       => array(
                'Sur le côté'  => 'sidebar',
                'Sur le large' => 'content'
              ),
              'std'         => 'content',
              'description' => "Modifier le mode d'affichage",
              'admin_label' => false,
              'weight'      => 0
            ),
          )
        )
      );

      // Les offres
      \vc_map(
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

      // Formulaire d'ajout d'offre
      \vc_map(
        array(
          'name'        => 'Ajouter une offre',
          'base'        => 'vc_added_offer',
          'description' => 'Offre d\'une entreprise',
          'category'    => 'itJob',
          'params'      => array(
            array(
              'type'        => 'textfield',
              'holder'      => 'h3',
              'class'       => 'vc-ij-title',
              'heading'     => 'Titre',
              'param_name'  => 'title',
              'value'       => 'Ajouter une offre',
              'description' => "Une titre pour le formulaire",
              'admin_label' => true,
              'weight'      => 0
            )
          )
        )
      );
    }

    public function ajx_insert_offers() {
      /**
       * @func wp_doing_ajax
       * (bool) True if it's a WordPress Ajax request, false otherwise.
       */
      if ( ! \wp_doing_ajax() || ! \is_user_logged_in() ) {
        return;
      }

      $User    = wp_get_current_user();
      $Company = Company::get_company_by( 'user_id', $User->ID );
      if ( ! $Company->is_company() ) {
        wp_send_json( [ 'success' => false, 'msg' => 'Utilisateur n\'est pas une entreprise', 'user' => $Company ] );
      }

      $form = (object) [
        'post'            => Http\Request::getValue( 'post' ),
//        'reference'       => Http\Request::getValue( 'reference' ),
        'ctt'             => Http\Request::getValue( 'ctt' ),
        'salary_proposed' => Http\Request::getValue( 'salary_proposed', 0 ),
        'region'          => Http\Request::getValue( 'region' ),
        'branch_activity' => Http\Request::getValue( 'ba' ),
        'datelimit'       => Http\Request::getValue( 'datelimit' ),
        'mission'         => Http\Request::getValue( 'mission' ),
        'profil'          => Http\Request::getValue( 'profil' ),
        'other'           => Http\Request::getValue( 'other' ),
        'company_id'      => $Company->ID
      ];
      // Ajouter l'offre dans la base de donnée
      $result = wp_insert_post( [
        'post_title'   => $form->post,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_author'  => $User->ID,
        'post_type'    => 'offers'
      ] );
      if ( is_wp_error( $result ) ) {
        wp_send_json( [ 'success' => false, 'msg' => $result->get_error_message() ] );
      }

      // update acf field
      $post_id = &$result;
      $this->update_acf_field( $post_id, $form );
      wp_set_post_terms( $post_id, [ (int) $form->region ], 'region' );
      wp_send_json( [ 'success' => true, 'msg' => new Offers( $post_id ), 'form' => $form ] );
    }

    private function update_acf_field( $post_id, $form ) {
      update_field( 'itjob_offer_post', $form->post, $post_id );

      // FEATURE: La référence est automatiquement gérer par le systeme
      //update_field( 'itjob_offer_reference', $form->reference, $post_id );

      update_field( 'itjob_offer_datelimit', $form->datelimit, $post_id );
      update_field( 'itjob_offer_contrattype', $form->ctt, $post_id );
      update_field( 'itjob_offer_profil', $form->profil, $post_id );
      update_field( 'itjob_offer_mission', $form->mission, $post_id );
      update_field( 'itjob_offer_proposedsallary', $form->salary_proposed, $post_id );
      update_field( 'itjob_offer_otherinformation', $form->other, $post_id );
      update_field( 'itjob_offer_abranch', $form->branch_activity, $post_id );
      update_field( 'itjob_offer_featured', 0, $post_id );

      update_field( 'itjob_offer_company', $form->company_id, $post_id );
    }

    // This is "itjob_offer_abranch" field
    // Cette fonction permet de mettre à jour la reference par rapport à son secteur d'activité
    // Callback: acf/update_value/name=itjob_offer_abranch
    public function update_offer_reference( $value, $post_id, $field ) {
      $taxonomy        = "branch_activity";
      $term_abranch_id = (int) $value;
      if ( term_exists( $term_abranch_id, $taxonomy ) ) {
        $branch_activity_obj = get_term( $term_abranch_id, $taxonomy );
        update_field( 'itjob_offer_reference', strtoupper( $branch_activity_obj->slug ) . $post_id );
      }

      return $value;
    }

    /**
     * Afficher les offres recement ajouter
     *
     * @param array $attrs
     *
     * @return mixed
     */
    public function vc_offers_render( $attrs ) {
      global $Engine, $itJob;

      // load script or style
      wp_enqueue_style( 'offers' );

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

      try {
        /** @var STRING $title - Titre de l'element VC */
        /** @var STRING $orderby */
        /** @var STRING $order */
        return $Engine->render( '@VC/offers/offers.html.twig', [
          'title'  => $title,
          'offers' => $itJob->services->getRecentlyPost('offers', 4),
          'archive_offer_url' => get_post_type_archive_link('offers')
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

    /**
     * Affiche les offres à la une par position
     *
     * @param array $attrs
     *
     * @return mixed
     */
    public function vc_featured_offers_render( $attrs ) {
      global $itJob;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title'    => 'Offres à la une',
            'position' => ''
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      /** @var string $position */
      /** @var string $title */
      $args = [
        'title'  => $title,
        'offers' => $itJob->services->getFeaturedPost('offers', 'itjob_offer_featured')
      ];

      return ( trim( $position ) === 'sidebar' ) ? $this->getPositionSidebar( $args ) : $this->getPositionContent( $args );
    }

    /**
     * Shortcode - Crée une formulaire d'ajout d'offre
     *
     * @param  array $attrs
     *
     * @return bool|string
     */
    public function vc_added_offer_render( $attrs ) {
      global $Engine, $itJob;
      if ( ! is_user_logged_in() ) {
        // FEATURE: Proposer l'utilisateur à s'inscrire en tands que sociéte pour ajouter une offre
        return vcRegisterCompany::getInstance()->register_render_html(['title' => 'FORMULAIRE ENTREPRISE']);
        /*return $Engine->render( '@ERROR/403.html.twig', [
          'template_url' => get_template_directory_uri()
        ] );*/
      }

      // TODO: Verifier si l'utilicateur est une entreprise
      // Réfuser l'access s'il n'est pas une entreprise
      if ( ! itjob_current_user_is_company()) {
        return false;
      }


      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => 'Ajouter une offre'
          ),
          $attrs
        )
        , EXTR_OVERWRITE );
      try {
        if ( ! defined('VENDOR_URL'))
          define( 'VENDOR_URL', get_template_directory_uri() . '/assets/vendors' );
        wp_enqueue_style( 'b-datepicker-3');
        wp_enqueue_style( 'themify-icons' );
        wp_enqueue_style( 'froala' );
        wp_enqueue_style( 'froala-gray', VENDOR_URL . '/froala-editor/css/themes/gray.min.css', '', '2.8.4' );
        wp_enqueue_script( 'b-datepicker');
        wp_enqueue_script( 'offers', get_template_directory_uri() . '/assets/js/app/offers/form.js',
          [
            'angular',
            'angular-ui-route',
            'angular-sanitize',
            'angular-messages',
            'angular-animate',
            'angular-aria',
            'froala',
          ], $itJob->version, true );

        wp_localize_script( 'offers', 'itOptions', [
          'ajax_url'     => admin_url( 'admin-ajax.php' ),
          'partials_url' => get_template_directory_uri() . '/assets/js/app/offers/partials',
          'template_url' => get_template_directory_uri()
        ] );

        do_action('get_notice');

        /** @var STRING $title - Titre de l'element VC */
        return $Engine->render( '@VC/offers/form-offer.html.twig', [
          'title'        => $title,
          'template_url' => get_template_directory_uri()
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }

    }

    /**
     * Position sidebar
     *
     * @param string $title
     *
     * @return mixed
     */
    public function getPositionSidebar( $args ) {
      global $Engine;
      try {
        return $Engine->render( '@VC/offers/sidebar.html.twig', $args );
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
    public function getPositionContent( $args ) {
      global $Engine;
      try {
        return $Engine->render( '@VC/offers/content.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

  }
endif;

class WPBakeryShortCode_Vc_featured_offers extends \WPBakeryShortCodesContainer {
}

return new vcOffers();