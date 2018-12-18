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
    $paged = isset($_POST['start']) ? ($start === 0 ? 1 : $start / $length) : 1;
    $posts_per_page = isset($_POST['length']) ? (int)$_POST['length'] : 20;
    $args = [
      'post_type' => 'offers',
      'post_status' => ['publish', 'pending'],
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
      $meta_query[] = ['relation' => "AND"];
      $tax_query = [];
      $status = preg_replace('/\s+/', '', $searchs[1]);
      $status = $status === '0' ? 0 : ($status === '1' ? 1 : ($status === 'pending' ? 'pending' : ''));
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

      $activityArea = (int)$searchs[2];
      if ($activityArea !== 0) {
        $tax_query[] = [
          'taxonomy' => 'branch_activity',
          'field' => 'term_id',
          'terms' => [$activityArea]
        ];
      }

      $filterDate = $searchs[3];
      if ($filterDate !== '' && !empty($filterDate)) {
        $date = explode('x', $filterDate);
        $date_query = [
          [
            'after' => $date[0],
            'before' => $date[1],
            'inclusive' => true,
          ]
        ];
        $args = array_merge($args, ['date_query' => $date_query]);
      }

      $args = array_merge($args, ['meta_query' => $meta_query]);
      $args = array_merge($args, ['tax_query' => $tax_query]);
    }

    $the_query = new WP_Query($args);
    $offers = [];
    if ($the_query->have_posts()) {
      $offers = array_map(function ($offer) {
        $response = new \includes\post\Offers($offer->ID, true);
        return $response;
      }, $the_query->posts);

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