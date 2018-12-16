<?php

final class apiOffer
{
  public function __construct()
  {
  }

  public function get_offers(WP_REST_Request $rq)
  {
    $length = (int)$_POST['length'];
    $start = (int)$_POST['start'];
    $paged = isset($_POST['start']) ? ($start === 0) ? 0 : $start / $length : 1;
    $posts_per_page = isset($_POST['length']) ? (int)$_POST['length'] : 20;
    $args = [
      'post_type' => 'offers',
      'post_status' => ['publish', 'pending'],
      "posts_per_page" => $posts_per_page,
      "paged" => $paged
    ];
    if (isset($_POST['search']) && !empty($_POST['search']['value'])) {
      $search = stripslashes($_POST['search']['value']);
      $searchs = explode('|', $search);
      $s = '';
      $meta_query = [];

      $activated = trim($searchs[1]) !== '' && trim($searchs[1]) !== ' ' ? (int)$searchs[1] : '';
      if ($activated === 1 || $activated === 0) {
        $activatedArg = [
          'meta_key' => 'activated',
          'meta_value' => (int)$activated,
          'meta_compare' => '='
        ];
        $args = array_merge($args, $activatedArg);
      }

      if (!empty($searchs[2]) && $searchs[2] !== ' ') {
        $args['post_status'] = $searchs[2];
      }

      if (!empty($searchs[0]) && $searchs[0] !== ' ') {
        $s = $searchs[0];
        add_filter('posts_where', function ($where) use ($s) {
          global $wpdb;
          //global $wp_query;
          if (!is_admin()) {
            $where .= " AND {$wpdb->posts}.ID IN (
                        SELECT
                          pt.ID
                        FROM {$wpdb->posts} as pt
                        WHERE  
                          pt.ID IN (
                            SELECT {$wpdb->postmeta}.post_id as post_id
                            FROM {$wpdb->postmeta}
                            WHERE {$wpdb->postmeta}.meta_key = 'itjob_offer_reference' AND {$wpdb->postmeta}.meta_value LIKE '%{$s}%'
                          )
                          OR (pt.ID IN (
                            SELECT {$wpdb->postmeta}.post_id as post_id
                            FROM {$wpdb->postmeta}
                            WHERE {$wpdb->postmeta}.meta_key = 'itjob_offer_post' AND {$wpdb->postmeta}.meta_value LIKE '%{$s}%'
                          ))";
            $where .= ")"; //  .end AND
          }
          return $where;
        });

      }
    }

    $the_query = new WP_Query($args);
    $offers = [];
    if ($the_query->have_posts()) {
      while ($the_query->have_posts()) {
        $the_query->the_post();
        if (!is_array($the_query->posts)) return false;
        $offers = array_map(function ($offer) {
          if (!isset($offer->ID)) return false;
          $objOffer = new \includes\post\Offers($offer->ID, true);

          return $objOffer;
        }, $the_query->posts);
      }
      wp_reset_postdata();

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