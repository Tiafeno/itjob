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
      }
      wp_send_json_success("En construction");
    }

    protected function add_term( $taxonomy ) {
      if ( ! is_user_logged_in() ) {
        wp_send_json_error( "Accès refuser" );
      }
      switch ( $taxonomy ) {
        case 'city':
          $row         = Http\Request::getValue( 'column' );
          $row         = json_decode( $row );
          $parent      = $row[0];
          $child       = $row[1];
          $parent_term = term_exists( $parent, $taxonomy );
          if ( 0 == $parent_term || is_null( $parent_term ) ) {
            $parent_term = wp_insert_term( $parent, $taxonomy, [ 'slug' => $parent ] );
          }
          $child_term = term_exists($child, $taxonomy, $parent_term['term_id']);
          if (0 == $child_term || is_null($child_term)) {
            $child_term = wp_insert_term($child, $taxonomy, ['parent' => $parent_term['term_id']]);
          }
          wp_send_json_success("({$parent_term['term_id']}) {$child_term['term_id']}");
          break;
      }
      wp_send_json_success("En construction");
    }

    // TODO: Réfuser l'accès au public
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