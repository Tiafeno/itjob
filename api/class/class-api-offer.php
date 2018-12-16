<?php

final class apiOffer {
  public function __construct() {}

  public function get_offers() {
    $length = (int)$_POST['length'];
    $start = (int)$_POST['start'];
    $paged = isset($_POST['start']) ? ($start === 0) ? 0 : $start / $length : 1;
    $posts_per_page = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $args = [
      'post_type' => 'offers',
      'post_status' => 'any',
      "posts_per_page" => $posts_per_page,
      'order' => 'DESC',
      'orderby' => 'ID',
      "paged" => $paged
    ];
    if (isset($_POST['search']) && !empty($_POST['search']['value'])) {
      $search = stripslashes($_POST['search']['value']);
      $searchs = explode('|', $search);
      if (!empty($searchs[0]) && $searchs[0] !== ' ')
        $args = array_merge($args, ['s' => $searchs[0]]);
      // activation
      if (!empty($searchs[1]) && $searchs[1] !== ' ') {
        $activated = (int)$searchs[1];
        $meta_query = ['meta_key' => 'activated', 'meta_value' => (int)$activated, 'meta_compare' => '='];
        $args = array_merge($args, $meta_query);
      }

      if (!empty($searchs[2]) && $searchs[2] !== ' ') {
        $status = $searchs[2];
        $args['post_status'] = $status;
      }
    }
    
    $the_query = new WP_Query($args);
    $offers = [];
    if ($the_query->have_posts()) {
      while ($the_query->have_posts()) {
        $the_query->the_post();
        if (!is_array($the_query->posts)) return false;
        $offers = array_map(function ($offer) {
          if (!isset($offer->ID)) return $offer;
          $objOffer = new \includes\post\Offers($offer->ID);
          $objOffer->__get_access();

          return $objOffer;
        }, $the_query->posts);
      }

      return [
        "recordsTotal" => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data' => $offers
      ];
    } else {

      return [
        "recordsTotal" => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data' => []
      ];
    }
  }
}