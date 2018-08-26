<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  die( 'WPBakery plugins missing!' );
}
if ( ! class_exists( 'vcRegisterCompany' ) ) :
  class vcRegisterCompany extends WPBakeryShortCode {
    public function __construct() {
      add_action( 'init', [ $this, 'register_mapping' ] );

      add_shortcode( 'vc_register', [ $this, 'register_render_html' ] );
    }

    private function get_branch_activity() {
      return get_terms( 'branch_activity', [
        'hide_empty' => false,
        'fields'     => 'all'
      ] );
    }

    public function register_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      vc_map(
        array(
          'name'        => 'Formulaire d\'enregistrement',
          'base'        => 'vc_register',
          'description' => 'Entreprise/CV Formulaire',
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
            ),
            array(
              'type'        => 'dropdown',
              'class'       => 'vc-ij-position',
              'heading'     => 'Type de formulaire',
              'param_name'  => 'form',
              'value'       => array(
                'Entreprise'     => 'company',
                'Candidate (CV)' => 'candidate'
              ),
              'std'         => 'company',
              'description' => "Un formulaire que vous souhaiter utiliser",
              'admin_label' => true,
              'weight'      => 0
            ),
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
            'form'  => null // Post type value
          ),
          $attrs
        )
        , EXTR_OVERWRITE );
      /** @var string $form - candidate/company */
      if ( $form === 'company' || is_null( $form ) ) {

        // load script & style
        wp_enqueue_script( 'form-company', get_template_directory_uri() . '/assets/js/app/register/form.company.js',
          [ 'angular' ], $itJob->version, true );

        try {
          /** @var STRING $title - Titre de l'element VC */
          return $Engine->render( '@VC/register/company.html.twig', [
            'title'    => $title,
            'abranchs' => $this->get_branch_activity()
          ] );
        } catch ( Twig_Error_Loader $e ) {
        } catch ( Twig_Error_Runtime $e ) {
        } catch ( Twig_Error_Syntax $e ) {
          return $e->getRawMessage();
        }
      } else {
        // load script & style
        wp_enqueue_script( 'form-candidate', get_template_directory_uri() . '/assets/js/app/register/form.candidate.js',
          [ 'angular' ], $itJob->version, true );

        try {
          /** @var STRING $title - Titre de l'element VC */
          return $Engine->render( '@VC/register/candidate.html.twig', [
            'title' => $title
          ] );
        } catch ( Twig_Error_Loader $e ) {
        } catch ( Twig_Error_Runtime $e ) {
        } catch ( Twig_Error_Syntax $e ) {
          return $e->getRawMessage();
        }
      }
    }
  }
endif;

return new vcRegisterCompany();