<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;
use includes\classes\import\importUser;
use includes\object\JHelper;
use includes\post\Candidate;
use includes\post\Company;

if ( ! class_exists( 'scImport' ) ) :
  class scImport {
    public function __construct() {
      add_shortcode( 'itjob_import_csv', [ $this, 'sc_render_html' ] );
      add_action( 'wp_ajax_import_csv', [ &$this, 'import_csv' ] );
      add_action( 'wp_ajax_get_offer_data', [ &$this, 'get_offer_data' ] );
      add_action( 'wp_ajax_delete_offer_data', [ &$this, 'delete_offer_data' ] );

      add_action( 'init', function () {

      } );

      add_action( 'admin_init', function () {
        add_action( 'delete_offers', function ($pId) {
          delete_post_meta($pId, '__offer_job_sought');
          delete_post_meta($pId, '__offer_publish_date');
          delete_post_meta($pId, '__offer_create_date');
          delete_post_meta($pId, '__offer_count_view');
          delete_post_meta($pId, '__id_offer');
        }, 10 );
      });
    }

    public function import_csv() {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( "Accès refuser" );
      }
      $type         = Http\Request::getValue( 'entry_type' );
      $content_type = Http\Request::getValue( 'content_type' );
      switch ( $type ) {
        case 'taxonomy':
          $this->add_term( $content_type );
          break;
        case 'user':
          $this->add_user( $content_type );
          break;
        case 'offers':
          $this->add_offer();
          break;
      }
    }

    /**
     * Récuperer les anciens offres
     */
    public function get_offer_data() {
      include get_template_directory() . '/includes/class/import/data/oc29_offers.php';
      $rows = &$oc29_offers;
      wp_send_json_success($rows);
    }

    public function delete_offer_data() {
      $args = [
        'posts_per_page' => -1,
        'post_type' => 'offers',
        'post_status' => ['pending', 'publish']
      ];
      $offers = get_posts($args);
      $results = [];
      foreach ($offers as $offer) {
        $response = wp_delete_post($offer->ID);
        array_push($results, $response);
      }

      wp_send_json_success($results);
    }

    /**
     * Ajouter des terms dans une taxonomie
     *
     * @param string $taxonomy
     */
    protected function add_term( $taxonomy ) {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( "Accès refuser" );
      }
      switch ( $taxonomy ) {
        // Ajouter la ville d'une code postal
        case 'city':
          $row         = Http\Request::getValue( 'column' );
          $row         = json_decode( $row );
          $parent      = $row[0];
          $child       = $row[1];
          $parent_term = term_exists( $parent, $taxonomy );
          if ( 0 == $parent_term || is_null( $parent_term ) ) {
            $parent_term = wp_insert_term( $parent, $taxonomy, [ 'slug' => $parent ] );
          }
          $child_term = term_exists( $child, $taxonomy, $parent_term['term_id'] );
          if ( 0 == $child_term || is_null( $child_term ) ) {
            $child_term = wp_insert_term( $child, $taxonomy, [ 'parent' => $parent_term['term_id'] ] );
          }
          wp_send_json_success( "({$parent_term['term_id']}) {$child_term['term_id']}" );
          break;
        case 'software':

          break;
        default:

          break;
      }
    }

    // FEATURED: Ajouter les utilisateurs du site itjobmada
    protected function add_user( $content_type ) {
      if ( ! is_user_logged_in() ) {
        return false;
      }
      $Helper = new JHelper();
      $lines  = Http\Request::getValue( 'column' );
      $lines  = json_decode( $lines );
      switch ( $content_type ) {
        case 'user':
          $rows        = [
            'id_user'     => (int) $lines[0],
            'name'        => $lines[1],
            'seoname'     => $lines[2],
            'email'       => $lines[3],
            'password'    => $lines[4],
            'change_pwd'  => (int) $lines[5],
            'description' => $lines[6],
            'status'      => (int) $lines[7],
            'id_role'     => (int) $lines[8],
            'created'     => $lines[9],
            'last_login'  => $lines[10],
            'subscriber'  => (int) $lines[11]
          ];
          $rows        = array_map( function ( $row ) {
            return trim( $row );
          }, $rows );
          $rows_object = (object) $rows;
          $Helper      = new JHelper();
          if ( ! $rows_object->status ) {
            $user = $Helper->has_user( $rows_object->id_user );
            if ( $user ) {
              $objPost = in_array( 'company', $user->roles ) ? Company::get_company_by( $user->ID ) : Candidate::get_candidate_by( $user->ID );
              wp_delete_post( $objPost->getId() );
              wp_delete_user( $user->ID );

              wp_send_json_success( "Mise à jours de la base de donnée avec succès" );
            }
            wp_send_json_success( "Passer sur une autres colonne, utilisateur pour status désactiver" );
          }
          if ( ! $rows_object->id_role ) {
            wp_send_json_success( "Passer sur une autres colonne" );
          }
          $userExist = get_user_by( 'email', $rows_object->email );
          if ( $userExist ) {
            wp_send_json_success( "L'utilisateur existe déja" );
          }
          switch ( $rows_object->id_role ) :
            case 11:
            case 12:
            case 14:
            case 15:
            case 16:
            case 18:
            case 19:
              // Entreprise
              // Ajouter une entreprise
              $args = [
                "user_pass"    => $rows_object->password,
                "user_login"   => "user{$rows_object->id_user}",
                "user_email"   => $rows_object->email,
                "display_name" => $rows_object->name,
                "first_name"   => $rows_object->name,
                "role"         => 'company'
              ];
              if ( username_exists( "user{$rows_object->id_user}" ) ) {
                wp_send_json_success( "Cet identifiant existe déjà !" );
              }
              $user_id = wp_insert_user( $args );
              if ( ! is_wp_error( $user_id ) ) {
                // Stocker les anciens information dans des metas
                $importUser = new importUser( $user_id );
                $importUser->update_user_meta( $rows_object );

                // Ajouter une post type 'company'
                $argc           = [
                  'post_title'  => $rows_object->name,
                  'post_status' => 'publish',
                  'post_type'   => 'company',
                  'post_author' => $user_id
                ];
                $insert_company = wp_insert_post( $argc );
                if ( is_wp_error( $insert_company ) ) {
                  wp_send_json_error( $insert_company->get_error_message() );
                }

                // Mettre à jour le nom login
                $up_user = wp_update_user(['user_login' => 'user' . $insert_company, 'ID' => $user_id]);
                if (is_wp_error($up_user)) {
                  wp_send_json_error($up_user->get_error_message());
                }

                $id_company = (int) $insert_company;
                update_field( 'itjob_company_email', $rows_object->email, $id_company );
                update_field( 'itjob_company_name', $rows_object->name, $id_company );
                update_field( 'itjob_company_newsletter', $rows_object->subscriber, $id_company );

                $user = new \WP_User( $user_id );
                get_password_reset_key( $user );
                wp_send_json_success( [ 'msg' => "Utilisateur ajouter avec succès", 'utilisateur' => $user ] );

              } else {
                wp_send_json_error( $user_id->get_error_message() );
              }
              break;

            case 13:
            case 17:
              // Candidate
              $names      = $rows_object->name;
              $names      = explode( ' ', $names );
              $first_name = $names[ count( $names ) - 1 ];
              $last_name  = '';
              array_walk( $names, function ( $name, $key ) use ( $names, &$last_name ) {
                if ( count( $names ) - 1 < $key ) {
                  $last_name .= $name . " ";
                }

                return $name;
              } );
              $args = [
                "user_pass"    => $rows_object->password,
                "user_login"   => "user{$rows_object->id_user}",
                "user_email"   => $rows_object->email,
                "display_name" => $rows_object->name,
                "first_name"   => $first_name,
                "last_name"    => $last_name,
                "role"         => 'candidate'
              ];
              if ( username_exists( "user{$rows_object->id_user}" ) ) {
                wp_send_json_success( "Cet identifiant existe déjà !" );
              }
              $user_id = wp_insert_user( $args );
              if ( ! is_wp_error( $user_id ) ) {
                // Stocker les anciens information dans des metas
                $importUser = new importUser( $user_id );
                $importUser->update_user_meta( $rows_object );

                // Ajouter une post type 'candidate'
                $argc             = [
                  'post_title'  => $rows_object->name,
                  'post_status' => 'pending',
                  'post_type'   => 'candidate',
                  'post_author' => $user_id
                ];
                $insert_candidate = wp_insert_post( $argc );
                if ( is_wp_error( $insert_candidate ) ) {
                  wp_send_json_error( $insert_candidate->get_error_message() );
                }

                // Mettre à jour le nom login
                $up_user = wp_update_user(['user_login' => 'user' . $insert_candidate, 'ID' => $user_id]);
                if (is_wp_error($up_user)) {
                  wp_send_json_error($up_user->get_error_message());
                }

                $id_candidate = (int) $insert_candidate;
                update_field( 'itjob_cv_email', $rows_object->email, $id_candidate );
                update_field( 'itjob_cv_firstname', $first_name, $id_candidate );
                update_field( 'itjob_cv_lastname', $last_name, $id_candidate );
                update_field( 'itjob_cv_newsletter', $rows_object->subscriber, $id_candidate );

                // Voir fichier ~ itjob_demandeur_cvs.csv
                /*$up_candidate = wp_update_post( [ 'post_title' => 'CV' . $id_candidate, 'ID' => $id_candidate ] );
                if ( is_wp_error( $up_candidate ) ) {
                  wp_send_json_error( $up_candidate->get_error_message() );
                }*/

                $user = new \WP_User( $user_id );
                get_password_reset_key( $user );
                wp_send_json_success( [ 'msg' => "Utilisateur ajouter avec succès", 'utilisateur' => $user ] );
              } else {
                wp_send_json_error( $user_id->get_error_message() );
              }
              break;
            default:
              wp_send_json_success( "Impossible d'ajouter ce type de compte: " . $rows_object->id_role );
              break;
          endswitch;
          break;

        case 'user_company':
          $rows = [
            'id_user'      => (int) $lines[0],
            'company_name' => $lines[1],
            'address'      => $lines[2],
            'nif'          => $lines[3],
            'stat'         => $lines[4],
            'phone'        => $lines[5],
            'greeting'     => $lines[6],
            'first_name'   => $lines[7],
            'last_name'    => $lines[8],
            'cellphones'   => $lines[9],
            'email'        => $lines[10],
            'job_sought'   => $lines[11],
            'newsletter'   => (int) $lines[12],
            'notification' => (int) $lines[13],
            'alert'        => $lines[14],
            'create'       => $lines[15]
          ];

          $rows        = array_map( function ( $row ) {
            return $row;
          }, $rows );
          $rows_object = (object) $rows;
          if ( (int) $rows_object->id_user === 0 ) {
            wp_send_json_success( "Passer sur une autre colonne" );
          }
          // Retourne false or WP_User
          $User = $Helper->has_user( (int) $rows_object->id_user );
          if ( $User && in_array( 'company', $User->roles ) ) {
            $Company = Company::get_company_by( $User->ID );
            if ( function_exists( 'update_field' ) ) {
              update_field( 'itjob_company_address', $rows_object->address, $Company->getId() );
              update_field( 'itjob_company_nif', $rows_object->nif, $Company->getId() );
              update_field( 'itjob_company_stat', $rows_object->stat, $Company->getId() );
              update_field( 'itjob_company_name', $rows_object->first_name . ' ' . $rows_object->last_name, $Company->getId() );
              update_field( 'itjob_company_newsletter', $rows_object->newsletter, $Company->getId() );
              update_field( 'itjob_company_notification', $rows_object->notification === '0' || empty( $rows_object->notification ) ? '' : $rows_object->notification, $Company->getId() );
              update_field( 'itjob_company_phone', $rows_object->phone, $Company->getId() );
              update_field( 'itjob_company_alerts', $rows_object->alert === '0' ? '' : $rows_object->alert, $Company->getId() );
              update_field( 'itjob_company_greeting', $rows_object->greeting, $Company->getId() );
              update_field( 'activated', 1, $Company->getId() );

              $values     = [];
              $cellphones = explode( ';', $rows_object->cellphones );
              foreach ( $cellphones as $phone ) {
                $values[] = [ 'number' => $phone ];
              }
              update_field( 'itjob_company_cellphone', $values, $Company->getId() );
            }
            // Enregistrer la date de creation original & la secteur d'activité
            update_post_meta( $Company->getId(), '__create', $rows_object->create );
            // Ancien valeur du secteur d'activité
            update_post_meta( $Company->getId(), '__company_job_sought', $rows_object->job_sought );

            wp_send_json_success( [ 'msg' => "L'entreprise ajouter avec succès", 'data' => $Company ] );
          } else {
            wp_send_json_success( "L'utilisateur est refuser de s'inscrire ID:{$rows_object->id_user}" );
          }
          break;

        case 'user_candidate_information':
          $rows        = [
            'id_user'               => (int) $lines[0], // Ne pas enregistrer
            'audition'              => (int) $lines[1], // Ajouter ~ meta
            'find_job'              => (int) $lines[2], // Ajouter ~ meta
            'greeting'              => $lines[3], // Ajouter
            'first_name'            => $lines[4], // Ajouter
            'last_name'             => $lines[5], // Ajouter
            'email'                 => $lines[6], // Déja ajouter depuis utilisateur
            'address'               => $lines[7], // Ajouter
            'region'                => trim( $lines[8] ), // Ajouter
            'phone'                 => $lines[9], // Ajouter
            'birthday_date'         => $lines[10], // Ajouter
            'notification_job'      => (int) $lines[11], // Ajouter
            'notification_training' => (int) $lines[12], // Ajouter
            'activated'             => (int) $lines[13], // Ajouter
            'newsletter'            => (int) $lines[14], // Ajouter
            'id_demandeur'          => (int) $lines[15] // Ajouter
          ];
          $rows        = array_map( function ( $row ) {
            return trim( $row );
          }, $rows );
          $rows_object = (object) $rows;
          // Retourne false or WP_User
          $User = $Helper->has_user( $rows_object->id_user );
          if ( $User ) {
            if ( in_array( 'candidate', $User->roles ) ) {
              $candidatePost = $Helper->get_candidate_by_email( $User->user_email );
              if ( ! $candidatePost ) {
                wp_send_json_error( "" );
              }

              // Update ACF field
              $greeting = $rows_object->greeting === 'NULL' ? '' : $rows_object->greeting;
              if ( ! empty( $greeting ) ) {
                update_field( 'itjob_cv_greeting', $greeting, $candidatePost->ID );
              }
              update_field( 'itjob_cv_firstname', $rows_object->first_name, $candidatePost->ID );
              update_field( 'itjob_cv_lastname', $rows_object->last_name, $candidatePost->ID );
              update_field( 'itjob_cv_address', $rows_object->address, $candidatePost->ID );

              $birthday         = new \DateTime( $rows_object->birthday_date );
              $acfBirthdayValue = $birthday->format( 'Ymd' );
              update_field( 'itjob_cv_birthdayDate', $acfBirthdayValue, $candidatePost->ID );

              update_field( 'itjob_cv_notifEmploi', [ 'notification' => $rows_object->notification_job ], $candidatePost->ID );
              update_field( 'itjob_cv_notifFormation', [ 'notification' => $rows_object->notification_training ], $candidatePost->ID );
              update_field( 'activated', $rows_object->activated, $candidatePost->ID );
              update_field( 'itjob_cv_newsletter', $rows_object->newsletter, $candidatePost->ID );

              $values     = [];
              $cellphones = explode( ',', $rows_object->phone );
              foreach ( $cellphones as $phone ) {
                $values[] = [ 'number' => $phone ];
              }
              update_field( 'itjob_cv_phone', $values, $candidatePost->ID );

              // Meta
              update_post_meta( $candidatePost->ID, '__cv_audition', $rows_object->audition );
              update_post_meta( $candidatePost->ID, '__cv_find_job', $rows_object->find_job );
              update_post_meta( $candidatePost->ID, '__cv_find_job', $rows_object->find_job );
              update_post_meta( $candidatePost->ID, '__cv_id_demandeur', $rows_object->id_demandeur );
              // Ajouter term region
              $term = term_exists( $rows_object->region, 'region' );
              if ( 0 !== $term && null !== $term ) {
                $term = wp_insert_term( $rows_object->region, 'region' );
              }
              wp_set_post_terms( $candidatePost->ID, [ $term['term_id'] ], 'region' );
            }
          }
          break;

          // TODO: Ajouter les CV
        case 'user_candidate_cv':
          // Publier le CV
          $rows = [
            'id_cv'           => $lines[0],
            'id_demandeur'    => $lines[1],
            'driver_licences' => $lines[2],
            'find_job_id'     => $lines[3],
            'software_id'     => $lines[4],
            'languages'       => $lines[5],
            'title'           => $lines[6], // Reference ici, ajouter pour
          ];
          break;
      }
    }

    // Ajouter les anciens offres
    protected function add_offer() {
      if ( ! is_user_logged_in() ) {
        return;
      }
      $column = Http\Request::getValue('column');
      $row = json_decode($column);
      $Helper = new JHelper();
      $obj  = (object) $row;

      $User = $Helper->has_user( (int)$obj->id_user );
      if (!$User && !in_array('company', $User->roles)) {
        wp_send_json_success("Utilisateur non inscrit, ID:" . $obj->id_user);
      }

      $args = [
        'post_type' => 'offers', 
        'post_status' => ['publish', 'pending'],
        'meta_query' => array([
          'key' => '__id_offer',
          'value' => (int)$obj->id
        ]
      )];
      $offers_exists = get_posts($args);
      if (is_array($offers_exists) && !empty($offers_exists)) {
        wp_send_json_success("Offre déja publier");
      }
      // Ajouter une offre
      $publish_date = strtotime($obj->published);
      $publish = date('Y-m-d H:i:s', $publish_date);
      
      $args    = [
        "post_type"   => 'offers',
        "post_title"  => $obj->poste_a_pourvoir,
        "post_status" => (int)$obj->status ? 'publish' : 'pending',
        'post_author' => $User->ID,
        'post_date'   => $publish
      ];
      $post_id = wp_insert_post( $args );
      if ( ! is_wp_error( $post_id ) ) {
        update_field( 'itjob_offer_post', $obj->poste_a_pourvoir, $post_id );
        update_field( 'itjob_offer_reference', $obj->reference, $post_id );

        $date_limit   = new \DateTime( $obj->date_limit_candidature );
        $acfDateLimit = $date_limit->format( 'Ymd' );
        update_field( 'itjob_offer_datelimit', $acfDateLimit, $post_id );

        update_field( 'activated', (int)$obj->status, $post_id );
        update_field( 'itjob_offer_contrattype', (int)$obj->type_contrat, $post_id );
        update_field( 'itjob_offer_profil', html_entity_decode( htmlspecialchars_decode( $obj->profil_recherche ) ), $post_id );
        update_field( 'itjob_offer_mission', html_entity_decode( htmlspecialchars_decode( $obj->mission ) ), $post_id );
        update_field( 'itjob_offer_otherinformation', html_entity_decode( htmlspecialchars_decode( $obj->autre_info ) ), $post_id );

        if ( in_array( 'company', $User->roles ) ) {
          $Company = Company::get_company_by( $User->ID );
          update_field( 'itjob_offer_company', $Company->getId(), $post_id );
          $company_job_sought = get_post_meta( $Company->getId(), '__company_job_sought', true );
        }

        update_post_meta( $post_id, '__offer_job_sought', $company_job_sought ); // Secteur d'activité
        update_post_meta( $post_id, '__offer_publish_date', $publish );

        $created_date = strtotime($obj->created);
        $created = date('Y-m-d H:i:s', $created_date);
        update_post_meta( $post_id, '__offer_create_date', $created );

        update_post_meta( $post_id, '__offer_count_view', (int)$obj->nbr_vue );
        update_post_meta( $post_id, '__id_offer', (int)$obj->id );

        // Ajouter term region
        $term = term_exists( $obj->poste_base_a, 'region' );
        if ( 0 === $term && null === $term ) {
          $term = wp_insert_term( $obj->poste_base_a, 'region' );
          if (is_wp_error($term)) {
            $term = get_term_by('name', $obj->poste_base_a);
          }
        }
        wp_set_post_terms( $post_id, [ $term['term_id'] ], 'region' );
        wp_send_json_success( ['msg' => "Offre ajouter avec succès", 'term' => $term] );
      } else {
        wp_send_json_error( "Une erreur s'est produite pendant l'ajout :" . $post_id->get_error_message() );
      }

    }

    public function sc_render_html( $atts, $content = "" ) {
      global $Engine, $itJob;
      extract(
        shortcode_atts(
          array(
            'title' => ''
          ),
          $atts
        )
      );
      if ( ! is_user_logged_in() && ! current_user_can( 'delete_users' ) ) {
        // Access refuser au public
      }

      wp_enqueue_script( 'import-csv', get_template_directory_uri() . '/assets/js/app/import/importcsv.js', [
        'angular',
        'angular-ui-route',
        'angular-messages',
        'angular-animate',
        'angular-aria',
        'papaparse'
      ], $itJob->version, true );
      wp_localize_script( 'import-csv', 'importOptions', [
        'ajax_url'     => admin_url( "admin-ajax.php" ),
        'partials_url' => get_template_directory_uri() . '/assets/js/app/import/partials',
      ] );
      try {
        /** @var STRING $title */
        return $Engine->render( '@SC/import-csv.html.twig', [
          'title' => $title
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        return $e->getRawMessage();
      }
    }
  }
endif;

return new scImport();