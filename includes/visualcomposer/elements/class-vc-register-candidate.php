<?php

namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

if ( ! class_exists('vcRegisterCandidate')) :
  class vcRegisterCandidate extends \WPBakeryShortCode {
    public function __construct() {
      add_action('init', [&$this, 'register_candidate_mapping']);
      if ( ! shortcode_exists('vc_register_candidate') ) {
        add_shortcode('vc_register_candidate', [&$this, 'register_render_html']);
      }
    }

    public function register_candidate_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      \vc_map(
        array(
          'name'        => 'Candidate Form (SingUp)',
          'base'        => 'vc_register_candidate',
          'description' => 'Formulaire d\'activation d\'un candidate',
          'category'    => 'itJob',
          'params'      => array(
            array(
              'type'        => 'textfield',
              'holder'      => 'h3',
              'class'       => 'vc-ij-title',
              'heading'     => 'Titre',
              'param_name'  => 'title',
              'value'       => '',
              'description' => "Une titre pour le formulaire",
              'admin_label' => true,
              'weight'      => 0
            )
          )
        )
      );
    }

    public function register_render_html( $attrs ) {
      global $Engine, $itJob;
      // Params extraction
      extract(
        shortcode_atts(
          array(
            'title' => null,
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      // Ne pas autoriser un client non connecté
      if ( ! is_user_logged_in() ) {
        return '<div class="d-flex align-items-center">'.
               '<div class="uk-margin-large-top uk-margin-auto-left uk-margin-auto-right text-uppercase">Access refuser</div></div>';
      }

      wp_enqueue_style( 'b-datepicker-3' );
      wp_enqueue_style( 'sweetalert' );
      wp_enqueue_style( 'ng-tags-bootstrap' );
      wp_enqueue_script( 'form-candidate', get_template_directory_uri() . '/assets/js/app/register/form-candidate.js', [
        'angular',
        'angular-ui-route',
        'angular-sanitize',
        'angular-messages',
        'angular-animate',
        'b-datepicker',
        'daterangepicker',
        'sweetalert',
        'ng-tags',
        'typeahead'
      ], $itJob->version, true );
      wp_localize_script( 'form-candidate', 'itOptions', [
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'partials_url' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template_url' => get_template_directory_uri()
      ] );

      try {
        /** @var STRING $title */
        return $Engine->render( '@VC/register/candidate.html.twig', [
          'title' => $title
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }
  }
endif;

return new vcRegisterCandidate();