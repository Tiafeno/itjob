<?php

namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  die( 'WPBakery plugins missing!' );
}

use Http;
use includes\post\Company;

if ( ! class_exists( 'vcRegisterCompany' ) ) :
  class vcRegisterCompany extends \WPBakeryShortCode {
    public function __construct() {
      add_action( 'init', [ $this, 'register_mapping' ] );
      add_action( 'acf/update_value/name=itjob_company_email', [ &$this, 'post_publish_company' ], 10, 3 );

      // Ajouter le mot de passe de l'utilisateur
      add_action( 'user_register', function ( $user_id ) {
        $user       = get_userdata( $user_id );
        $user_roles = $user->roles;
        if ( ! in_array( 'company', $user_roles, true ) ) {
          return false;
        }
        $pwd = $_POST['pwd'];
        if ( isset( $pwd ) ) {
          $id = wp_update_user( [ 'ID' => $user_id, 'user_pass' => trim( $pwd ) ] );
          if ( is_wp_error( $user_id ) ) {
            return true;
          } else {
            // Mot de passe utilisateur à etes modifier avec success
            return false;
          }
        }
      }, 10, 1 );

      add_shortcode( 'vc_register', [ &$this, 'register_render_html' ] );

      add_action( 'wp_ajax_ajx_insert_company', [ &$this, 'ajx_insert_company' ] );
      add_action( 'wp_ajax_nopriv_ajx_insert_company', [ &$this, 'ajx_insert_company' ] );

      add_action( 'wp_ajax_ajx_get_branch_activity', [ &$this, 'ajx_get_branch_activity' ] );
      add_action( 'wp_ajax_nopriv_ajx_get_branch_activity', [ &$this, 'ajx_get_branch_activity' ] );

      add_action( 'wp_ajax_ajx_get_taxonomy', [ &$this, 'ajx_get_taxonomy' ] );
      add_action( 'wp_ajax_nopriv_ajx_get_taxonomy', [ &$this, 'ajx_get_taxonomy' ] );

      add_action( 'wp_ajax_ajx_user_exist', [ &$this, 'ajx_user_exist' ] );
      add_action( 'wp_ajax_nopriv_ajx_user_exist', [ &$this, 'ajx_user_exist' ] );
    }

    /**
     * This hook allows you to modify the value of a field before it is saved to the database.
     *
     * @param $value – the value of the field as found in the $_POST object
     * @param $post_id - the post id to save against
     * @param $field – the field object (actually an array, not object)
     *
     * @return bool|string
     */
    public function post_publish_company( $value, $post_id, $field ) {
      $chars     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      $post_type = get_post_type( $post_id );
      if ( $post_type != 'company' ) {
        return $value;
      }

      $post      = get_post( $post_id );
      $userEmail = &$value;
      // (WP_User|false) WP_User object on success, false on failure.
      $userExist = get_user_by( 'email', $userEmail );
      if ( true == $userExist ) {
        return $value;
      }
      $args    = [
        "user_pass"    => substr( str_shuffle( $chars ), 0, 8 ),
        "user_login"   => 'user' . $post_id,
        "user_email"   => $userEmail,
        "display_name" => $post->post_title,
        "first_name"   => $post->post_title,
        "role"         => $post_type
      ];
      $user_id = wp_insert_user( $args );
      if ( ! is_wp_error( $user_id ) ) {
        $user = new \WP_User( $user_id );
        get_password_reset_key( $user );

        return $value;
      } else {
        return $value;
      }
    }


    public function register_mapping() {
      // Stop all if VC is not enabled
      if ( ! defined( 'WPB_VC_VERSION' ) ) {
        return;
      }
      \vc_map(
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

    /**
     * Vérifier si l'utilisateur existe déja
     */
    public function ajx_user_exist() {
      if ( ! \wp_doing_ajax() ) {
        return;
      }
      if ( is_user_logged_in() ) {
        return;
      }
      $log = Http\Request::getValue( 'log', false );
      if ( filter_var( $log, FILTER_VALIDATE_EMAIL ) ) {
        $usr = get_user_by( 'email', $log );
      } else {
        $usr = get_user_by( 'login', $log );
      }
      wp_send_json( $usr );
    }

    // AJAX

    public function ajx_get_branch_activity() {
      /**
       * @func wp_doing_ajax
       * (bool) True if it's a WordPress Ajax request, false otherwise.
       */
      if ( ! \wp_doing_ajax() ) {
        return;
      }
      $terms = get_terms( 'branch_activity', [
        'hide_empty' => false,
        'fields'     => 'all'
      ] );
      wp_send_json( $terms );
    }

    public function ajx_get_taxonomy() {
      /**
       * @func wp_doing_ajax
       * (bool) True if it's a WordPress Ajax request, false otherwise.
       */
      if ( ! \wp_doing_ajax() || empty( $_GET ) ) {
        return false;
      }

      $taxonomy = Http\Request::getValue( 'tax', false );
      if ( $taxonomy ) {
        $terms = get_terms( $taxonomy, [
          'hide_empty' => false,
          'fields'     => 'all'
        ] );
        wp_send_json( $terms );
      } else {
        return false;
      }

    }

    public function ajx_insert_company() {
      /**
       * @func wp_doing_ajax
       * (bool) True if it's a WordPress Ajax request, false otherwise.
       */
      if ( ! \wp_doing_ajax() ) {
        return;
      }

      $userEmail = Http\Request::getValue( 'email', false );
      $userExist = get_user_by( 'email', $userEmail );
      if ( true == $userExist ) {
        wp_send_json( [ 'success' => false, 'msg' => 'L\'adresse e-mail ou l\'utilisateur existe déja' ] );
      }

      $form = (object) [
        'greeting'           => Http\Request::getValue( 'greeting' ),
        'title'              => Http\Request::getValue( 'title' ),
        'address'            => Http\Request::getValue( 'address' ),
        'nif'                => Http\Request::getValue( 'nif' ),
        'stat'               => Http\Request::getValue( 'stat' ),
        'name'               => Http\Request::getValue( 'name' ),
        'email'              => Http\Request::getValue( 'email' ),
        'phone'              => Http\Request::getValue( 'phone' ),
        'cellphone'          => Http\Request::getValue( 'cellphone' ),
        'branch_activity_id' => Http\Request::getValue( 'abranchID' ),
        'notification'       => Http\Request::getValue( 'notification' ),
        'newsletter'         => Http\Request::getValue( 'newsletter' )
      ];

      $result = wp_insert_post( [
        'post_title'   => $form->title,
        'post_content' => '',
        'post_status'  => 'pending',
        'post_author'  => 1,
        'post_type'    => 'company'
      ] );
      if ( is_wp_error( $result ) ) {
        wp_send_json( [ 'success' => false, 'msg' => $result->get_error_message() ] );
      }

      // update acf field
      $post_id = &$result;
      $this->update_acf_field( $post_id, $form );
      wp_set_post_terms( $post_id, [ (int) $form->branch_activity_id ], 'branch_activity' );
      wp_send_json( [ 'success' => true, 'msg' => new Company( $post_id ), 'form' => $form ] );
    }

    /**
     * @param int $post_id
     * @param \stdClass $form
     */
    private function update_acf_field( $post_id, $form ) {
      update_field( 'itjob_company_address', $form->address, $post_id );
      update_field( 'itjob_company_nif', $form->nif, $post_id );
      update_field( 'itjob_company_stat', $form->stat, $post_id );
      update_field( 'itjob_company_greeting', $form->greeting, $post_id );
      update_field( 'itjob_company_name', $form->name, $post_id );
      update_field( 'itjob_company_newsletter', (int) $form->newsletter, $post_id );
      update_field( 'itjob_company_notification', (int) $form->notification, $post_id );
      update_field( 'itjob_company_email', $form->email, $post_id );
      update_field( 'itjob_company_phone', $form->phone, $post_id );

      // save repeater field
      $value  = [];
      $phones = json_decode( $form->cellphone );
      foreach ( $phones as $row => $phone ) {
        $value[] = [ 'number' => $phone->value ];
      }
      update_field( 'itjob_company_cellphone', $value, $post_id );

      return true;
    }

    public function register_render_html( $attrs ) {
      global $Engine, $itJob;

      if ( is_user_logged_in() ) {
        $logoutUrl          = wp_logout_url( home_url( '/' ) );
        $user               = wp_get_current_user();
        $espace_client_link = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : '#no-link';
        $output             = 'Vous êtes déjà connecté avec ce compte: <b>' . $user->display_name . '</b><br>';
        $output             .= '<a class="btn btn-outline-primary btn-fix btn-thick mt-4" href="' . $espace_client_link . '">Espace client</a>';
        $output             .= '<a class="btn btn-outline-primary btn-fix btn-thick mt-4 ml-2" href="' . $logoutUrl . '">Déconnecter</a>';

        return $output;
      }

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
        wp_enqueue_style( 'input-form', get_template_directory_uri() . '/assets/css/inputForm.css' );
        wp_enqueue_script( 'form-company', get_template_directory_uri() . '/assets/js/app/register/form-company.js',
          [
            'angular',
            'angular-route',
            'angular-sanitize',
            'angular-messages',
            'angular-animate',
            'angular-aria',
          ], $itJob->version, true );
        wp_localize_script( 'form-company', 'itOptions', [
          'ajax_url'     => admin_url( 'admin-ajax.php' ),
          'partials_url' => get_template_directory_uri() . '/assets/js/app/register/partials',
          'template_url' => get_template_directory_uri()
        ] );
        try {
          /** @var STRING $title - Titre de l'element VC */
          return $Engine->render( '@VC/register/company.html.twig', [
            'title' => $title
          ] );
        } catch ( \Twig_Error_Loader $e ) {
        } catch ( \Twig_Error_Runtime $e ) {
        } catch ( \Twig_Error_Syntax $e ) {
          return $e->getRawMessage();
        }
      } else {
        // load script & style
        wp_enqueue_script( 'form-candidate', get_template_directory_uri() . '/assets/js/app/register/form-candidate.js',
          [ 'angular' ], $itJob->version, true );

        try {
          /** @var STRING $title - Titre de l'element VC */
          return $Engine->render( '@VC/register/candidate.html.twig', [
            'title' => $title
          ] );
        } catch ( \Twig_Error_Loader $e ) {
        } catch ( \Twig_Error_Runtime $e ) {
        } catch ( \Twig_Error_Syntax $e ) {
          return $e->getRawMessage();
        }
      }
    }
  }
endif;

return new vcRegisterCompany();