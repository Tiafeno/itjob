<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;
use includes\object\jobServices;

// FEATURED: Crée une shortcode pour le formulaire de connexion
if ( ! class_exists( 'scLogin' ) ) :
  class scLogin {
    public function __construct() {
      add_shortcode( 'itjob_login', [ &$this, 'sc_render_html' ] );

      add_action( 'wp_ajax_ajx_signon', [ &$this, 'login' ] );
      add_action( 'wp_ajax_nopriv_ajx_signon', [ &$this, 'login' ] );

      add_action( 'init', function () {
        $page_login_id = LOGIN_PAGE ? (int) LOGIN_PAGE : 0;
        if ( $page_login_id !== 0 ) {
          add_rewrite_tag( '%ptype%', '([^&]+)' );
          add_rewrite_rule( '^connexion/([^/]*)/?', 'index.php?page_id=' . $page_login_id . '&ptype=$matches[1]', 'top' );
        }
      }, 10, 0 );
    }

    public function login() {
      if ( ! \wp_doing_ajax() || \is_user_logged_in()) {
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
      global $Engine, $itJob, $wp_query;

      extract(
        shortcode_atts(
          array(
            'role'  => '',
            'redir' => home_url( '/' )
          ),
          $attrs
        )
      );

      /** @var STRING $redir */

      $redirection = Http\Request::getValue( 'redir' );
      $redirection = $redirection ? $redirection : $redir;

      $query_type = ! in_array( 'ptype', array_keys( $wp_query->query_vars ) ) ? null : $wp_query->query_vars['ptype'];
      /** @var string $role - Post type slug */
      $ptype = ! is_null( $query_type ) ? $query_type : $role;
      if ( is_user_logged_in() ) {
        $logoutUrl          = wp_logout_url( home_url( '/' ) );
        $user               = wp_get_current_user();
        $espace_client_link = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : '#no-link';

        $output = '<div class="uk-margin-large-top">';
        $output .= 'Vous êtes déjà connecté avec ce compte: <b>' . $user->display_name . '</b><br>';
        $output .= '<a class="btn btn-outline-blue btn-fix btn-thick mt-4" href="' . $espace_client_link . '">Espace client</a>';
        $output .= '<a class="btn btn-danger btn-fix btn-thick mt-4 ml-2" href="' . $logoutUrl . '">Déconnecter</a>';
        $output .= '</div>';

        return $output;
      }

      if ( ! post_type_exists( $ptype ) ) {
        return 'Bad link';
      }
      // Only company & particular singup in itjob
      $singup_page_id = ( $ptype === 'company' ) ? REGISTER_COMPANY_PAGE_ID : REGISTER_PARTICULAR_PAGE_ID;
      $singup_url     = $singup_page_id ? get_permalink( (int) $singup_page_id ) : '#no-link';

      // Enqueue scripts & style dependence
      wp_enqueue_script( 'login', get_template_directory_uri() . '/assets/js/app/login/form-login.js', [
        'angular',
        'angular-sanitize',
        'angular-messages',
        'angular-animate',
        'angular-aria',
      ], $itJob->version, true );

      $custom_area_url = jobServices::page_exists( 'Espace client' );
      $custom_area_url = $custom_area_url ? $custom_area_url : home_url( '/' );
      wp_localize_script( 'login', 'itOptions', [
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'urlHelper' => [
          'customer_area_url' => get_the_permalink($custom_area_url),
          'redir'             => $redirection
        ]
      ] );

      try {
        // Get pos type object
        $post_type_object = get_post_type_object( $ptype );
        $title            = $post_type_object->name === 'company' ? strtolower( $post_type_object->labels->singular_name ) : '';

        do_action('get_notice');

        /** @var STRING $title */
        return $Engine->render( '@SC/login.html.twig', [
          'title' => $title,
          'uri'   => (object) [
            'theme'  => get_template_directory_uri(),
            'singup' => $singup_url . '?redir='.$redirection
          ]
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