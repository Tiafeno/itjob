<?php

namespace includes\vc;

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

if ( ! class_exists( 'vcCandidate' ) ):
  class vcCandidate extends \WPBakeryShortCode {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_candidate_mapping' ] );

      if ( ! shortcode_exists( 'vc_featured_candidate' ) ) {
        add_shortcode( 'vc_featured_candidate', [ $this, 'vc_featured_candidate_render' ] );
      }

      if ( ! shortcode_exists( 'vc_candidate_recently_added' ) ) {
        add_shortcode( 'vc_candidate_recently_added', [ $this, 'vc_candidate_recently_added_render' ] );
      }
    }

    public function vc_candidate_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }

      // Les candidates à la une
      vc_map(
        array(
          'name'        => 'CV à la une',
          'base'        => 'vc_featured_candidate',
          'description' => 'Afficher les candidates à la une.',
          'category'    => 'itJob',
          'params'      => array(
            array(
              'type'        => 'textfield',
              'holder'      => 'h3',
              'class'       => 'vc-ij-title',
              'heading'     => 'Titre',
              'param_name'  => 'title',
              'value'       => '',
              'description' => "Une titre pour les candidates à la une",
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

      // Les candidats recemment ajouter
      vc_map( [
        'name'     => 'Liste des CV',
        'base'     => 'vc_candidate_recently_added',
        'category' => 'itJob',
        'params'   => [
          array(
            'type'        => 'textfield',
            'holder'      => 'h3',
            'class'       => 'vc-ij-title',
            'heading'     => 'Titre',
            'param_name'  => 'title',
            'value'       => '',
            'description' => "Ajouter un titre",
            'admin_label' => false,
            'weight'      => 0
          ),
        ]
      ] );
    }

    /**
     * Afficher les CV à la une ou premium.
     * 
     * @shortcode [vc_featured_candidate [title=<string> position=<string>] ]
     * @attr title - Contient le titre de l'element
     * @attr position - Cette attribut definie le style de l'affichage
     */
    public function vc_featured_candidate_render( $attrs ) {
      global $Engine, $itJob;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title'    => '',
            'position' => 'content'
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      /** @var string $position */
      /** @var string $title */
      $position   = empty($position) ? 'content' : $position;
      $candidates = $itJob->services->getFeaturedPost( 'candidate', [
        'key'   => 'itjob_cv_featured',
        'value' => 1
      ] );
      $args       = [
        'title'      => $title,
        'candidates' => $candidates
      ];
      wp_enqueue_style( 'candidate' );
      try {
        return $Engine->render( "@VC/candidates/{$position}-featured.html.twig", $args );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

    /**
     * Afficher les CV récemment ajouter
     * 
     * @shortcode [vc_candidate_recently_added [title=<string>]]
     * @attr title - Contient le titre de l'element
     */
    public function vc_candidate_recently_added_render( $attrs ) {
      global $Engine, $itJob;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => ''
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      /** @var string $title */
      $args = [
        'title'      => $title,
        'candidates' => $itJob->services->getRecentlyPost( 'candidate', 4),
        'archive_cv_url' => get_post_type_archive_link('candidate')
      ];
      try {
        return $Engine->render( '@VC/candidates/lists.html.twig', $args );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

  }
endif;

return new vcCandidate();