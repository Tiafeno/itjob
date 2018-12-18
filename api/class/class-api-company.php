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
    $paged = isset($_POST['start']) ? ($start === 0 ? 1 : $start / $length) : 1;
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
    $meta_query[] = ['relation' => "AND"];
    if (isset($_POST['search']) && !empty($_POST['search']['value'])) {
      $search = stripslashes($_POST['search']['value']);
      $searchs = explode('|', $search);
      $s = '';
      $status = preg_replace('/\s+/', '', $searchs[1]);
      $status = $status === '0' ? 0 : ($status === '1' ? 1 : ($status === 'pending' ? 'pending' : ''));
      if ($status === 1 || $status === 0) {
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

      if (!empty($searchs[0]) && $searchs[0] !== ' ') {
        $s = $searchs[0];
        $args['s'] = $s;
      }

      $account = trim($searchs[2]) !== '' && trim($searchs[2]) !== ' ' ? (int)$searchs[2] : '';
      if ($account === 1 || $account === 0 || $account === 2) {
        $meta_query[] = [
          'key' => 'itjob_meta_account',
          'value' => (int)$account,
          'compare' => '='
        ];
      }
      
    }

    $args = array_merge($args, ['meta_query' => $meta_query]);
    $the_query = new WP_Query($args);
    $entreprises = [];
    if ($the_query->have_posts()) {
      while ($the_query->have_posts()) {
        $the_query->the_post();
        if (!is_array($the_query->posts)) return false;
        $entreprises = array_map(function ($entreprise) {
          if (!isset($entreprise->ID)) return $entreprise;
          $objCompany = new \includes\post\Company($entreprise->ID);
          $objCompany->isActive = $objCompany->isValid();

          return $objCompany;
        }, $the_query->posts);
      }

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