<?php

class apiCompany {
  public function __construct() {
  }

  public function get_companys(WP_REST_Request $request) {
    $args = [
      'post_type' => 'company',
      'post_type' => 'publish',
      'posts_per_page' => -1
    ];
    $allCompany = get_posts($args);
    $allCompany = array_map($allCompany, function($company) {
      $cdt = new \includes\post\Company($company->ID);
      return $cdt;
    });
    return $allCompany;
  }
}