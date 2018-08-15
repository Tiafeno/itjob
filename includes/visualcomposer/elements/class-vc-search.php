<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 13/08/2018
 * Time: 11:05
 */
if ( ! class_exists('WPBakeryShortCode')) die('WPBakery plugins missing!');
if ( ! class_exists('vcSearch')):
  class vcSearch {
    public function __construct() {
      add_action('init', [$this, 'vc_search_mapping']);
      add_shortcode('vc_itjob_search', [$this, 'vc_search_template']);
    }

    public function vc_search_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      // Map the block with vc_map()
      vc_map(
        array(
          'name'        => __( 'VC Search Element', __SITENAME__ ),
          'base'        => 'vc_itjob_search',
          'description' => 'Effectuer une recherche sur l\'emplois ou sur les candidats',
          'category'    => __( 'itJob', __SITENAME__ )
        )
      );
    }

    public function vc_search_template($atts) {
      global $Engine;
      try {
        return $Engine->render( '@VC/search.html.twig', [] );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }
  }
endif;

return new vcSearch();