<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( ! class_exists( 'itJob' ) ) {
  class itJob {
    public function __construct() {
      add_action( 'wp_loaded', function () {
      }, 20 );

      add_action( 'init', function () {
      } );

      add_action( 'widgets_init', function () {
      } );

      add_action( 'wp_enqueue_scripts', function () {
        global $itJob;

        // Load uikit stylesheet
        wp_enqueue_style( 'uikit', get_template_directory_uri() . '/assets/css/uikit.min.css', '', '3.0.0rc10' );
        // Load the main stylesheet
        wp_enqueue_style( 'itjob', get_stylesheet_uri(), '', $itJob->version );

        // scripts
        wp_enqueue_script( 'underscore' );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'numeral', get_template_directory_uri() . '/assets/js/numeral.min.js', [], $itJob->version, true );
        wp_enqueue_script( 'bluebird', get_template_directory_uri() . '/assets/js/bluebird.min.js', [], $itJob->version, true );
//        wp_enqueue_script( 'uikit', get_template_directory_uri() . '/assets/js/uikit.min.js', ['jquery'], $itJob->version, true );
//        wp_enqueue_script( 'uikit-icon', get_template_directory_uri() . '/assets/js/uikit-icons.min.js', ['jquery'], $itJob->version, true );

        /** Register scripts */
        $this->register_enqueue_scripts();
      } );

    }

    public function register_enqueue_scripts() {
      global $itJob;
      // angular components
      wp_register_script('angular-route', get_template_directory_uri() . '/assets/js/libs/angularjs/angular-route.min.js', [], '1.7.2');
      wp_register_script('angular-sanitize', get_template_directory_uri() . '/assets/js/libs/angularjs/angular-sanitize.min.js', [], '1.7.2');
      wp_register_script('angular-messages', get_template_directory_uri() . '/assets/js/libs/angularjs/angular-messages.min.js', [], '1.7.2');
      wp_register_script('angular-animate', get_template_directory_uri() . '/assets/js/libs/angularjs/angular-animate.min.js', [], '1.7.2');
      wp_register_script('angular-aria', get_template_directory_uri() . '/assets/js/libs/angularjs/angular-aria.min.js', [], '1.7.2');
      wp_register_script('angular', get_template_directory_uri() . '/assets/js/libs/angularjs/angular.js', [], '1.7.2');

      // papaparse
      wp_register_script('papaparse', get_template_directory_uri() . '/assets/js/libs/papaparse/papaparse.min.js', [], '4.6.0');

      // Register components adminca stylesheet
      wp_register_style('bootstrap', get_template_directory_uri().'/assets/vendors/bootstrap/dist/css/bootstrap.min.css', '', '4.0.0');
      wp_register_style('font-awesome', get_template_directory_uri().'/assets/vendors/font-awesome/css/font-awesome.min.css', '', '4.7.0');
      wp_register_style('line-awesome', get_template_directory_uri().'/assets/vendors/line-awesome/css/line-awesome.min.css', '', '1.1.0');
      wp_register_style('themify-icons', get_template_directory_uri().'/assets/vendors/themify-icons/css/themify-icons.css', '', '1.1.0');
      wp_register_style('adminca-animate', get_template_directory_uri().'/assets/vendors/animate.css/animate.min.css', '', '3.5.1');
      wp_register_style('toastr', get_template_directory_uri().'/assets/vendors/toastr/toastr.min.css', '', '3.5.1');
      wp_register_style('bootstrap-select', get_template_directory_uri().'/assets/vendors/bootstrap-select/dist/css/bootstrap-select.min.css', '', '1.12.4');
      wp_register_style('adminca', get_template_directory_uri().'/assets/adminca/adminca.css', [
        'bootstrap', 'font-awesome', 'line-awesome', 'themify-icons', 'adminca-animate', 'toastr', 'bootstrap-select'
      ], $itJob->version);

      // Register components adminca scripts
      wp_register_script('popper', get_template_directory_uri().'/assets/vendors/popper.js/dist/umd/popper.min.js', [], '0.0.0', true);
      wp_register_script('bootstrap', get_template_directory_uri().'/assets/vendors/bootstrap/dist/js/bootstrap.min.js', ['popper'], '4.0.0-beta', true);
      wp_register_script('jq-slimscroll', get_template_directory_uri().'/assets/vendors/jquery-slimscroll/jquery.slimscroll.min.js', ['jquery'], '1.3.8', true);
      wp_register_script('idle-timer', get_template_directory_uri().'/assets/vendors/jquery-idletimer/dist/idle-timer.min.js', [], '1.1.0', true);
      wp_register_script('toastr', get_template_directory_uri().'/assets/vendors/toastr/toastr.min.js', ['jquery'], '0.0.0', true);
      wp_register_script('bootstrap-select', get_template_directory_uri().'/assets/vendors/bootstrap-select/dist/js/bootstrap-select.min.js', ['jquery', 'bootstrap'], '1.12.4', true);
      wp_register_script('adminca', get_template_directory_uri().'/assets/adminca/adminca.js', [
        'bootstrap', 'jq-slimscroll', 'idle-timer', 'toastr', 'bootstrap-select'
      ], $itJob->version, true);
    }
  }
}

return new itJob();
?>
