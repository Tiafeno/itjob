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
      $meta_query[] = ['relation' => "AND"];
      $status = preg_replace('/\s+/', '', $searchs[1]);
      $status = strlen($status) > 1 ? $status : (int)$status;
      if ($status === 1 || $status === 0) {
        $meta_query[] = [
          'key' => 'activated',
          'value' => (int)$status,
          'compare' => '='
        ];
      }

      if ($status === 'pending') {
        $args['post_status'] = $status;
      }

      if (!empty($searchs[0]) && $searchs[0] !== ' ') {
        $s = $searchs[0];
        $meta_query[] = [
          [
            "relation" => "OR"
          ],
          [
            'key' => 'itjob_offer_reference',
            'value' => $s,
            'compare' => 'LIKE'
          ],
          [
            'key' => 'itjob_offer_post',
            'value' => $s,
            'compare' => 'LIKE'
          ]
        ];
      }
      $args = array_merge($args, ['meta_query' => $meta_query]);
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