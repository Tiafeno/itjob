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
        $this->initRegister();
      } );

      add_action( 'je_postule', [ &$this, 'je_postule_Fn' ] );

      // TODO: Envoyer un mail information utilisateur et adminstration (pour s'informer d'un nouveau utilisateur)
      add_action( 'acf/save_post', function ( $post_id ) {
        // Code here

      }, 20 );

      /** Effacer les elements acf avant d'effacer l'article */
      add_action( 'before_delete_post', function ( $postId ) {
        $isPosts = ['candidate', 'offers', 'company'];
        $pst = get_post( $postId );
        $class_name = ucfirst( $pst->post_type );
        if (class_exists($class_name) && in_array($pst->post_type, $isPosts)) {
          $class = new \ReflectionClass( "includes\\post\\$class_name" );
          $class->remove();
        }


      } );

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

      // Ajouter le mot de passe de l'utilisateur
      add_action( 'user_register', function ( $user_id ) {
        $user       = get_userdata( $user_id );
        $user_roles = $user->roles;
        if ( in_array( 'company', $user_roles, true ) ||
             in_array( 'candidate', $user_roles, true ) ) {
          $pwd = $_POST['pwd'];
          if ( isset( $pwd ) ) {
            $id = wp_update_user( [ 'ID' => $user_id, 'user_pass' => trim( $pwd ) ] );
            if ( is_wp_error( $id ) ) {
              return true;
            } else {
              // Mot de passe utilisateur à etes modifier avec success
              return false;
            }
          }
        }
      }, 10, 1 );


      add_action( 'wp_loaded', function () {

      }, 20 );

      // @doc https://wordpress.stackexchange.com/questions/7518/is-there-a-hook-for-when-you-switch-themes
      add_action('after_switch_theme', function ($new_theme) {
        global $wpdb;
        //$meta_sql = "ALTER TABLE $wpdb->postmeta ADD COLUMN IF NOT EXISTS `meta_activate` INT NOT NULL DEFAULT 1 AFTER `meta_value`";
        //$term_sql = "ALTER TABLE {$wpdb->prefix}terms ADD COLUMN IF NOT EXISTS `term_activate` INT NOT NULL DEFAULT 1 AFTER `slug`";
        //$wpdb->query($meta_sql);
        //$wpdb->query($term_sql);
      });

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
          $post_type = $query->get( 'post_type' );
          //$query->set( 'posts_per_page', 1 );

          if ( $query->is_search ) {

            $region  = Http\Request::getValue( 'rg', '' );
            $abranch = Http\Request::getValue( 'ab', '' );
            $s       = get_query_var( 's' );

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
                  $meta_query = [
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
                  $query->set( 'meta_query', $meta_query );
                  $query->meta_query = new \WP_Meta_Query( $meta_query );
                endif;

                if ( isset( $tax_query ) && ! empty( $tax_query ) ) {
                  $query->set( 'tax_query', $tax_query );
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
                    'taxonomy'         => 'language',
                    'field'            => 'term_id',
                    'terms'            => (int) $language,
                    'include_children' => false
                  ];
                }

                if ( ! empty( $software ) ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query[] = [
                    'taxonomy'         => 'master_software',
                    'field'            => 'term_id',
                    'terms'            => (int) $software,
                    'include_children' => false
                  ];
                }

                // Meta query
                if ( ! isset( $meta_query ) ) {
                  $meta_query = $query->get( 'meta_query' );
                }

                $meta_query[] = [
                  'key'     => 'itjob_cv_activated',
                  'value'   => 1,
                  'compare' => '=',
                  'type'    => 'NUMERIC'
                ];

                if ( isset( $meta_query ) && ! empty( $meta_query ) ):
                  $query->set( 'meta_query', $meta_query );
                  $query->meta_query = new \WP_Meta_Query( $meta_query );
                endif;

                if ( isset( $tax_query ) && ! empty( $tax_query ) ) {
                  $query->set( 'tax_query', $tax_query );
                  $query->tax_query = new \WP_Tax_Query( $tax_query );
                }
                BREAK;
            } // .end switch

            // FEATURE: Supprimer la condition de trouver le ou les mots dans le titre et le contenue
            $query->query['s']      = '';
            $query->query_vars['s'] = '';
          } // .end if - search conditional
          else {
            // Filtrer les candidates
            // Afficher seulement les candidates activer
            if ( $post_type === 'candidate' ):
              // Meta query
              if ( ! isset( $meta_query ) ) {
                $meta_query = $query->get( 'meta_query' );
              }

              $meta_query[] = [
                'key'     => 'itjob_cv_activated',
                'value'   => 1,
                'compare' => '=',
                'type'    => 'NUMERIC'
              ];

              if ( isset( $meta_query ) && ! empty( $meta_query ) ):
                $query->set( 'meta_query', $meta_query );
                $query->meta_query = new \WP_Meta_Query( $meta_query );
              endif;
            endif;
          }
        }
      } );

      /**
       * Ajouter dans les variables global pour nom post-types le contenue du post
       */
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

      // Désactiver l'access à la back-office pour les utilisateurs non admin
      add_action( 'after_setup_theme', function () {
        /** Seuls les administrateurs peuvent voir le menu bar de WordPress */
        if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
          show_admin_bar( false );
        }
      } );

      // Ajouter des positions widgetable et enregistrer des widgets
      add_action( 'widgets_init', function () {
        // Register sidebar
        // Offers
        register_sidebar( array(
          'name'          => 'Archive Offre Haut',
          'id'            => 'archive-offer-top',
          'description'   => 'Afficher des widgets en haut de la page archive',
          'before_widget' => '<div id="%1$s" class="widget mt-4 %2$s">',
          'after_widget'  => '</div>'
        ) );
        register_sidebar( array(
          'name'          => 'Archive Offre Sidebar',
          'id'            => 'archive-offer-sidebar',
          'description'   => 'Afficher des widgets sur côté de la page archive',
          'before_widget' => '<div id="%1$s" class="widget mt-4 %2$s">',
          'after_widget'  => '</div>'
        ) );

        // CV
        register_sidebar( array(
          'name'          => 'Archive CV Haut',
          'id'            => 'archive-cv-top',
          'description'   => 'Afficher des widgets en haut de la page archive',
          'before_widget' => '<div id="%1$s" class="widget mb-4 %2$s">',
          'after_widget'  => '</div>'
        ) );

        register_sidebar( array(
          'name'          => 'Archive CV Sidebar',
          'id'            => 'archive-cv-sidebar',
          'description'   => 'Afficher des widgets en haut de la page archive',
          'before_widget' => '<div id="%1$s" class="widget %2$s">',
          'after_widget'  => '</div>'
        ) );

        register_sidebar( array(
          'name'          => 'CV Header',
          'id'            => 'cv-header',
          'description'   => 'Afficher des widgets en mode header',
          'before_widget' => '<div id="%1$s" class="widget %2$s">',
          'after_widget'  => '</div>'
        ) );

        // Register widget
        register_widget( 'Widget_Publicity' );
        register_widget( 'Widget_Shortcode' );
        register_widget( 'Widget_Accordion' );
        register_widget( 'Widget_Header_Search' );

      } );

      // Enregistrer les scripts dans la back-office
      add_action( 'admin_enqueue_scripts', [ $this, 'register_enqueue_scripts' ] );

      // Ajouter les scripts ou styles necessaire pour le template
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

        // Register scripts
        $this->register_enqueue_scripts();
        wp_register_style( 'offers', get_template_directory_uri() . '/assets/css/offers.css', [ 'adminca' ], $itJob->version );
        wp_register_style( 'candidate', get_template_directory_uri() . '/assets/css/candidate.css', [ 'adminca' ], $itJob->version );

        wp_enqueue_style( 'adminca' );
        wp_enqueue_style( 'themify-icons' );
        wp_enqueue_script( 'adminca' );
        wp_enqueue_script( 'itjob', get_template_directory_uri() . '/assets/js/itjob.js', [
          'jquery',
          'underscore',
          'numeral',
          'bluebird',
          'uikit'
        ], $itJob->version, true );
      } );
    }

    /**
     * Enregistrer des styles et scripts
     */
    public function register_enqueue_scripts() {
      global $itJob;

      $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
      // angular components
      wp_register_script( 'angular-ui-route',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular-ui-router' . $suffix . '.js', [], '1.0.20' );
      wp_register_script( 'angular-route',
        get_template_directory_uri() . '/assets/js/libs/angularjs/angular-route' . $suffix . '.js', [], '1.7.2' );
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
      wp_register_script('ngFileUpload', get_template_directory_uri() . '/assets/js/libs/ngfileupload/ng-file-upload.min.js',[], '12.2.13', true);

      wp_register_script( 'angular-froala', VENDOR_URL . '/froala-editor/src/angular-froala.js', [], '2.8.4' );
      wp_register_script( 'froala', VENDOR_URL . '/froala-editor/js/froala_editor.pkgd.min.js', [ 'angular-froala' ], '2.8.4' );

      // plugins depend
      wp_register_style( 'font-awesome', VENDOR_URL . '/font-awesome/css/font-awesome.min.css', '', '4.7.0' );
      wp_register_style( 'line-awesome', VENDOR_URL . '/line-awesome/css/line-awesome.min.css', '', '1.1.0' );
      wp_register_style( 'themify-icons', VENDOR_URL . '/themify-icons/css/themify-icons.css', '', '1.1.0' );
      wp_register_style( 'select-2', VENDOR_URL . "/select2/dist/css/select2.min.css", '', $itJob->version );

      wp_register_script('jquery-additional-methods', VENDOR_URL . '/jquery-validation/dist/additional-methods.min.js', ['jquery'], '1.17.0', true);
      wp_register_script('jquery-validate', VENDOR_URL . '/jquery-validation/dist/jquery.validate.min.js', ['jquery'], '1.17.0', true);
      // papaparse
      wp_register_script( 'papaparse',get_template_directory_uri() . '/assets/js/libs/papaparse/papaparse.min.js', [], '4.6.0' );

      // Register components adminca stylesheet
      wp_register_style( 'bootstrap', VENDOR_URL . '/bootstrap/dist/css/bootstrap.min.css', '', '4.0.0' );

      wp_register_style( 'bootstrap-tagsinput', VENDOR_URL . '/bootstrap-tagsinput/src/bootstrap-tagsinput.css', '', '4.0.0' );
      wp_register_script('bootstrap-tagsinput', VENDOR_URL . '/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js', [], '0.8', true);

      wp_register_style( 'ng-tags-bootstrap', get_template_directory_uri() . '/assets/css/ng-tags-input.min.css', '', '3.2.0' );
      wp_register_script('ng-tags', get_template_directory_uri() . '/assets/js/ng-tags-input.min.js', ['angular'], '3.2.0', true);

      wp_register_script('typeahead', get_template_directory_uri() . '/assets/js/typeahead.bundle.js', ['jquery'], '0.11.1', true);

      wp_register_style( 'adminca-animate', VENDOR_URL . '/animate.css/animate.min.css', '', '3.5.1' );
      wp_register_style( 'toastr', VENDOR_URL . '/toastr/toastr.min.css', '', '3.5.1' );
      wp_register_style( 'bootstrap-select', VENDOR_URL . '/bootstrap-select/dist/css/bootstrap-select.min.css', '', '1.12.4' );

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
      wp_register_style( 'b-datepicker-3', VENDOR_URL . '/bootstrap-datepicker/dist/css/bootstrap-datepicker3.min.css', '', '1.7.1' );
      wp_register_style( 'admin-adminca', get_template_directory_uri() . '/assets/css/admin-custom.css', [ 'adminca' ], $itJob->version );

      wp_register_script('daterangepicker-lang', VENDOR_URL . '/bootstrap-daterangepicker/locales/bootstrap-datepicker.fr.min.js', ['jquery']);
      wp_register_script('daterangepicker', VENDOR_URL . '/bootstrap-daterangepicker/daterangepicker.js', ['daterangepicker-lang']);

      wp_register_style( 'sweetalert', VENDOR_URL . '/bootstrap-sweetalert/dist/sweetalert.css' );
      wp_register_script( 'sweetalert', VENDOR_URL . '/bootstrap-sweetalert/dist/sweetalert.min.js', [], $itJob->version, true );

      wp_register_style( 'froala-editor', VENDOR_URL . '/froala-editor/css/froala_editor.min.css', '', '2.8.4' );
      wp_register_style( 'froala', VENDOR_URL . '/froala-editor/css/froala_style.min.css', [
        'froala-editor',
        'font-awesome'
      ], '2.8.4' );

      // Register components adminca scripts
      wp_register_script( 'popper', VENDOR_URL . '/popper.js/dist/umd/popper.min.js', [], '0.0.0', true );
      wp_register_script( 'bootstrap', VENDOR_URL . '/bootstrap/dist/js/bootstrap.min.js', [ 'popper' ], '4.0.0-beta', true );
      wp_register_script( 'jq-slimscroll', VENDOR_URL . '/jquery-slimscroll/jquery.slimscroll.min.js', [ 'jquery' ], '1.3.8', true );
      wp_register_script( 'idle-timer', VENDOR_URL . '/jquery-idletimer/dist/idle-timer.min.js', [], '1.1.0', true );
      wp_register_script( 'toastr', VENDOR_URL . '/toastr/toastr.min.js', [ 'jquery' ], '0.0.0', true );
      wp_register_script( 'b-datepicker', VENDOR_URL . '/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js', [ 'jquery' ], '1.7.1' );
      wp_register_script( 'bootstrap-select', VENDOR_URL . '/bootstrap-select/dist/js/bootstrap-select.min.js', [
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

    public function je_postule_Fn() {
      // Vérifier si la page de création de CV exist
      $addedCVPage = jobServices::page_exists('Ajouter un CV');
      if ( ! $addedCVPage) return;
      $User = null;
      $addedCVUrl = sprintf('%s?offerId=%d&redir=%s', get_the_permalink($addedCVPage), get_the_ID(), get_the_permalink());
      $button = "<a href=\"$addedCVUrl\">
                  <button class=\"btn btn-blue btn-fix\">
                    <span class=\"btn-icon\">Je postule </span>
                  </button>
                </a>";
      echo $button;
    }

  }
}

return new itJob();
?>
