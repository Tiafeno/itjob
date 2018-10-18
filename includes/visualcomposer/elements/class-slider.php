<?php

namespace includes\vc;


if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

if ( ! class_exists( 'vcSlider' ) ):
  final class vcSlider {
    public function __construct() {
      add_action( 'init', [ &$this, 'slider_mapping' ], 10, 0 );
      add_shortcode( 'cv_slider_post', [ &$this, 'slider_html' ] );
    }

    public function slider_mapping() {

      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      \vc_map(
        array(
          'name'        => 'Featured Post Slider',
          'base'        => 'cv_slider_post',
          'description' => 'Affiche les offres ou autres post en slide',
          'category'    => 'itJob',
          'params'      => array(
            array(
              'type'        => 'dropdown',
              'class'       => 'vc-ij-post-type',
              'heading'     => 'Post type',
              'param_name'  => 'post_type',
              'value'       => [
                'Les candidates' => 'candidate',
                'Les offres'     => 'offers'
              ],
              'std'         => 'offers',
              'description' => "Type de post à afficher dans le blog",
              'admin_label' => true,
              'weight'      => 0
            ),
          )
        )
      );
    }

    public function slider_html( $attrs ) {
      global $Engine, $itJob;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title'     => null,
            'post_type' => 'offers'
          ),
          $attrs
        )
        , EXTR_OVERWRITE );
      /** @var STRING $post_type */
      $post_type = empty($post_type) ? 'offers' : $post_type;
      $args = [];
      // Recuperer dans le service les offres publier et à la une
      switch ($post_type) {
        case 'offers':
          $offers = $itJob->services->getFeaturedPost( 'offers', [
            'key'     => 'itjob_offer_featured',
            'value'   => 1,
            'compare' => '='
          ] );
          /** @var STRING $title - Titre de l'element VC */
          $args = array_merge($args, [
            'title'  => $title,
            'offers' => $offers
          ]);

          break;
      }

      try {

        return $Engine->render( "@VC/slider/{$post_type}.html.twig", $args );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }


  }
endif;

return new vcSlider();