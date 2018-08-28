<?php

namespace includes\shortcode;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'scImport' ) ) :
  class scImport {
    public function __construct() {
      add_shortcode( 'itjob_import_csv', [$this, 'sc_render_html'] );
    }

    public function sc_render_html( $atts, $content ="" ) {
      global $Engine, $itJob;

      if ( ! $Engine instanceof Twig_Environment ) {
        return $content;
      }
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
        'angular-sanitize',
        'angular-messages',
        'angular-animate',
        'angular-aria',
        'papaparse'
      ], $itJob->version, true );
      try {
        /** @var STRING $title */
        return $Engine->render( '@SC/import-csv.html.twig', [
          'title' => $title
        ] );
      } catch ( Twig_Error_Loader $e ) {
      } catch ( Twig_Error_Runtime $e ) {
      } catch ( Twig_Error_Syntax $e ) {
        echo $e->getRawMessage();
      }
    }
  }
endif;

return new scImport();