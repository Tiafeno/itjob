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

      /**
       * Activer l'offre.
       * Cette evenement ce declanche quand l'administrateur publie une offre
       *
       * @param int $ID
       */
      add_action( 'acf/save_post', function ( $post_id ) {
        $post_type   = get_post_type( $post_id );
        $post_status = get_post_status( $post_id );
        update_field( 'activated', 1, $post_id );

        // Activer les experiences et les formations
        if ($post_type === 'candidate' && $post_status === 'publish') {
          $Experiences = get_field('itjob_cv_experiences', $post_id);
          $Trainings = get_field('itjob_cv_trainings', $post_id);
          if (is_array($Experiences) && !empty($Experiences)) {
            $Values = [];
            foreach ($Experiences as $experience) {
              $experience['validated'] = 1;
              $Values[] = $experience;
            }
            update_field('itjob_cv_experiences', $Values, $post_id);
          }

          if (is_array($Trainings) && !empty($Trainings)) {
            $Values = [];
            foreach ($Trainings as $training) {
              $training['validated'] = 1;
              $Values[] = $training;
            }
            update_field('itjob_cv_trainings', $Values, $post_id);
          }
        }
      }, 10, 1 );

      /** Effacer les elements acf avant d'effacer l'article */
      add_action( 'before_delete_post', function ( $postId ) {
        $isPosts    = [ 'candidate', 'offers', 'company' ];
        $pst        = get_post( $postId );
        $class_name = ucfirst( $pst->post_type );
        if ( class_exists( $class_name ) && in_array( $pst->post_type, $isPosts ) ) {
          $class = new \ReflectionClass( "includes\\post\\$class_name" );
          $class->remove();
        }
      } );

      // Effacer le candidat ou l'entreprise si on supprime l'utilisateur
      add_action('delete_user', function ($user_id) {
        $user_obj = get_userdata($user_id);
        if (in_array('company', $user_obj->roles)) {
          $Company = Post\Company::get_company_by($user_id);
          // TODO: Effacer les offres
          wp_delete_post($Company->getId());
        }

        if (in_array('candidate', $user_obj->roles)) {
          $Candidate = Post\Candidate::get_candidate_by($user_id);
          wp_delete_post($Candidate->getId());
        }
      });

      add_action( 'user_register', function ( $user_id ) {
        do_action("new_register_user", $user_id);

      }, 10, 1 );

      // @doc https://wordpress.stackexchange.com/questions/7518/is-there-a-hook-for-when-you-switch-themes
      add_action( 'after_switch_theme', function ( $new_theme ) {
        global $wpdb;

        // Cree une table pour les entreprise intereser par des candidats
        $cv_request = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_request (`id_cv_request` BIGINT(20) NOT NULL AUTO_INCREMENT , `id_company` BIGINT(20) NOT NULL DEFAULT 0, 
`id_candidate` BIGINT(20) NOT NULL DEFAULT 0, `id_offer` BIGINT(20) NOT NULL DEFAULT 0 , `type` VARCHAR(20) NOT NULL , `status` VARCHAR(20) NOT NULL DEFAULT 'pending' , 
`id_attachment` BIGINT(20) NOT NULL DEFAULT 0,
                         PRIMARY KEY (`id_cv_request`)) ENGINE = InnoDB;";
        $wpdb->query( $cv_request );

        // Crée une table pour ajouter les CV dans la liste des entreprise
        // Pour les entreprise de membre standar, la liste se limite à 5 CV
        $cv_lists = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_lists (`id_lists` BIGINT(20) NOT NULL AUTO_INCREMENT , `id_company` BIGINT(20) NOT NULL DEFAULT 0, 
                      `id_candidate` BIGINT(20) NOT NULL DEFAULT 0, 
                      PRIMARY KEY (`id_lists`)) ENGINE = InnoDB;";
        $wpdb->query( $cv_lists );
      } );

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
          $query->set( 'post_status', [ 'publish' ] );
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

            switch ( $post_type ) {
              // Trouver des offres d'emplois
              CASE 'offers':

                if ( ! empty( $abranch ) ) {
                  $meta_query   = isset( $meta_query ) ? $meta_query : $query->get( 'meta_query' );
                  $meta_query[] = [
                    'key'     => 'itjob_offer_abranch',
                    'value'   => (int) $abranch,
                    'compare' => '=',
                    'type'    => 'NUMERIC'
                  ];
                }

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

                if ( ! empty( $abranch ) ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query[] = [
                    'taxonomy'         => 'branch_activity',
                    'field'            => 'term_id',
                    'terms'            => (int) $abranch,
                    'include_children' => false
                  ];
                }

                if ( ! empty( $language ) ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query[] = [
                    'taxonomy'         => 'language',
                    'field'            => 'term_id',
                    'terms'            => (int) $language,
                    'include_children' => false
                  ];
                }

                // Rechercher dans les logiciel si le champ n'es pas vide
                if ( ! empty( $software ) ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query[] = [
                    'taxonomy'         => 'software',
                    'field'            => 'term_id',
                    'terms'            => (int) $software,
                    'include_children' => false
                  ];
                }

                if ( ! empty( $s ) ) {
                  $searchs = explode(' ', $s);
                  // Rechercher les mots dans les emplois
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query[] = [
                    'taxonomy' => 'job_sought',
                    'field' => 'name',
                    'terms' => $searchs
                  ];

                  if ( ! isset( $meta_query ) ) {
                    $meta_query = $query->get( 'meta_query' );
                  }
                  foreach ($searchs as $search) {
                    if (empty($search)) continue;
                    // TODO: Corriger la cherche pour tout les experiences et les formations
                    $meta_query[] = [
                      'relation' => 'OR',
                      [
                        'key'     => 'itjob_cv_trainings_0_training_establishment',
                        'value'   => $search,
                        'compare' => 'LIKE',
                        'type'    => 'CHAR'
                      ],
                      [
                        'key'     => 'itjob_cv_trainings_0_training_diploma',
                        'value'   => $search,
                        'compare' => 'LIKE',
                        'type'    => 'CHAR'
                      ],
                      [
                        'key'     => 'itjob_cv_experiences_0_exp_mission',
                        'value'   => $search,
                        'compare' => 'LIKE',
                        'type'    => 'CHAR'
                      ],
                      [
                        'key'     => 'itjob_cv_experiences_0_exp_positionHeld',
                        'value'   => $search,
                        'compare' => 'LIKE',
                        'type'    => 'CHAR'
                      ],
                      [
                        'key'     => 'itjob_cv_experiences_0_exp_company',
                        'value'   => $search,
                        'compare' => 'LIKE',
                        'type'    => 'CHAR'
                      ]
                    ];
                                      }
                  // Feature: Recherché aussi dans le profil recherché et mission
                  
                  $query->set( 'meta_query', $meta_query );
                  $query->meta_query = new \WP_Meta_Query( $meta_query );
                }

                // Meta query
                if ( ! isset( $meta_query ) ) {
                  $meta_query = $query->get( 'meta_query' );
                }

                $meta_query[] = [
                  'key'     => 'activated',
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

                print_r($query);
                BREAK;
            } // .end switch


            // FEATURE: Supprimer la condition de trouver le ou les mots dans le titre et le contenue
            $query->query['s']      = '';
            $query->query_vars['s'] = '';
          } // .end if - search conditional
          else {
            // Filtrer les candidates ou les offers ou les entreprises
            // Afficher seulement les candidates ou les offres ou les entreprises activer
            if ( $post_type === 'candidate' || $post_type === 'offers' || $post_type === 'company' ):
              // Meta query
              if ( ! isset( $meta_query ) ) {
                $meta_query = $query->get( 'meta_query' );
              }

              $meta_query[] = [
                'key'     => 'activated',
                'value'   => 1,
                'compare' => '=',
                'type'    => 'NUMERIC'
              ];
              if ($post_type === 'candidate')
                $meta_query[] = [
                  'key'     => 'itjob_cv_hasCV',
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
            // Afficher les CV pour les comptes entreprise premium
            $current_user = wp_get_current_user();
            if ( $current_user->ID !== 0 && ! current_user_can( 'remove_users' ) ) {
              if ( in_array( 'company', $current_user->roles ) ) {
                $Company = Post\Company::get_company_by( $current_user->ID );
                // Si le client en ligne est une entreprise on continue...
                // On recupere le type du compte
                $account = get_post_meta( $Company->getId(), 'itjob_meta_account', true );
                // Si le compte de l'entreprise est premium
                if ( (int) $account === 1 ) {
                  $GLOBALS['candidate']->__client_premium_access();
                }
              }
            }
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
          'name'          => 'Archive Top (Offer)',
          'id'            => 'archive-offer-top',
          'description'   => 'Afficher des widgets en haut de la page archive',
          'before_widget' => '<div id="%1$s" class="widget mt-4 %2$s">',
          'after_widget'  => '</div>'
        ) );
        register_sidebar( array(
          'name'          => 'Archive Sidebar (Offer)',
          'id'            => 'archive-offer-sidebar',
          'description'   => 'Afficher des widgets sur côté de la page archive',
          'before_widget' => '<div id="%1$s" class="widget mt-4 %2$s">',
          'after_widget'  => '</div>'
        ) );

        register_sidebar( array(
          'name'          => 'Single Sidebar (Offer)',
          'id'            => 'single-offer-sidebar',
          'description'   => 'Afficher des widgets sur côté de la page archive',
          'before_widget' => '<div id="%1$s" class="widget mt-4 %2$s">',
          'after_widget'  => '</div>'
        ) );

        // CV
        register_sidebar( array(
          'name'          => 'Archive Top (Candidate)',
          'id'            => 'archive-cv-top',
          'description'   => 'Afficher des widgets en haut de la page archive',
          'before_widget' => '<div id="%1$s" class="widget mb-4 %2$s">',
          'after_widget'  => '</div>'
        ) );

        register_sidebar( array(
          'name'          => 'Archive Sidebar (Candidate)',
          'id'            => 'archive-cv-sidebar',
          'description'   => 'Afficher des widgets en haut de la page archive',
          'before_widget' => '<div id="%1$s" class="widget %2$s">',
          'after_widget'  => '</div>'
        ) );

        register_sidebar( array(
          'name'          => 'Content Top (Candidate)',
          'id'            => 'cv-header',
          'description'   => 'Afficher des widgets en mode header',
          'before_widget' => '<div id="%1$s" class="widget %2$s">',
          'after_widget'  => '</div>'
        ) );

        // Register widget
        register_widget( 'Widget_Publicity' );
        register_widget( 'Widget_Shortcode' );
        register_widget( 'includes\widgets\Widget_Accordion' );
        register_widget( 'Widget_Header_Search' );

      } );

      // Enregistrer les scripts dans la back-office
      add_action( 'admin_enqueue_scripts', [ $this, 'register_enqueue_scripts' ] );

      // Ajouter les scripts ou styles necessaire pour le template
      add_action( 'wp_enqueue_scripts', function () {
        global $itJob;

        // Load uikit stylesheet
        wp_enqueue_style( 'uikit', get_template_directory_uri() . '/assets/css/uikit.min.css', '', '3.0.0rc19' );
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
      wp_register_script( 'ngFileUpload', get_template_directory_uri() . '/assets/js/libs/ngfileupload/ng-file-upload.min.js', [], '12.2.13', true );

      wp_register_script( 'angular-froala', VENDOR_URL . '/froala-editor/src/angular-froala.js', [], '2.8.4' );
      wp_register_script( 'froala', VENDOR_URL . '/froala-editor/js/froala_editor.pkgd.min.js', [ 'angular-froala' ], '2.8.4' );

      // plugins depend
      wp_register_style( 'font-awesome', VENDOR_URL . '/font-awesome/css/font-awesome.min.css', '', '4.7.0' );
      wp_register_style( 'line-awesome', VENDOR_URL . '/line-awesome/css/line-awesome.min.css', '', '1.1.0' );
      wp_register_style( 'themify-icons', VENDOR_URL . '/themify-icons/css/themify-icons.css', '', '1.1.0' );
      wp_register_style( 'select-2', VENDOR_URL . "/select2/dist/css/select2.min.css", '', $itJob->version );

      wp_register_script( 'jquery-additional-methods', VENDOR_URL . '/jquery-validation/dist/additional-methods.min.js', [ 'jquery' ], '1.17.0', true );
      wp_register_script( 'jquery-validate', VENDOR_URL . '/jquery-validation/dist/jquery.validate.min.js', [ 'jquery' ], '1.17.0', true );
      // papaparse
      wp_register_script( 'papaparse', get_template_directory_uri() . '/assets/js/libs/papaparse/papaparse.min.js', [], '4.6.0' );

      // Register components adminca stylesheet
      wp_register_style( 'bootstrap', VENDOR_URL . '/bootstrap/dist/css/bootstrap.min.css', '', '4.0.0' );

      wp_register_style( 'bootstrap-tagsinput', VENDOR_URL . '/bootstrap-tagsinput/src/bootstrap-tagsinput.css', '', '4.0.0' );
      wp_register_script( 'bootstrap-tagsinput', VENDOR_URL . '/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js', [], '0.8', true );

      wp_register_style( 'ng-tags-bootstrap', get_template_directory_uri() . '/assets/css/ng-tags-input.min.css', '', '3.2.0' );
      wp_register_script( 'ng-tags', get_template_directory_uri() . '/assets/js/ng-tags-input.min.js', [ 'angular' ], '3.2.0', true );

      wp_register_script( 'typeahead', get_template_directory_uri() . '/assets/js/typeahead.bundle.js', [ 'jquery' ], '0.11.1', true );

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

      wp_register_script( 'daterangepicker-lang', VENDOR_URL . '/bootstrap-daterangepicker/locales/bootstrap-datepicker.fr.min.js', [ 'jquery' ] );
      wp_register_script( 'daterangepicker', VENDOR_URL . '/bootstrap-daterangepicker/daterangepicker.js', [ 'daterangepicker-lang' ] );

      wp_register_style( 'sweetalert', VENDOR_URL . '/bootstrap-sweetalert/dist/sweetalert.css' );
      wp_register_script( 'sweetalert', VENDOR_URL . '/bootstrap-sweetalert/dist/sweetalert.min.js', [], $itJob->version, true );

      wp_register_style( 'alertify', VENDOR_URL . '/alertifyjs/dist/css/alertify.css' );
      wp_register_script( 'alertify', VENDOR_URL . '/alertifyjs/dist/js/alertify.js', [], '1.0.11', true );

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
      wp_register_script( 'fr-datepicker', VENDOR_URL . '/bootstrap-datepicker/dist/locales/bootstrap-datepicker.fr.min.js', [ 'jquery' ], '1.7.1' );

      wp_register_script( 'b-datepicker', VENDOR_URL . '/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js', [ 'jquery' ], '1.7.1' );
      wp_register_script( 'bootstrap-select', VENDOR_URL . '/bootstrap-select/dist/js/bootstrap-select.min.js', [
        'jquery',
        'bootstrap'
      ], '1.12.4', true );
      wp_register_script( 'moment-locales', VENDOR_URL . '/moment/min/moment-with-locales.min.js', [], '2.19.1', true );
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
