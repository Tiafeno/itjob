<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;
use includes\classes\import\importUser;
use includes\post\Company;

if ( ! class_exists( 'scImport' ) ) :
  class scImport {
    public function __construct() {
      add_shortcode( 'itjob_import_csv', [ $this, 'sc_render_html' ] );
      add_action( 'wp_ajax_import_csv', [ &$this, 'import_csv' ] );

      add_action('init', function () {

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
      }
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
      }
      wp_send_json_success( "En construction ..." );
    }

    // TODO: Ajouter les utilisateurs du site itjobmada
    protected function add_user( $content_type ) {
      if ( ! is_user_logged_in() ) {
        return false;
      }
      $lines = Http\Request::getValue( 'column' );
      $lines = json_decode( $lines );
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
          if ( ! is_numeric( $rows_object->id_role ) ) {
            wp_send_json_error( "Utilisateur non valide, id_role: {$rows_object->id_role}" );
          }
          $userExist = get_user_by( 'email', $rows_object->email );
          if ( $userExist ) {
            wp_send_json_error( "L'utilisateur existe déja" );
          }
          switch ( $rows_object->id_role ):
            case 11:
            case 12:
            case 14:
            case 15:
            case 16:
            case 18:
            case 19:
              // Entreprise
              // Ajouter une entreprise
              $args    = [
                "user_pass"    => $rows_object->password,
                "user_login"   => $rows_object->seoname,
                "user_email"   => $rows_object->email,
                "display_name" => $rows_object->name,
                "first_name"   => $rows_object->name,
                "role"         => 'company'
              ];
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
                $id_company = (int) $insert_company;
                update_field( 'itjob_company_email', $rows_object->email, $id_company );
                update_field( 'itjob_company_name', $rows_object->name, $id_company );
                update_field( 'itjob_company_newsletter', $rows_object->subscriber, $id_company );

                // Mettre à jour le nom login
                $up_user = wp_update_user( [ 'user_login' => 'user' . $id_company, 'ID' => $user_id ] );
                if ( is_wp_error( $up_user ) ) {
                  wp_send_json_error( $up_user->get_error_message() );
                }

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
              $args    = [
                "user_pass"    => $rows_object->password,
                "user_login"   => $rows_object->seoname,
                "user_email"   => $rows_object->email,
                "display_name" => $rows_object->name,
                "first_name"   => $first_name,
                "last_name"    => $last_name,
                "role"         => 'candidate'
              ];
              $user_id = wp_insert_user( $args );
              if ( ! is_wp_error( $user_id ) ) {
                // Stocker les anciens information dans des metas
                $importUser = new importUser( $user_id );
                $importUser->update_user_meta( $rows_object );

                // Ajouter une post type 'candidate'
                $argc             = [
                  'post_title'  => $rows_object->name,
                  'post_status' => 'publish',
                  'post_type'   => 'candidate',
                  'post_author' => $user_id
                ];
                $insert_candidate = wp_insert_post( $argc );
                if ( is_wp_error( $insert_candidate ) ) {
                  wp_send_json_error( $insert_candidate->get_error_message() );
                }

                $id_candidate = (int) $insert_candidate;
                update_field( 'itjob_cv_email', $rows_object->email, $id_candidate );
                update_field( 'itjob_cv_firstname', $first_name, $id_candidate );
                update_field( 'itjob_cv_lastname', $last_name, $id_candidate );
                update_field( 'itjob_cv_newsletter', $rows_object->subscriber, $id_candidate );

                // Mettre à jour le nom login
                $up_user = wp_update_user( [ 'user_login' => 'user' . $id_candidate, 'ID' => $user_id ] );
                if ( is_wp_error( $up_user ) ) {
                  wp_send_json_error( $up_user->get_error_message() );
                }

                $up_candidate = wp_update_post( [ 'post_title' => 'CV' . $id_candidate, 'ID' => $id_candidate ] );
                if ( is_wp_error( $up_candidate ) ) {
                  wp_send_json_error( $up_candidate->get_error_message() );
                }

                $user = new \WP_User( $user_id );
                get_password_reset_key( $user );
                wp_send_json_success( [ 'msg' => "Utilisateur ajouter avec succès", 'utilisateur' => $user ] );
              } else {
                wp_send_json_error( $user_id->get_error_message() );
              }
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
            'newsletter'   => (int)$lines[12],
            'notification' => (int)$lines[13],
            'alert'        => $lines[14],
            'create'       => $lines[15]
          ];

          $rows = array_map( function ( $row ) {
            return trim( $row );
          }, $rows );
          $rows_object = (object) $rows;
          $User = $this->get_user_by_old_userid($rows_object->id_user);
          if ($User) {
            $Company = Company::get_company_by($User->ID);
            if (function_exists('update_field')) {
              update_field('itjob_company_address', $rows_object->address, $Company->getId());
              update_field('itjob_company_nif', $rows_object->nif, $Company->getId());
              update_field('itjob_company_stat', $rows_object->stat, $Company->getId());
              update_field('itjob_company_name', $rows_object->first_name . ' ' . $rows_object->last_name, $Company->getId());
              update_field('itjob_company_newsletter', $rows_object->newsletter, $Company->getId());
              update_field('itjob_company_notification', $rows_object->notification, $Company->getId());
              update_field('itjob_company_phone', $rows_object->phone, $Company->getId());
              update_field('itjob_company_alerts', !$rows_object->alert ? '' : $rows_object->alert, $Company->getId());
              update_field('itjob_company_greeting', $rows_object->greeting, $Company->getId());

              $values = [];
              $cellphones = explode(';', $rows_object->cellphones);
              foreach ($cellphones as $phone)
                $values[] = ['number' => $phone];
              update_field( 'itjob_company_cellphone', $values, $Company->getId() );
            }

            update_post_meta($Company->getId(), '__create', $rows_object->create);
            update_post_meta($Company->getId(), '__job_sought', $rows_object->job_sought);

            wp_send_json_success(['msg' => "L'entreprise est ajouter avec succès", 'data' => $Company]);
          } else {
            wp_send_json_error("L'utilisateur n'existe pas, ID: {$rows_object->id_user}");
          }
          break;

          // demandeur emploi
        case 'user_candidate_information':
          $rows = [
            'id_user' => (int)$lines[0],
            'audition' => (int)$lines[1],
            'find_job' => (int)$lines[2],
            'greeting' => $lines[3],
            'first_name' => $lines[4],
            'last_name' => $lines[5],
            'email' => $lines[6],
            'address' => $lines[7],
            'region' => $lines[8],

          ];
          break;
      }
    }


    private function get_user_by_old_userid($old_user_id) {
      if ( ! is_numeric($old_user_id)) return false;
      $user_query = new \WP_User_Query( array( 'meta_key' => '__id_user', 'meta_value' => $old_user_id ) );
      // Get the results
      $authors = $user_query->get_results();
      if (!empty($authors)) {
        $User  = $authors[0];
        return $User;
      } else {
        return false;
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