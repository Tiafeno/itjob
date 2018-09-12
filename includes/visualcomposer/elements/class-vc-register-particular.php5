<?php
namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery','WPBakery plugins missing!' );
}
if ( ! class_exists( 'vcRegisterParticular' ) ) :
  class vcRegisterParticular extends \WPBakeryShortCode {
    public function __construct() {
      add_action('init', [&$this, 'register_particular_mapping']);
      add_shortcode('vc_register_particular', [&$this, 'register_render_html']);
    }

    public function register_particular_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      \vc_map(
        array(
          'name'        => 'Particular Form (SingUp)',
          'base'        => 'vc_register_particular',
          'description' => 'Formulaire d\'enregistrement d\'un utilisateur particulier',
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

    public function register_render_html($attrs) {
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

      if (is_user_logged_in()) return 'Access refuser';
      wp_enqueue_style('b-datepicker-3');
      wp_enqueue_script('form-particular', get_template_directory_uri().'/assets/js/app/register/form-particular.js', [
        'angular',
        'angular-ui-route',
        'angular-sanitize',
        'angular-messages',
        'b-datepicker'
      ], $itJob->version, true);
      wp_localize_script( 'form-particular', 'itOptions', [
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'partials_url' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template_url' => get_template_directory_uri()
      ] );
      try {
        /** @var STRING $title */
        return $Engine->render( '@VC/register/particular.html.twig', [
          'title' => $title
        ] );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }
  }
endif;

return new vcRegisterParticular();