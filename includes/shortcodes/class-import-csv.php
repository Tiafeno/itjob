<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

use Http;

if ( ! class_exists( 'scImport' ) ) :
  class scImport {
    public function __construct() {
      add_shortcode( 'itjob_import_csv', [ $this, 'sc_render_html' ] );

      add_action( 'wp_ajax_import_csv', [ &$this, 'import_csv' ] );
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
          $this->add_user($content_type);
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
      switch ($content_type) {
        case 'user':
          $lines = Http\Request::getValue('column');
          $lines = json_decode($lines);
          $rows = [
            'id_user'     => $lines[0],
            'name'        => $lines[1],
            'seoname'     => $lines[2],
            'email'       => $lines[3],
            'password'    => $lines[4],
            'change_pwd'  => $lines[5],
            'description' => $lines[6],
            'status'      => $lines[7],
            'id_role'     => $lines[8],
            'created'     => $lines[9],
            'last_login'  => $lines[10],
            'subscriber'  => $lines[11]
          ];
          wp_send_json_success($rows);
          break;
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