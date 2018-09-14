<?php
namespace includes\vc;

use Http;
use includes\post\Candidate;
use includes\post\UserParticular;

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

if ( ! class_exists('vcCandidate')):
  class vcCandidate extends \WPBakeryShortCode {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_candidate_mapping' ] );
      add_shortcode( 'vc_featured_candidate', [ $this, 'vc_featured_candidate_render' ] );
    }

    public function vc_candidate_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      // Map the block with vc_map()

      // Les offres à la une
      \vc_map(
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
    }

    public function vc_featured_candidate_render( $attrs ) {
      global $Engine;
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
      $args = [
        'title'  => $title,
        //'candidates' => self::get_featured_cv()
      ];
      wp_enqueue_style('candidate', get_template_directory_uri().'/assets/css/candidate.css', ['adminca']);

      try {
        return $Engine->render( '@VC/candidates/sidebar-top.html.twig', $args );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }

  }
endif;

return new vcCandidate();