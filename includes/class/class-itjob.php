<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('itJob')) {
  class itJob {
    public function __construct(){
      add_action('wp_loaded', function() {}, 20);
    }
  }
}

return new itJob();
?>
