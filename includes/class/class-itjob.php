<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('itJob')) {
  class itJob
  {
    public function __construct()
    {
      add_action('wp_loaded', function () {
      }, 20);

      add_action('init', function () {
      });

      add_action('widgets_init', function () {});

      add_action('wp_enqueue_scripts', function () {
        global $itJob;
        /** Styles */
        // Load components semantic ui stylesheet

        // Load uikit stylesheet
        wp_enqueue_style('uikit', get_template_directory_uri() . '/assets/css/uikit.min.css', '', '3.0.0rc10');
        // Load the main stylesheet
        wp_enqueue_style('itjob', get_stylesheet_uri(), '', $itJob->version);

        /** Scripts */
        wp_enqueue_script('underscore');
        wp_enqueue_script('jquery');
        wp_enqueue_script('numeral', get_template_directory_uri() . '/assets/js/numeral.min.js', array(), $itJob->version, true);
        wp_enqueue_script('bluebird', get_template_directory_uri() . '/assets/js/bluebird.min.js', array(), $itJob->version, true);
        wp_enqueue_script('uikit', get_template_directory_uri() . '/assets/js/uikit.min.js', array('jquery'), $itJob->version, true);
        wp_enqueue_script('uikit-icon', get_template_directory_uri() . '/assets/js/uikit-icons.min.js', array('jquery'), $itJob->version, true);
      });

    }
  }
}

return new itJob();
?>
