<?php

final class apiOffer {
  public function __construct() {}


    private function add_filter_search($search, $meta_query = [])
    {
      add_filter('posts_where', function ($where) use ($search, $meta_query) {
        global $wpdb;
        //global $wp_query;
        $s = $search;
        $where .= " AND {$wpdb->posts}.ID IN (
                        SELECT
                          pt.ID
                        FROM {$wpdb->posts} as pt
                        INNER JOIN {$wpdb->postmeta} as pm1 ON (pt.ID = pm1.post_id)
                        WHERE pt.post_type = 'offers' ";

        foreach ($meta_query as $meta) {
          $meta = (object) $meta;
          $where .=   "  AND (pt.ID IN (
                            SELECT {$wpdb->postmeta}.post_id as post_id
                            FROM {$wpdb->postmeta}
                            WHERE {$wpdb->postmeta}.meta_key = '{$meta->meta_key}' AND {$wpdb->postmeta}.meta_value {$meta->meta_compare} {$meta->meta_value}
                          ))";
        }
        $where .= ")"; //  .end AND
          // Si une taxonomie n'est pas definie on ajoute cette condition dans la recherche
        $where .= "  OR (
                          {$wpdb->posts}.post_title LIKE  '%{$s}%'
                          AND {$wpdb->posts}.post_type = 'offers'";

        foreach ($meta_query as $meta) {
          $meta = (object) $meta;
          if (strpos($meta->meta_key, '%')) continue;
          $where .=     " AND ({$wpdb->posts}.ID IN (
                            SELECT {$wpdb->postmeta}.post_id as post_id
                            FROM {$wpdb->postmeta}
                            WHERE {$wpdb->postmeta}.meta_key = '{$meta->meta_key}' AND {$wpdb->postmeta}.meta_value {$meta->meta_compare} {$meta->meta_value}
                          ))";
        }

        $where .=      ")";

        return $where;
      });
    }
  

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
      $s = '';
      $meta_query = [];
      $activated = trim($searchs[1]) !== '' &&  trim($searchs[1]) !== ' ' ? (int)$searchs[1] : '';

      if ($activated === 1 || $activated === 0) {
        $activatedArg = [
          'meta_key' => 'activated', 
          'meta_value' => (int)$activated, 
          'meta_compare' => '='
        ];
        $meta_query[] = $activatedArg;
      }
      
      if (!empty($searchs[0]) && $searchs[0] !== ' ') {
        $s = $searchs[0];
        $meta_query[] = [
          'meta_key' => 'itjob_offer_reference',
          'meta_value' =>  "'%$s%'",
          'meta_compare' => "LIKE"
        ];
        $this->add_filter_search($s, $meta_query);
      } else {
        if (isset($activatedArg)) {
          $args = array_merge($args, $activatedArg);
        }
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