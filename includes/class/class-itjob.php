<?php
namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;
use includes\model\itModel;
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

        // Activer les experiences et les formations si le post est publier
        if ( $post_type === 'candidate' && $post_status === 'publish' ) {
          update_field( 'activated', 1, $post_id );
          $Experiences = get_field( 'itjob_cv_experiences', $post_id );
          $Trainings   = get_field( 'itjob_cv_trainings', $post_id );
          if ( is_array( $Experiences ) && ! empty( $Experiences ) ) {
            $Values = [];
            foreach ( $Experiences as $experience ) {
              $experience['validated'] = 1;
              $Values[]                = $experience;
            }
            update_field( 'itjob_cv_experiences', $Values, $post_id );
          }

          if ( is_array( $Trainings ) && ! empty( $Trainings ) ) {
            $Values = [];
            foreach ( $Trainings as $training ) {
              $training['validated'] = 1;
              $Values[]              = $training;
            }
            update_field( 'itjob_cv_trainings', $Values, $post_id );
          }

          // Crée une notification pour informer que le CV est validé
          
          do_action("notice-publish-cv", $post_id);
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

      add_action( 'delete_post', function ($post_id) {
        $pst = get_post( $post_id );
        if ($pst->post_type === "attachment") {
          $Model = new itModel();
          $Model->remove_attachment($pst->ID);
        }
      }, 10 );

      // Effacer le candidat ou l'entreprise si on supprime l'utilisateur
      add_action( 'delete_user', function ( $user_id ) {
        $user_obj = get_userdata( $user_id );
        $Model = new itModel();
        if ( in_array( 'company', $user_obj->roles ) ) {
          $company_id = $Model->get_company_id_by_email($user_obj->user_email);
          // FEATURED: Effacer les offres de l'entreprise
          $argOffers = [
            'post_type' => 'offers',
            'post_type' => 'any',
            'meta_query' => [
              [
                'key' => 'itjob_offer_company',
                'value' => $company_id,
                'compare' => '='
              ]
            ]
          ];
          // Récuperer tous les offres de l'entreprise
          $offers = get_posts($argOffers);
          foreach ($offers as $offer):
            // Effacer l'offre
            $deleted = wp_delete_post($offer->ID, true);
          if ( ! is_wp_error($deleted) )
            $Model->remove_interest($offer->ID);
          endforeach;
          wp_delete_post( $company_id, true);

        }

        if ( in_array( 'candidate', $user_obj->roles ) ) {
          $candidate_id = $Model->get_candidate_id_by_email($user_obj->user_email);
          wp_delete_post( $candidate_id, true);
        }
      } );

      add_action( 'user_register', function ( $user_id ) {
        // Ajouter le mot de passe de l'utilisateur...
        // Cette instruction est important car il enregistre les mot de passe des utilisateurs (particulier & entreprise)
        // qui s'inscrivent dans le site ITJOBMada
        if ( isset( $_POST['pwd'] ) || ! empty( $_POST['pwd'] ) ) {
          $user       = get_userdata( $user_id );
          $user_roles = $user->roles;
          if ( in_array( 'company', $user_roles, true ) ||
               in_array( 'candidate', $user_roles, true ) ) {
            $pwd = $_POST['pwd'];
            if ( isset( $pwd ) ) {
              $id = wp_update_user( [ 'ID' => $user_id, 'user_pass' => trim( $pwd ) ] );
              if ( is_wp_error( $id ) ) {
                return false;
              } else {
                // Mot de passe utilisateur à etes modifier avec success
                do_action( "new_register_user", $user_id );
                return true;
              }
            }
          }
        }

      }, 10, 1 );

      // @doc https://wordpress.stackexchange.com/questions/7518/is-there-a-hook-for-when-you-switch-themes
      add_action( 'after_switch_theme', function ( $new_theme ) {
        global $wpdb;
        // Cree une table pour les entreprise intereser par des candidats
        $cv_request = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_request (`id_cv_request` BIGINT(20) NOT NULL AUTO_INCREMENT , `id_company` BIGINT(20) NOT NULL DEFAULT 0, 
            `id_candidate` BIGINT(20) NOT NULL DEFAULT 0, `id_offer` BIGINT(20) NOT NULL DEFAULT 0 , `type` VARCHAR(20) NOT NULL , `status` VARCHAR(20) NOT NULL DEFAULT 'pending' , 
            `id_attachment` BIGINT(20) NOT NULL DEFAULT 0, `date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                         PRIMARY KEY (`id_cv_request`)) ENGINE = InnoDB;";
        $wpdb->query( $cv_request );

        // Crée une table pour ajouter les CV dans la liste des entreprise
        // Pour les entreprise de membre standar, la liste se limite à 5 CV
        $cv_lists = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_lists (`id_lists` BIGINT(20) NOT NULL AUTO_INCREMENT , `id_company` BIGINT(20) NOT NULL DEFAULT 0, 
                      `id_candidate` BIGINT(20) NOT NULL DEFAULT 0, `date_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id_lists`)) ENGINE = InnoDB;";
        $wpdb->query( $cv_lists );

        $notice = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}notices (
            `id_notice` bigint(20) PRIMARY KEY AUTO_INCREMENT,
            `id_user` bigint(20) NOT NULL,
            `template` TINYINT(1) NOT NULL DEFAULT 0,
            `status` boolean DEFAULT false,
            `needle` longtext NOT NULL COMMENT 'Contient les variables dans une array',
            `guid` varchar(255)	NOT NULL DEFAULT '',
            `date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP );";
        $wpdb->query($notice);

        $ads = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ads` (
          `id_ads` BIGINT(20) NOT NULL AUTO_INCREMENT,
          `id_attachment` BIGINT(20) NOT NULL,
          `img_size` VARCHAR(250) NOT NULL DEFAULT 'full',
          `title` TEXT(255) NOT NULL,
          `start` DATETIME NOT NULL,
          `end` DATETIME NOT NULL,
          `classname` VARCHAR(50) NULL,
          `id_user` BIGINT(20) UNSIGNED NOT NULL,
          `position` VARCHAR(45) NULL DEFAULT 0,
          `paid` TINYINT(1) NOT NULL DEFAULT 0,
          `bill` VARCHAR(45) NULL COMMENT 'Numéro de facture',
          `attr` LONGTEXT NULL,
          `create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_ads`)) ENGINE = InnoDB;";
        
        $wpdb->query($ads);
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

            $region  = Http\Request::getValue( 'rg' );
            $abranch = Http\Request::getValue( 'ab' );
            $s       = $_GET['s'];

            if ( ! empty( $region ) ) {
              $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
              $tax_query = !is_array($tax_query) ? [] : $tax_query;
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

                if ( $abranch ) {
                  $meta_query   = isset( $meta_query ) ? $meta_query : $query->get( 'meta_query' );
                  $meta_query = !is_array($meta_query) ? [] : $meta_query;
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
                  $meta_query = !is_array($meta_query) ? [] : $meta_query;
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

                // Meta query
                if ( ! isset( $meta_query ) ) {
                  $meta_query = $query->get( 'meta_query' );
                }
                $meta_query = !is_array($meta_query) ? [] : $meta_query;
                $meta_query[] = [
                  [
                    'key'     => 'activated',
                    'value'   => 1,
                    'compare' => '=',
                    'type'    => 'NUMERIC'
                  ]
                ];

                $meta_query['relation'] = "AND";

                if ( isset( $meta_query ) && ! empty( $meta_query ) ):
                  $query->set( 'meta_query', $meta_query );
                  $query->meta_query = new \WP_Meta_Query( $meta_query );
                endif;

                if ( isset( $tax_query ) && ! empty( $tax_query ) ) {
                  $query->set( 'tax_query', $tax_query );
                  $query->tax_query = new \WP_Tax_Query( $tax_query );
                  //$query->query_vars['tax_query'] = $query->tax_query->queries;
                }
                $query->query['s']      = '';
                $query->query_vars['s'] = '';
                BREAK;

              // Trouver des candidates
              CASE 'candidate':
                $language = Http\Request::getValue( 'lg', '' );
                $software = Http\Request::getValue( 'ms', '' );

                if ( $abranch ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query = !is_array($tax_query) ? [] : $tax_query;
                  $tax_query[] = [
                    'taxonomy'         => 'branch_activity',
                    'field'            => 'term_id',
                    'terms'            => (int) $abranch,
                    'include_children' => false
                  ];
                }

                if ( ! empty( $language ) ) {
                  $tax_query   = isset( $tax_query ) ? $tax_query : $query->get( 'tax_query' );
                  $tax_query = !is_array($tax_query) ? [] : $tax_query;
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
                  $tax_query = !is_array($tax_query) ? [] : $tax_query;
                  $tax_query[] = [
                    'taxonomy'         => 'software',
                    'field'            => 'term_id',
                    'terms'            => (int) $software,
                    'include_children' => false
                  ];
                }

                if ( ! empty( $s ) ) {
                  // Si une taxonomie n'st pas definie dans la recherche on ajoute du vide dans ce variable
                  $tax_query = isset($tax_query) ? $tax_query : '';
                  // Appliquer une filtre dans la recherche
                  add_filter('posts_where', function ( $where ) use ($tax_query) {
                    global $wpdb;
                    //global $wp_query;
                    $s = isset($_GET['s']) ? $_GET['s'] : '';
                    if (!is_admin()) {
                      $where .= " AND {$wpdb->posts}.ID IN (
                                      SELECT
                                        pt.ID
                                      FROM {$wpdb->posts} as pt
                                      INNER JOIN {$wpdb->postmeta} as pm1 ON (pt.ID = pm1.post_id)
                                      WHERE pt.post_type = 'candidate'
                                        AND pt.post_status = 'publish'
                                        AND (pt.ID IN (
                                          SELECT {$wpdb->postmeta}.post_id as post_id
                                          FROM {$wpdb->postmeta}
                                          WHERE {$wpdb->postmeta}.meta_key = 'activated' AND {$wpdb->postmeta}.meta_value = 1
                                        ))
                                        AND (pt.ID IN (
                                          SELECT {$wpdb->postmeta}.post_id as post_id
                                          FROM {$wpdb->postmeta}
                                          WHERE {$wpdb->postmeta}.meta_key = 'itjob_cv_hasCV' AND {$wpdb->postmeta}.meta_value = 1
                                        ))
                                        
                                        AND (pt.ID IN(
                                          SELECT trs.object_id as post_id
                                          FROM {$wpdb->terms} as terms
                                            INNER JOIN {$wpdb->term_relationships} as trs
                                            INNER JOIN {$wpdb->term_taxonomy} as ttx ON (trs.term_taxonomy_id = ttx.term_taxonomy_id)
                                          WHERE terms.term_id = ttx.term_id
                                          AND ttx.taxonomy = 'job_sought'
                                          AND terms.name LIKE '%{$s}%'
                                        ))
                                        OR (
                                            pt.ID IN (
                                            SELECT {$wpdb->postmeta}.post_id
                                            FROM {$wpdb->postmeta}
                                            WHERE {$wpdb->postmeta}.meta_key = '_old_job_sought' AND {$wpdb->postmeta}.meta_value LIKE '%{$s}%'
                                           ) 
                                            AND pt.ID IN (
                                            SELECT {$wpdb->postmeta}.post_id as post_id
                                            FROM {$wpdb->postmeta}
                                            WHERE {$wpdb->postmeta}.meta_key = 'activated' AND {$wpdb->postmeta}.meta_value = 1
                                           )
                                           AND pt.ID IN (
                                            SELECT {$wpdb->postmeta}.post_id as post_id
                                            FROM {$wpdb->postmeta}
                                            WHERE {$wpdb->postmeta}.meta_key = 'itjob_cv_hasCV' AND {$wpdb->postmeta}.meta_value = 1
                                          )
                                        )";
                      /**
                       * Si une taxonomie est definie on effectuer une recherche sur le titre de l'article seulement si on
                       * le trouve pas dans l'emploi (job_sought) et l'ancien empoi rechercher.
                       */
                      if (!empty($tax_query)) {
                        $where .= " OR (pt.ID IN (SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} 
                          WHERE {$wpdb->posts}.post_title LIKE '%{$s}%'
                          AND {$wpdb->posts}.ID IN (
                            SELECT {$wpdb->postmeta}.post_id
                            FROM {$wpdb->postmeta}
                            WHERE {$wpdb->postmeta}.meta_key = 'activated' AND {$wpdb->postmeta}.meta_value = 1
                          )
                        ) )";
                      }
                      $where .= ")"; //  .end AND
                      if (empty($tax_query)):
                        // Si une taxonomie n'est pas definie on ajoute cette condition dans la recherche
                        $where .= "  OR (
                                        {$wpdb->posts}.post_title LIKE  '%{$s}%'
                                        AND {$wpdb->posts}.post_type = 'candidate'
                                        AND {$wpdb->posts}.post_status = 'publish'
                                        AND ({$wpdb->posts}.ID IN (
                                          SELECT {$wpdb->postmeta}.post_id as post_id
                                          FROM {$wpdb->postmeta}
                                          WHERE {$wpdb->postmeta}.meta_key = 'activated' AND {$wpdb->postmeta}.meta_value = 1
                                        ))
                                      )";
                      endif;
                    }

                    return $where;
                  });

                } else {
                  // Meta query
                  if ( ! isset( $meta_query ) ) {
                    $meta_query = $query->get( 'meta_query' );
                  }
                  $meta_query = !is_array($meta_query) ? [] : $meta_query;
                  $meta_query[] = [
                    [
                      'key'     => 'activated',
                      'value'   => 1,
                      'compare' => '=',
                      'type'    => 'NUMERIC'
                    ],
                    [
                      'key'     => 'itjob_cv_hasCV',
                      'value'   => 1,
                      'compare' => '=',
                      'type'    => 'NUMERIC'
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
                }
                $query->query['s']      = '';
                $query->query_vars['s'] = '';
                BREAK;

            } // .end switch
            // FEATURE: Supprimer la condition de trouver le ou les mots dans le titre et le contenue

          } // .end if - search conditional
          else {
            // Filtrer les candidates ou les offers ou les entreprises
            // Afficher seulement les candidates ou les offres ou les entreprises activer
            if ( $post_type === 'candidate' || $post_type === 'offers' || $post_type === 'company' ):
              // Meta query
              if ( ! isset( $meta_query ) ) {
                $meta_query = $query->get( 'meta_query' );
              }
              $meta_query = !is_array($meta_query) ? [] : $meta_query;
              $meta_query[] = [
                'key'     => 'activated',
                'value'   => 1,
                'compare' => '=',
                'type'    => 'NUMERIC'
              ];
              if ( $post_type === 'candidate' ) {
                $meta_query[] = [
                  'key'     => 'itjob_cv_hasCV',
                  'value'   => 1,
                  'compare' => '=',
                  'type'    => 'NUMERIC'
                ];
              }

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
        $User = wp_get_current_user();
        $userRole = in_array( 'company', $User->roles ) || in_array( 'candidate', $User->roles );
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
          'name'          => 'Archive Content Top (Candidate)',
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
          'name'          => 'Archive Header Top (Candidate)',
          'id'            => 'cv-header',
          'description'   => 'Afficher des widgets en mode header',
          'before_widget' => '<div id="%1$s" class="widget %2$s">',
          'after_widget'  => '</div>'
        ) );

        register_sidebar( array(
          'name'          => 'Single Sidebar (Candidate)',
          'id'            => 'single-cv-sidebar',
          'description'   => 'Afficher des widgets sur le côté à droite',
          'before_widget' => '<div id="%1$s" class="widget %2$s">',
          'after_widget'  => '</div>'
        ) );

        // Register widget
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
      wp_register_script( 'angular-ui-select2',
        get_template_directory_uri() . '/assets/js/libs/angularjs/bower_components/angular-ui-select2/src/select2.js', ['jquery'], '1.7.2' );
      wp_register_script( 'ngFileUpload', get_template_directory_uri() . '/assets/js/libs/ngfileupload/ng-file-upload.min.js', [], '12.2.13', true );

      wp_register_script( 'tinymce',  VENDOR_URL . '/tinymce/tinymce.js', null, "4.5.9");
      wp_register_script( 'angular-ui-tinymce', VENDOR_URL . '/angular-ui-tinymce/dist/tinymce.min.js', null, '4.0.0' );

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
