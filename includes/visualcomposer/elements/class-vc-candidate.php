<?php
namespace includes\vc;

use Http;
use includes\post\Candidate;
use includes\post\UserParticular;

if ( ! class_exists( 'WPBakeryShortCode' ) ) {
  new \WP_Error( 'WPBakery', 'WPBakery plugins missing!' );
}

if ( ! class_exists('vcCandidate')):
  class vcCandidate extends \WPBakeryShortCode {
    public function __construct() {
      add_action( 'init', [ $this, 'vc_candidate_mapping' ] );
    }

    public function vc_candidate_mapping() {

    }
  }
endif;

return new vcCandidate();