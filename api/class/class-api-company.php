<?php

class apiCompany
{
  public function __construct()
  {
  }


  public function get_companys(WP_REST_Request $request)
  {
    $length = (int)$_POST['length'];
    $start = (int)$_POST['start'];
    $paged = isset($_POST['start']) ? ($start === 0 ? 1 : ($start + $length) / $length) : 1;
    $posts_per_page = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $args = [
      'post_type' => 'company',
      'post_status' => 'any',
      'order' => 'DESC',
      'orderby' => 'ID',
      "posts_per_page" => $posts_per_page,
      "paged" => $paged,
    ];
    $meta_query = [];
    if (isset($_POST['search']) && !empty($_POST['search']['value'])) {
      $search = stripslashes($_POST['search']['value']);
      $searchs = explode('|', $search);
      $s = '';
      $status = preg_replace('/\s+/', '', $searchs[2]);
      $status = $status === '0' ? 0 : ($status === '1' ? 1 : ($status === 'pending' ? 'pending' : ''));
      if ($status === 1 || $status === 0) {
        $meta_query[] = ['relation' => "AND"];
        $meta_query[] = [
          'key' => 'activated',
          'value' => $status,
          'compare' => '='
        ];
        $args['post_status'] = $status ? 'publish' : 'any';
      }

      if ($status === 'pending') {
        $args['post_status'] = $status;
      }

      $s = $searchs[2];
      if (!empty($s) && $s !== ' ') {
        $args['s'] = $s;
      }

      $account = $searchs[0] === '0' ? 0 : (empty($searchs[0]) ? '' : (int)$searchs[0]);
      if ($account === 1 || $account === 0 || $account === 2) {
        $value = $account === 0 ? [1, 2] : $account;
        $compare = $account === 0 ? 'NOT IN' : '=';
          $account_query = [
          [
            'key' => 'itjob_meta_account',
            'value' => $value,
            'compare' => $compare
          ]
        ];
        if ($account === 0) {
          $account_query = array_merge($account_query, [
            'relation' => 'OR',
            [
              'key' => 'itjob_meta_account',
              'compare' => 'NOT EXISTS'
            ]
          ]);
        }
        $meta_query = $account_query;
      }

      $activityArea = (int)$searchs[1];
      if ($activityArea !== 0) {
        $tax_query[] = [
          'taxonomy' => 'branch_activity',
          'field' => 'term_id',
          'terms' => [$activityArea]
        ];
      }

      $beetwenDate = $searchs[3];
      if ($beetwenDate !== '' && !empty($beetwenDate)) {
        $date = explode('x', $beetwenDate);
        $date_query = [
          [
            'after' => $date[0],
            'before' => $date[1],
            'inclusive' => true,
          ]
        ];
        $args = array_merge($args, ['date_query' => $date_query]);
      }

    }

    $args = array_merge($args, ['meta_query' => $meta_query]);
    $the_query = new WP_Query($args);
    $entreprises = [];
    if ($the_query->have_posts()) {
      $entreprises = array_map(function ($entreprise) {
        $objCompany = new \includes\post\Company($entreprise->ID);
        $objCompany->isActive = $objCompany->isValid();

        return $objCompany;
      }, $the_query->posts);
      return [
        "recordsTotal" => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data' => $entreprises
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