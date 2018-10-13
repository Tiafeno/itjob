<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 13/10/2018
 * Time: 16:17
 */

namespace includes\vc;


if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

use Http;
use includes\post\Candidate;
use includes\post\Offers;

if ( ! class_exists('vcSlider')):
  final class vcSlider {
    public function __construct() {
      add_action('init', [&$this, 'slider_mapping'], 10, 0);
      add_shortcode('cv_slider_post', [&$this, 'slider_html']);
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
          'category'    => 'itJob'
        )
      );
    }

    public function slider_html( $attrs ) {
      global $Engine, $itJob;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => null,
            'post_type' => 'offers'
          ),
          $attrs
        )
        , EXTR_OVERWRITE );
      // Recuperer dans le service les offres publier et à la une
      $offers = $itJob->services->getFeaturedPost('offers', [
        'key' => 'itjob_offer_featured',
        'value' => 1,
        'compare' => '='
      ]);
      try {
        /** @var STRING $title - Titre de l'element VC */
        /** @var STRING $post_type */
        return $Engine->render( "@VC/slider/{$post_type}.html.twig", [
          'title' => $title,
          'offers' => $offers
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }


  }
endif;

return new vcSlider();