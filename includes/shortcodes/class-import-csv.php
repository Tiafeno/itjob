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
use Underscore\Types\Arrays;

if ( ! class_exists( 'scImport' ) ) :
  class scImport {
    public function __construct() {

      add_action( 'init', function () {
        if ( current_user_can( "remove_users" ) ) {
          add_shortcode( 'itjob_import_csv', [ $this, 'sc_render_html' ] );
          add_action( 'wp_ajax_import_csv', [ &$this, 'import_csv' ] );
          add_action( 'wp_ajax_get_offer_data', [ &$this, 'get_offer_data' ] );
          add_action( 'wp_ajax_delete_offer_data', [ &$this, 'delete_offer_data' ] );
        }
      } );

      add_action( 'admin_init', function () {
        add_action( 'delete_offers', function ( $pId ) {
          delete_post_meta( $pId, '__offer_job_sought' );
          delete_post_meta( $pId, '__offer_publish_date' );
          delete_post_meta( $pId, '__offer_create_date' );
          delete_post_meta( $pId, '__offer_count_view' );
          delete_post_meta( $pId, '__id_offer' );
        }, 10 );
      } );
    }

    public function import_csv() {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( "Accès refuser" );
      }
      $type         = Http\Request::getValue( 'entry_type' );
      $content_type = Http\Request::getValue( 'content_type' );
      switch ( $type ) {
        case 'taxonomy':
          $taxonomy = $content_type;
          $this->add_term( $taxonomy );
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
      $fileLink = get_template_directory() . '/includes/class/import/data/oc29_offers.php';
      if ( \file_exists( $fileLink ) ) {
        include $fileLink;
        $rows = &$oc29_offers;
        wp_send_json_success( $rows );
      } else {
        wp_send_json_error( "Le fichier backup n'existe pas" );
      }

    }

    public function delete_offer_data() {
      $args    = [
        'posts_per_page' => - 1,
        'post_type'      => 'offers',
        'post_status'    => [ 'pending', 'publish' ]
      ];
      $offers  = get_posts( $args );
      $results = [];
      foreach ( $offers as $offer ) {
        $response = wp_delete_post( $offer->ID );
        array_push( $results, $response );
      }

      wp_send_json_success( $results );
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
      $row = Http\Request::getValue( 'column' );
      $row = json_decode( $row );
      switch ( $taxonomy ) {
        // Ajouter la ville d'une code postal
        case 'city':
          $parent      = $row[0];
          $child       = $row[1];
          $parent_term = term_exists( trim( $parent ), 'city' );
          if ( 0 === $parent_term || null === $parent_term || ! $parent_term ) {
            $parent_term = wp_insert_term( $parent, $taxonomy, [ 'slug' => $parent ] );
            if ( is_wp_error( $parent_term ) ) {
              wp_send_json_error( $parent_term->get_message() );
            }
          }
          $child_term = term_exists( trim( $child ), 'city', $parent_term['term_id'] );
          if ( 0 === $child_term || null === $child_term || ! $child_term ) {
            $child_term = wp_insert_term( $child, $taxonomy, [ 'parent' => $parent_term['term_id'] ] );
            if ( is_wp_error( $child_term ) ) {
              wp_send_json_error( $child_term->get_message() );
            }
          }
          wp_send_json_success( "({$parent}) - {$child}" );
          break;
        case 'software':
          $titles = &$row;
          if ( is_array( $titles ) ) {
            foreach ( $titles as $title ) {
              // return bool
              $this->insert_term( $title, $taxonomy );
            }
            wp_send_json_success( "Ajouter avec succès" );
          } else {
            wp_send_json_error( "Un probléme detecter, Erreur:" . $titles );
          }

          break;
        case 'job_sought':
          $job = &$row;
          list( $id, $title, $status ) = $job;
          $id = (int) $id;
          if ( ! is_numeric( $id ) ) {
            wp_send_json_success( "Passer aux colonnes suivantes" );
          }
          if ( ! is_array( $title ) && (int) $status ) {
            $term = $this->insert_term( $title, $taxonomy );
            if ( $term ) {
              update_term_meta( $term['term_id'], '__job_id', $id );
              wp_send_json_success( "Emploie ajouter avec succès" );
            }
          }
          wp_send_json_success( "Emploi désactiver par l'administrateur, impossible de l'ajouter" );
          break;
        default:

          break;
      }
    }

    protected function insert_term( $name, $taxonomy ) {
      $term = term_exists( $name, $taxonomy );
      if ( 0 === $term || null === $term || ! $term ) {
        $term = wp_insert_term( ucfirst( $name ), $taxonomy );
        if ( is_wp_error( $term ) ) {
          $term = get_term_by( 'name', $name );
          if ( ! $term ) {
            return false;
          }
        }
      }
      // Activer le taxonomie
      update_term_meta( $term['term_id'], "activated", 1 );

      return $term;
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
                $up_user = wp_update_user( [ 'user_login' => 'user' . $insert_company, 'ID' => $user_id ] );
                if ( is_wp_error( $up_user ) ) {
                  wp_send_json_error( $up_user->get_error_message() );
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
                $up_user = wp_update_user( [ 'user_login' => 'user' . $insert_candidate, 'ID' => $user_id ] );
                if ( is_wp_error( $up_user ) ) {
                  wp_send_json_error( $up_user->get_error_message() );
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
          $old_user_id = (int) $rows_object->id_user;
          // Retourne false or WP_User
          if ( ! $old_user_id ) {
            wp_send_json_success( "Passer à la colonne suivante" );
          }
          $User = $Helper->has_user( $old_user_id );
          if ( $User ) {
            if ( in_array( 'candidate', $User->roles ) ) {
              $candidatePost = $Helper->get_candidate_by_email( $User->user_email );
              if ( ! $candidatePost ) {
                wp_send_json_error( "Le candidat n'existe pas email: {$User->user_email}" );
              }

              // Update ACF field
              $condition = $rows_object->greeting === 'NULL' || empty( $rows_object->greeting );
              $greeting  = $condition ? '' : $rows_object->greeting;
              update_field( 'itjob_cv_greeting', $greeting, $candidatePost->ID );
              update_field( 'itjob_cv_firstname', $rows_object->first_name, $candidatePost->ID );
              update_field( 'itjob_cv_lastname', $rows_object->last_name, $candidatePost->ID );
              update_field( 'itjob_cv_address', $rows_object->address, $candidatePost->ID );

              $birthday         = \DateTime::createFromFormat( "d/m/Y", $rows_object->birthday_date );
              $acfBirthdayValue = $birthday->format( 'Ymd' );
              update_field( 'itjob_cv_birthdayDate', $acfBirthdayValue, $candidatePost->ID );

              update_field( 'itjob_cv_notifEmploi', [ 'notification' => $rows_object->notification_job ], $candidatePost->ID );
              update_field( 'itjob_cv_notifFormation', [ 'notification' => $rows_object->notification_training ], $candidatePost->ID );
              update_field( 'activated', $rows_object->activated, $candidatePost->ID );
              update_field( 'itjob_cv_newsletter', $rows_object->newsletter, $candidatePost->ID );
              $values     = [];
              $cellphones = explode( ',', $rows_object->phone );
              foreach ( $cellphones as $phone ) {
                $values[] = [ 'number' => preg_replace( '/\s+/', '', trim( $phone ) ) ];
              }
              update_field( 'itjob_cv_phone', $values, $candidatePost->ID );

              // Meta
              update_post_meta( $candidatePost->ID, '__cv_audition', $rows_object->audition );
              update_post_meta( $candidatePost->ID, '__cv_find_job', $rows_object->find_job );
              update_post_meta( $candidatePost->ID, '__cv_id_demandeur', $rows_object->id_demandeur );
              // Ajouter term region
              $term = term_exists( $rows_object->region, 'region' );
              if ( 0 === $term || null === $term || ! $term ) {
                $term = wp_insert_term( $rows_object->region, 'region' );
                if ( is_wp_error( $term ) ) {
                  $term = get_term_by( 'name', $rows_object->region );
                }
              }
              wp_set_post_terms( $candidatePost->ID, [ $term['term_id'] ], 'region' );
              wp_send_json_success( "Information utilisateur mis à jours avec succès" );
            }
          } else {
            wp_send_json_success( "L'utilisateur n'existe pas, utilisateur id: {$rows_object->id_user}" );
          }
          break;

        // TODO: Ajouter les CV
        case 'user_candidate_cv':
          // Publier le CV
          list(
            $id_cv,
            $id_demandeur, // get candidat post with __cv_id_demandeur (meta)
            $drive_licences,
            $emploi_rechercher,
            $langues,
            $statut,
            $reference,
            $certificat,
            $date_creation,
            $date_publish ) = $lines;
          $id_cv        = (int) $id_cv;
          $id_demandeur = (int) $id_demandeur;
          if ( ! $id_cv ) {
            wp_send_json_success( "Passer à la colonne suivante" );
          }
          $post_ids = get_posts( [
            'meta_key'    => '__cv_id_demandeur',
            'meta_value'  => $id_demandeur,
            'post_status' => [ 'publish', 'pending' ],
            'post_type'   => 'candidate',
            'fields'      => 'ids',
          ] );
          if ( is_array( $post_ids ) && ! empty( $post_ids ) ) {
            $candidat_id   = $post_ids[ count( $post_ids ) - 1 ];
            $jobs          = $this->get_jobs();
            $driveLicences = [
              [ 'label' => 'A`', 'value' => 0 ],
              [ 'label' => 'A', 'value' => 1 ],
              [ 'label' => 'B', 'value' => 2 ],
              [ 'label' => 'C', 'value' => 3 ],
              [ 'label' => 'D', 'value' => 4 ]
            ];

            update_post_meta( $candidat_id, '__id_cv', $id_cv );
            $dls      = explode( ",", $drive_licences );
            $dlValues = [];
            foreach ( $dls as $dl ) {
              if ( empty( $dl ) ) {
                continue;
              }
              $result     = Arrays::find( $driveLicences, function ( $licence ) use ( $dl ) {
                return $licence['label'] == $dl;
              } );
              $dlValues[] = $result['value'];
            }
            // Permis de conduire
            update_field( 'itjob_cv_driveLicence', $dlValues, $candidat_id );

            // Emploi recherche
            $emplois         = explode( ",", $emploi_rechercher );
            $term_emploi_ids = [];
            foreach ( $emplois as $emploi ) {
              if ( empty( $emploi ) ) {
                continue;
              }
              $jobResult = Arrays::find( $jobs, function ( $job ) use ( $emploi, &$term_emploi_ids ) {
                return $job->__old_job_id == (int) $emploi;
              } );
              if ( ! empty( $jobResult ) || ! isset( $jobResult ) || ! $jobResult ) {
                $term_emploi_ids[] = $jobResult->term_id;
              }
            }
            wp_set_post_terms( $candidat_id, $term_emploi_ids, 'job_sought' );

            unset( $jobs );
            // Langue
            $languages  = explode( ',', $langues );
            $languages = Arrays::reject($languages, function ($lang) { return !empty($lang) || $lang !== '';});
            $languages  = array_map( function ( $langue ) {
              return strtolower( trim( $langue ) );
            }, $languages );
            $langValues = [];
            foreach ( $languages as $language ) {
              if ( empty( $language ) ) {
                continue;
              }
              $langTerm = term_exists( $language, 'language' );
              if ( 0 === $langTerm || null === $langTerm || ! $langTerm ) {
                $langTerm = wp_insert_term( ucfirst( $language ), 'language' );
                if ( is_wp_error( $langTerm ) ) {
                  $langTerm = get_term_by( 'name', $language );
                }
              }
              $langValues[] = $langTerm['term_id'];
            }
            wp_set_post_terms( $candidat_id, $langValues, 'language' );

            update_field( 'itjob_cv_status', (int) $statut, $candidat_id );
            update_field( 'activated', 1, $candidat_id );
            update_field( 'itjob_cv_hasCV', 1, $candidat_id );

            update_post_meta( $candidat_id, '__certification', $certificat );
            update_post_meta( $candidat_id, '__date_publish', $date_publish );

            // Mettre à jour le titre du post
            $create_date    = \DateTime::createFromFormat( "d/m/Y H:i", $date_creation );
            $publish        = $create_date->format( 'Y-m-d H:i:s' );
            $argc           = [
              'ID'          => $candidat_id,
              'post_title'  => trim( $reference ),
              'post_date'   => date($publish),
              'post_status' => 'publish'
            ];
            $update_results = wp_update_post( $argc );
            if ( is_wp_error( $update_results ) ) {
              wp_send_json_error( $update_results->get_error_message() );
            }

            wp_send_json_success( "CV ajouté avec succès ID:{$candidat_id}" );
          }
          wp_send_json_success( "Utilisateur abscent. Ancien CV:{$id_cv}" );
          break;
      }
    }

    private function get_jobs() {
      $args  = [ 'taxonomy' => 'job_sought', 'hide_empty' => false, 'parent' => 0, 'posts_per_page' => - 1 ];
      $terms = get_terms( $args );
      foreach ( $terms as $term ) {
        $old_job_sought_id  = get_term_meta( $term->term_id, '__job_id', true );
        $term->__old_job_id = (int) $old_job_sought_id;
      }

      return $terms;
    }

    // Ajouter les anciens offres
    protected function add_offer() {
      if ( ! is_user_logged_in() ) {
        return;
      }
      $column = Http\Request::getValue( 'column' );
      $row    = json_decode( $column );
      $Helper = new JHelper();
      $obj    = (object) $row;

      $User = $Helper->has_user( (int) $obj->id_user );
      if ( ! $User && ! in_array( 'company', $User->roles ) ) {
        wp_send_json_success( "Utilisateur non inscrit, ID:" . $obj->id_user );
      }

      $args = [
        'post_type'   => 'offers',
        'post_status' => [ 'publish', 'pending' ],
        'meta_query'  => array(
          [
            'key'   => '__id_offer',
            'value' => (int) $obj->id
          ]
        )
      ];
      // Verifier si l'offre est déja publier ou ajouter dans le site
      $offers_exists = get_posts( $args );
      if ( is_array( $offers_exists ) && ! empty( $offers_exists ) ) {
        wp_send_json_success( "Offre déja publier" );
      }
      // Ajouter une offre
      $publish_date = strtotime( $obj->published );
      $publish      = date( 'Y-m-d H:i:s', $publish_date );

      $args    = [
        "post_type"   => 'offers',
        "post_title"  => $obj->poste_a_pourvoir,
        "post_status" => (int) $obj->status ? 'publish' : 'pending',
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

        update_field( 'activated', (int) $obj->status, $post_id );
        update_field( 'itjob_offer_contrattype', (int) $obj->type_contrat, $post_id );
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

        $created_date = strtotime( $obj->created );
        $created      = date( 'Y-m-d H:i:s', $created_date );
        update_post_meta( $post_id, '__offer_create_date', $created );

        update_post_meta( $post_id, '__offer_count_view', (int) $obj->nbr_vue );
        update_post_meta( $post_id, '__id_offer', (int) $obj->id );

        // Ajouter term region
        $term = term_exists( $obj->poste_base_a, 'region' );
        if ( 0 === $term && null === $term ) {
          $term = wp_insert_term( $obj->poste_base_a, 'region' );
          if ( is_wp_error( $term ) ) {
            $term = get_term_by( 'name', $obj->poste_base_a );
          }
        }
        wp_set_post_terms( $post_id, [ $term['term_id'] ], 'region' );
        wp_send_json_success( [ 'msg' => "Offre ajouter avec succès", 'term' => $term ] );
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
      if ( ! is_user_logged_in() && ! current_user_can( 'remove_users' ) ) {
        // Access refuser au public
        return "<p class='align-center'>Accès interdit à toute personne non autorisée</p>";
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