<?php

namespace includes\vc;
/**
 * Class vcOffers
 * @method vc_our_offer - Récuperer les offres à la une
 * @method vc_offer_recently - Récuperer les offres recements ajouter
 */
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  die( 'WPBakery plugins missing!' );
}
if ( ! class_exists( 'vcOffers' ) ):
  class vcOffers extends \WPBakeryShortCode {
    public function __construct() {
      global $itJob;
      add_action( 'init', [ $this, 'vc_offers_mapping' ] );

      add_shortcode( 'vc_offers', [ $this, 'vc_offers_render' ] );
      add_shortcode( 'vc_featured_offers', [ $this, 'vc_featured_offers_render' ] );
      // Shortcode pour ajouter un offre
      add_shortcode( 'vc_added_offer', [ &$this, 'vc_added_offer_render' ] );

      add_action( 'wp_enqueue_scripts', function () {

      } );
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
        return $Engine->render( '@VC/offers/offers.html.twig', [
          'title' => $title,
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
        'offers' => self::get_featured_offers()
      ];

      return ( trim( $position ) === 'sidebar' ) ? $this->getPositionSidebar( $args ) : $this->getPositionContent( $args );
    }

    public function vc_added_offer_render( $attrs ) {
      if ( is_user_logged_in() ) {
        $logoutUrl          = wp_logout_url( home_url( '/' ) );
        $user               = wp_get_current_user();
        $espace_client_link = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : '#no-link';
        $output             = 'Vous êtes déjà connecté avec ce compte: <b>' . $user->display_name . '</b><br>';
        $output             .= '<a class="btn btn-outline-primary btn-fix btn-thick mt-4" href="' . $espace_client_link . '">Espace client</a>';
        $output             .= '<a class="btn btn-outline-primary btn-fix btn-thick mt-4 ml-2" href="' . $logoutUrl . '">Déconnecter</a>';

        return $output;
      }
      global $Engine, $itJob;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => null
          ),
          $attrs
        )
        , EXTR_OVERWRITE );
      try {

        wp_enqueue_script( 'offers', get_template_directory_uri() . '/assets/js/app/offers/form.js',
          [
            'angular',
            'angular-route',
            'angular-sanitize',
            'angular-messages',
            'angular-animate',
            'angular-aria',
          ], $itJob->version, true );
        wp_localize_script( 'offers', 'itOptions', [
          'ajax_url'     => admin_url( 'admin-ajax.php' ),
          'partials_url' => get_template_directory_uri() . '/assets/js/app/offers/partials',
          'template_url' => get_template_directory_uri()
        ] );

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

    /**
     * Récuperer les offres à la une
     * @return array
     */
    public static function get_featured_offers() {
      $featuredOffers = [];
      $args           = [
        'post_type'      => 'offers',
        'posts_per_page' => 4,
        'orderby'        => 'DATE',
        'meta_query'     => [
          [
            'key'     => 'itjob_offer_featured',
            'compare' => '=',
            'value'   => '1'
          ]
        ]
      ];
      $offers         = get_posts( $args );
      foreach ( $offers as $offer ) {
        setup_postdata( $offer );
        array_push( $featuredOffers, new Offers( $offer->ID ) );
      }
      wp_reset_postdata();

      return $featuredOffers;
    }

    public static function vc_offer_recently() {

    }
  }
endif;

return new vcOffers();