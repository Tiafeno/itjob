<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;

// TODO: Crée une shortcode pour le formulaire de connexion
if ( ! class_exists( 'scLogin' ) ) :
  class scLogin {
    public function __construct() {
      add_shortcode( 'itjob_login', [ &$this, 'sc_render_html' ] );

      add_action( 'wp_ajax_ajx_signon', [ &$this, 'login' ] );
      add_action( 'wp_ajax_nopriv_ajx_signon', [ &$this, 'login' ] );
    }

    public function login() {
      if ( ! \wp_doing_ajax() ) {
        return;
      }

      // Vérifier la valeur si c'est un login ou un adresse email
      $log        = Http\Request::getValue( 'log', false );
      $pwd        = Http\Request::getValue( 'pwd' );
      $remember   = Http\Request::getValue( 'rememberme', false );
      $filterUser = filter_var( $log, FILTER_VALIDATE_EMAIL )
        ? get_user_by( 'email', $log )
        : get_user_by( 'login', $log );

      if ( $filterUser && wp_check_password( $pwd, $filterUser->data->user_pass, $filterUser->ID ) ) {
        $creds = array(
          'user_login'    => $log,
          'user_password' => $pwd,
          'remember'      => $remember
        );
        $user  = wp_signon( $creds, false ); // WP_Error|WP_User
        if ( is_wp_error( $user ) ) {
          wp_send_json( [ 'logged' => false, 'msg' => __( 'Wrong username or password.' ) ] );
        } else {
          wp_send_json( [ 'logged' => true, 'msg' => 'Connexion réussie', 'user' => $user ] );
        }
      } else {
        wp_send_json( [ 'logged' => false, 'msg' => __( 'Wrong username or password.' ) ] );
      }
    }

    public function sc_render_html( $attrs, $content = '' ) {
      global $Engine, $itJob;

      if ( is_user_logged_in() ) {
        $logoutUrl = wp_logout_url( home_url( '/' ) );
        $user      = wp_get_current_user();
        $output    = 'Vous êtes déjà connecté avec ce compte: <b>' . $user->display_name . '</b><br>';
        $output    .= '<a class="btn btn-outline-primary btn-fix btn-thick mt-4" href="#espace_client">Espace client</a>';
        $output    .= '<a class="btn btn-outline-primary btn-fix btn-thick mt-4 ml-2" href="' . $logoutUrl . '">Déconnecter</a>';

        return $output;
      }
      extract(
        shortcode_atts(
          array(
            'title'        => '',
            'redirect_url' => home_url( '/' )
          ),
          $attrs
        )
      );

      // get customer area url

      wp_enqueue_script( 'login', get_template_directory_uri() . '/assets/js/app/login/form-login.js', [
        'angular',
        'angular-sanitize',
        'angular-messages',
        'angular-animate',
        'angular-aria',
      ], $itJob->version, true );

      /** @var STRING $redirect_url */
      wp_localize_script( 'login', 'itOptions', [
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'customer_area_url' => $redirect_url
      ] );

      try {
        /** @var STRING $title */
        return $Engine->render( '@SC/login.html.twig', [
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

return new scLogin();