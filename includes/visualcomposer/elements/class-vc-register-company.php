<?php

namespace includes\vc;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

use Http;
use includes\post\Company;

if ( ! class_exists( 'vcRegisterCompany' ) ) :
  class vcRegisterCompany extends \WPBakeryShortCode {
    public static $container_class = '';
    public static $isInstance = false;
    public function __construct() {
      add_action( 'init', [ $this, 'register_mapping' ] );
      add_filter( 'acf/update_value/name=itjob_company_email', [ &$this, 'create_company_user' ], 10, 3 );
      // Ne pas envoyer une notification pour le changement de mot de passe
      add_filter( 'send_password_change_email', '__return_false' );

      if ( ! shortcode_exists('vc_register_company'))
        add_shortcode( 'vc_register_company', [ &$this, 'register_render_html' ] );

      add_action( 'wp_ajax_ajx_insert_company', [ &$this, 'ajx_insert_company' ] );
      add_action( 'wp_ajax_nopriv_ajx_insert_company', [ &$this, 'ajx_insert_company' ] );

      add_action( 'wp_ajax_ajx_get_branch_activity', [ &$this, 'ajx_get_branch_activity' ] );
      add_action( 'wp_ajax_nopriv_ajx_get_branch_activity', [ &$this, 'ajx_get_branch_activity' ] );

      add_action( 'wp_ajax_ajx_get_taxonomy', [ &$this, 'get_taxonomy' ] );
      add_action( 'wp_ajax_nopriv_ajx_get_taxonomy', [ &$this, 'get_taxonomy' ] );

      add_action( 'wp_ajax_ajx_user_exist', [ &$this, 'ajx_user_exist' ] );
      add_action( 'wp_ajax_nopriv_ajx_user_exist', [ &$this, 'ajx_user_exist' ] );
    }

    public static function getInstance() {
      self::$container_class = 'uk-margin-large-top';
      return new self();
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
    public function create_company_user( $value, $post_id, $field ) {
      $chars     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      $post_type = get_post_type( $post_id );
      if ( $post_type != 'company' ) {
        return $value;
      }

      $post      = get_post( $post_id );
      $userEmail = &$value;
      // Verifier si l'utilisateur existe déja; Si oui, retourner la valeur
      // (WP_User|false) WP_User object on success, false on failure.
      $userExist = get_user_by( 'email', $userEmail );
      if ( $userExist || !is_email($userEmail) ) {
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
        // Ajouter une clé d'activation pour rejeter le mot de passe
        get_password_reset_key( $user );

        // Ajouter le secteur de l'entreprise
        $sector = Http\Request::getValue( 'sector', 1 ); // Recruteur par default (1)
        update_user_meta($user->ID, 'sector', $sector);

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
          'name'        => 'Professional Form (SingUp)',
          'base'        => 'vc_register_company',
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
            )
          )
        )
      );
    }

    /**
     * Vérifier si l'utilisateur existe déja
     */
    public function ajx_user_exist() {
      if ( ! \wp_doing_ajax() ) {
        return false;
      }
      if ( is_user_logged_in() ) {
        return false;
      }
      $email = Http\Request::getValue( 'mail', false );
      if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        if ( ! is_email( $email )) wp_send_json_error( "L'adresse email est incorrect ou refuser" );

        $usr = get_user_by( 'email', $email );
        if ($usr)
         wp_send_json_success( "L'utilisateur existe déja");
        wp_send_json_error( "L'utilisateur n'existe pas" );
      } else {
        wp_send_json_error("L'adresse email n'est pas valide");
      }
    }

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

    /**
     * Function ajax
     * @param bool $taxonomy
     *
     * @return array|bool|int|\WP_Error
     */
    public function get_taxonomy($taxonomy = false) {
      $validateTaxonomy = ['job_sought', 'software'];
      /**
       * @func wp_doing_ajax
       * (bool) True if it's a WordPress Ajax request, false otherwise.
       */
      if ( \wp_doing_ajax() ||  ! empty( $_GET ) ) {
        $taxonomy = Http\Request::getValue( 'tax', false );
      }

      // FEATURED: Ajouter seulement les terms activé
      $termValid = [];
      if ( $taxonomy ) {
        $terms = get_terms( $taxonomy, [
          'hide_empty' => false,
          'fields'     => 'all'
        ] );
        if (in_array($taxonomy, $validateTaxonomy)):
          foreach ($terms as $term) {
            $valid = get_term_meta( $term->term_id, 'activated', true);
            if ($valid) {
              //$term->name = utf8_encode($term->name);
              array_push($termValid, $term);
            }
          }
        else:
          $termValid = &$terms;
        endif;

        if ( \wp_doing_ajax() || !empty( $_GET ) ) {
          wp_send_json( $termValid );
        } else {
          return $termValid;
        }

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
      if ( ! $userEmail || !is_email( $userEmail ) ) wp_send_json_error("Veillez remplir le formulaire correctement");
      $userExist = get_user_by( 'email', $userEmail );
      if ( $userExist ) {
        wp_send_json_error( 'L\'adresse e-mail ou l\'utilisateur existe déja');
      }
      $user = &$userExist;

      $form = (object) [
        'greeting'           => Http\Request::getValue( 'greeting' ),
        'title'              => Http\Request::getValue( 'title' ),
        'address'            => Http\Request::getValue( 'address' ),
        'country'            => Http\Request::getValue( 'country' ),
        'region'             => Http\Request::getValue( 'region' ),
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
      ], true );
      if ( is_wp_error( $result ) ) {
        wp_send_json( [ 'success' => false, 'msg' => $result->get_error_message() ] );
      }

      // update acf field
      $post_id = &$result;
      $this->update_acf_field( $post_id, $form );

      // Ajouter les terms dans le post
      wp_set_post_terms( $post_id, [ (int) $form->branch_activity_id ], 'branch_activity' );
      wp_set_post_terms( $post_id, [ (int) $form->region ], 'region' );
      wp_set_post_terms( $post_id, [ (int) $form->country ], 'city' );

      // featured: Envoie une email de confirmation pour le changement de mot de passe
      $user = get_user_by( 'email', trim($form->email) );

      do_action('register_user_company', $user->ID);
      do_action('notice-admin-new-company', $post_id);

      wp_send_json( [ 'success' => true, 'msg' => new Company( $post_id ), 'form' => $form ] );
    }

    /**
     * Ajouter ou mettre à jour les elements ACF pour le post 'company'
     *
     * @param int $post_id
     * @param \stdClass $form
     *
     * @return bool
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

      // En attente de la validation de l'administrateur
      update_field( 'activated', 0, $post_id );

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
            'redir' => null
          ),
          $attrs
        )
        , EXTR_OVERWRITE );

      // load script & style
      wp_enqueue_style( 'sweetalert' );
      wp_enqueue_style( 'input-form', get_template_directory_uri() . '/assets/css/inputForm.css' );
      wp_enqueue_script( 'form-company', get_template_directory_uri() . '/assets/js/app/register/form-company.js?ver='.$itJob->version,
        [
          'angular',
          'angular-ui-route',
          'angular-sanitize',
          'angular-messages',
          'angular-animate',
          'angular-aria',
          'sweetalert',
        ], $itJob->version, true );

      /** @var url $redir */
      $redirHttp = Http\Request::getValue('redir');
      $redirection_query = !is_null($redir) ? "?redir={$redir}" : ($redirHttp ? "?redir={$redirHttp}" : '');
      $redirection = !is_null($redir) ? $redir : $redirHttp;
      wp_localize_script( 'form-company', 'itOptions', [
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'partials_url' => get_template_directory_uri() . '/assets/js/app/register/partials',
        'template_url' => get_template_directory_uri(),
        'version' => $itJob->version,
        'Helper'    => [
          'redir' => $redirection,
          'login' => home_url('/connexion/company') . $redirection_query
        ]
      ] );
      try {
        do_action('get_notice');
        /** @var STRING $title - Titre de l'element VC */
        return $Engine->render( '@VC/register/company.html.twig', [
          'title' => $title,
          'container_class' => self::$container_class,
          'template_url' => get_template_directory_uri()
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }
  }
endif;

return new vcRegisterCompany();