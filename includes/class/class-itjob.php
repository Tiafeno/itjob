<?php
namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;
use includes\post as Post;

if ( ! class_exists( 'itJob' ) ) {
  final class itJob {
    use \Register;

    public $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    public function __construct() {

      add_action( 'init', function () {
        $this->postTypes();
        $this->taxonomy();
      } );

      add_action( 'acf/save_post', [ &$this, 'post_publish_candidate' ], 20 );

      // TODO: Envoyer un mail information utilisateur et adminstration (pour s'informer d'un nouveau utilisateur)
      add_action( 'acf/save_post', function ( $post_id ) {
        // Code here

      }, 20 );

      /**
       * When there’s no previous status (this means these hooks are always run whenever "save_post" runs).
       * @Hook: new_post (https://codex.wordpress.org/Post_Status_Transitions)
       *
       * @param int $post_ID Post ID.
       * @param WP_Post $post Post object.
       * @param bool $update Whether this is an existing post being updated or not.
       */
      add_action( 'save_post', function ( $post_id, $post, $update ) {

      }, 10, 3 );

      add_action( 'wp_loaded', function () {
      }, 20 );

      // Add acf google map api
      add_filter( 'acf/init', function () {
        acf_update_setting( 'google_api_key', base64_decode( __google_api__ ) );
      } );

      // Sets the text domain used when translating field and field group settings.
      // Defaults to ”. Strings will not be translated if this setting is empty
      add_filter( 'acf/settings/l10n_textdomain', function () {
        return __SITENAME__;
      } );

      // Ajouter le post dans la requete
      // @link: https://codex.wordpress.org/Plugin_API/Action_Reference/pre_get_posts
      add_action( 'pre_get_posts', function ( &$query ) {
        if ( ! is_admin() && $query->is_main_query() ) {
          // Afficher les posts pour status 'en attente' et 'publier'
          $query->set( 'post_status', [ 'publish', 'pending' ] );

          if ( $query->is_search ) {

            $post_type = $query->get( 'post_type' );
            $region    = Http\Request::getValue( 'rg', '' );
            $abranch   = Http\Request::getValue( 'ab', '' );
            $s         = get_query_var( 's' );

            if ( ! empty( $region ) ) {
              $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
              $tax_query[] = [
                'taxonomy' => 'region',
                'field'    => 'term_id',
                'terms'    => (int) $region,
                'operator' => 'IN'
              ];
            }

            if ( ! empty( $abranch ) ) {
              $meta_query   = isset( $meta_query ) ? $meta_query : $query->get( 'meta_query' );
              $meta_query[] = [
                'key'     => 'itjob_offer_abranch',
                'value'   => (int) $abranch,
                'compare' => '=',
                'type'    => 'NUMERIC'
              ];
            }

            switch ( $post_type ) {
              // Trouver des offres d'emplois
              CASE 'offers':
                if ( ! empty( $s ) ) {
                  if ( ! isset( $meta_query ) ) {
                    $meta_query = $query->get( 'meta_query' );
                  }
                  // Feature: Recherché aussi dans le profil recherché et mission
                  $meta_query[] = [
                    'relation' => 'OR',
                    [
                      'key'     => 'itjob_offer_mission',
                      'value'   => $s,
                      'compare' => 'LIKE',
                      'type'    => 'CHAR'
                    ],
                    [
                      'key'     => 'itjob_offer_profil',
                      'value'   => $s,
                      'compare' => 'LIKE',
                      'type'    => 'CHAR'
                    ],
                    [
                      'key'     => 'itjob_offer_post',
                      'value'   => $s,
                      'compare' => 'LIKE',
                      'type'    => 'CHAR'
                    ]
                  ];

                }

                if ( isset( $meta_query ) && ! empty( $meta_query ) ):
                  //$query->set( 'meta_query', $meta_query );
                  $query->meta_query = new \WP_Meta_Query( $meta_query );
                endif;

                if ( isset( $tax_query ) && ! empty( $tax_query ) ) {
                  //$query->set( 'tax_query', $tax_query );
                  $query->tax_query = new \WP_Tax_Query( $tax_query );
                  //$query->query_vars['tax_query'] = $query->tax_query->queries;
                }
                BREAK;

              // Trouver des candidates
              CASE 'candidate':
                $language = Http\Request::getValue( 'lg', '' );
                $software = Http\Request::getValue( 'ms', '' );
                if ( ! empty( $language ) ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query[] = [
                    'taxonomy' => 'language',
                    'field'    => 'term_id',
                    'terms'    => (int) $language,
                    'include_children' => false
                  ];
                }

                if ( ! empty( $software ) ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query[] = [
                    'taxonomy' => 'master_software',
                    'field'    => 'term_id',
                    'terms'    => (int) $software,
                    'include_children' => false
                  ];
                }

                if ( isset( $tax_query ) && ! empty( $tax_query ) ) {
                  $query->set( 'tax_query', $tax_query );
                  $query->tax_query = new \WP_Tax_Query( $tax_query );
                }
                BREAK;
            } // .end switch

            // TODO: Supprimer la condition de trouver le ou les mots dans le titre et le contenue
            $query->query['s']      = '';
            $query->query_vars['s'] = '';
          } // .end if - search conditional
        }
      } );

      add_action( 'the_post', function ( $post_object ) {
        $post_types = [ 'offers', 'company', 'candidate' ];
        if ( ! in_array( $post_object->post_type, $post_types ) ) {
          return;
        }
        switch ( $post_object->post_type ) {
          case 'candidate':
            $GLOBALS[ $post_object->post_type ] = new Post\Candidate( $post_object->ID );
            break;
          case 'offers':
            $GLOBALS[ $post_object->post_type ] = new Post\Offers( $post_object->ID );
            break;
          case 'company':
            $GLOBALS[ $post_object->post_type ] = new Post\Company( $post_object->ID );
            break;
        }
      } );

      add_action( 'admin_init', function () {
        if ( is_null( get_role( 'company' ) ) || is_null( get_role( 'candidate' ) ) ) {
          $this->createRoles();
        }

        /**
         * Ajouter une redirection sur certains utilisateurs à la page d'accueil
         * si la connexion c'est bien effectué.
         */
        /** @var bool $userRole */
        $userRole = current_user_can( 'company' ) || current_user_can( 'candidate' );
        $redirect = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : home_url( '/' );
        if ( is_admin() && ! defined( 'DOING_AJAX' ) && $userRole ) {
          exit( wp_redirect( $redirect, 301 ) );
        }
      }, 100 );


      add_action( 'after_setup_theme', function () {
        /** Seuls les administrateurs peuvent voir le menu bar de WordPress */
        if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
          show_admin_bar( false );
        }
      } );

      add_action( 'widgets_init', function () {
      } );

      add_action( 'wp_enqueue_scripts', function () {
        global $itJob;

        // Load uikit stylesheet
        wp_enqueue_style( 'uikit', get_template_directory_uri() . '/assets/css/uikit.min.css', '', '3.0.0rc10' );
        wp_enqueue_style( 'montserrat', 'https://fonts.googleapis.com/css?family=Montserrat:300,400,600,700' );

        // scripts
        wp_enqueue_script( 'underscore' );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'numeral', get_template_directory_uri() . '/assets/js/numeral.min.js', [], $itJob->version, true );
        wp_enqueue_script( 'bluebird', get_template_directory_uri() . '/assets/js/bluebird.min.js', [], $itJob->version, true );
        wp_enqueue_script( 'uikit', get_template_directory_uri() . '/assets/js/uikit.min.js', [ 'jquery' ], $itJob->version, true );

        /** Register scripts */
        $this->register_enqueue_scripts();
        wp_register_style( 'offers', get_template_directory_uri() . '/assets/css/offers/offers.css', [ 'adminca' ], $itJob->version );

        wp_enqueue_style( 'adminca' );
        wp_enqueue_script( 'adminca' );
        wp_enqueue_script( 'itjob', get_template_directory_uri() . '/assets/js/itjob.js', [
          'jquery',
          'underscore',
          'numeral',
          'bluebird',
          'uikit'
        ], $itJob->version, true );
      } );

      /** Effacer les elements acf avant d'effacer l'article */
      add_action( 'before_delete_post', function ( $postId ) {
        $pst = get_post( $postId );
        if ( $pst->post_type === 'offers' ) {
          $offer = new Post\Offers( $postId );
          $offer->removeOffer();
          unset( $offer );
        }
      } );
    }

    public function post_publish_candidate( $post_id ) {

      $post_type = get_post_type( $post_id );
      if ( $post_type != 'candidate' ) {
        return false;
      }

      $post      = get_post( $post_id );
      $userEmail = get_field( 'itjob_cv_email', $post_id );
      // (WP_User|false) WP_User object on success, false on failure.
      $userExist = get_user_by( 'email', $userEmail );
      if ( true == $userExist ) {
        return false;
      }

      $userFirstName = get_field( 'itjob_vc_firstname', $post_id );
      $userLastName  = get_field( 'itjob_vc_lastname', $post_id );
      $args          = [
        "user_pass"    => substr( str_shuffle( $this->chars ), 0, 8 ),
        "user_login"   => 'user' . $post_id,
        "user_email"   => $userEmail,
        "display_name" => $post->post_title,
        "first_name"   => $userFirstName,
        "last_name"    => $userLastName,
        "role"         => $post_type
      ];
      $user_id       = wp_insert_user( $args );
      if ( ! is_wp_error( $user_id ) ) {
        $user = new \WP_User( $user_id );
        get_password_reset_key( $user );

        return true;
      } else {
        return false;
      }
    }

    /**
     * Enregistrer des styles et scripts
     */
    public function register_enqueue_scripts() {
      global $itJob;

      $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
      // angular components
      wp_register_script( 'angular-route',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular-ui-router' . $suffix . '.js', [], '1.0.20' );
      wp_register_script( 'angular-sanitize',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular-sanitize' . $suffix . '.js', [], '1.7.2' );
      wp_register_script( 'angular-messages',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular-messages' . $suffix . '.js', [], '1.7.2' );
      wp_register_script( 'angular-animate',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular-animate' . $suffix . '.js', [], '1.7.2' );
      wp_register_script( 'angular-aria',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular-aria' . $suffix . '.js', [], '1.7.2' );
      wp_register_script( 'angular',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular' . $suffix . '.js', [], '1.7.2' );

      wp_register_script( 'angular-froala',
        get_template_directory_uri() . '/assets/vendors/froala-editor/src/angular-froala.js', [], '2.8.4' );
      wp_register_script( 'froala',
        get_template_directory_uri() . '/assets/vendors/froala-editor/js/froala_editor.pkgd.min.js', [ 'angular-froala' ], '2.8.4' );

      // plugins depend
      wp_register_style( 'font-awesome',
        get_template_directory_uri() . '/assets/vendors/font-awesome/css/font-awesome.min.css', '', '4.7.0' );
      wp_register_style( 'line-awesome',
        get_template_directory_uri() . '/assets/vendors/line-awesome/css/line-awesome.min.css', '', '1.1.0' );
      wp_register_style( 'themify-icons',
        get_template_directory_uri() . '/assets/vendors/themify-icons/css/themify-icons.css', '', '1.1.0' );
      wp_register_style( 'select-2',
        get_template_directory_uri() . "/assets/vendors/select2/dist/css/select2.min.css", '', $itJob->version );

      // papaparse
      wp_register_script( 'papaparse',
        get_template_directory_uri() . '/assets/js/libs/papaparse/papaparse.min.js', [], '4.6.0' );

      // Register components adminca stylesheet
      wp_register_style( 'bootstrap',
        get_template_directory_uri() . '/assets/vendors/bootstrap/dist/css/bootstrap.min.css', '', '4.0.0' );
      wp_register_style( 'adminca-animate',
        get_template_directory_uri() . '/assets/vendors/animate.css/animate.min.css', '', '3.5.1' );
      wp_register_style( 'toastr',
        get_template_directory_uri() . '/assets/vendors/toastr/toastr.min.css', '', '3.5.1' );
      wp_register_style( 'bootstrap-select',
        get_template_directory_uri() . '/assets/vendors/bootstrap-select/dist/css/bootstrap-select.min.css', '', '1.12.4' );

      // Load the main stylesheet
      wp_register_style( 'style', get_stylesheet_uri(), [
        'font-awesome',
        'line-awesome',
        'select-2'
      ], $itJob->version );
      wp_register_style( 'adminca',
        get_template_directory_uri() . '/assets/adminca/adminca.css', [
          'bootstrap',
          'adminca-animate',
          'toastr',
          'bootstrap-select',
          'style'
        ], $itJob->version );

      wp_register_style( 'froala-editor',
        get_template_directory_uri() . '/assets/vendors/froala-editor/css/froala_editor.min.css', '', '2.8.4' );
      wp_register_style( 'froala',
        get_template_directory_uri() . '/assets/vendors/froala-editor/css/froala_style.min.css', [
          'froala-editor',
          'font-awesome'
        ], '2.8.4' );

      // Register components adminca scripts
      wp_register_script( 'popper',
        get_template_directory_uri() . '/assets/vendors/popper.js/dist/umd/popper.min.js', [], '0.0.0', true );
      wp_register_script( 'bootstrap',
        get_template_directory_uri() . '/assets/vendors/bootstrap/dist/js/bootstrap.min.js', [ 'popper' ], '4.0.0-beta', true );
      wp_register_script( 'jq-slimscroll',
        get_template_directory_uri() . '/assets/vendors/jquery-slimscroll/jquery.slimscroll.min.js', [ 'jquery' ], '1.3.8', true );
      wp_register_script( 'idle-timer',
        get_template_directory_uri() . '/assets/vendors/jquery-idletimer/dist/idle-timer.min.js', [], '1.1.0', true );
      wp_register_script( 'toastr',
        get_template_directory_uri() . '/assets/vendors/toastr/toastr.min.js', [ 'jquery' ], '0.0.0', true );
      wp_register_script( 'bootstrap-select',
        get_template_directory_uri() . '/assets/vendors/bootstrap-select/dist/js/bootstrap-select.min.js', [
          'jquery',
          'bootstrap'
        ], '1.12.4', true );
      wp_register_script( 'adminca', get_template_directory_uri() . '/assets/adminca/adminca.js', [
        'bootstrap',
        'jq-slimscroll',
        'idle-timer',
        'toastr',
        'bootstrap-select'
      ], $itJob->version, true );
    }

  }
}

return new itJob();
?>
