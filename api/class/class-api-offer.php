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
    $paged = isset($_POST['start']) ? ($start === 0 ? 1 : ($start + $length) / $length) : 1;
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
      $tax_query = [];
      $status = preg_replace('/\s+/', '', $searchs[1]);
      $status = $status === 'pending' ? 'pending' : (empty($status) && $status !== '0' ? null : intval($status));
      if ($status === 1 || $status === 0) {
        $meta_query[] = ['relation' => "AND"];
        $meta_query[] = [
          'key' => 'activated',
          'value' => (int)$status,
          'compare' => '='
        ];
        $args['post_status'] = "publish";
      }

      if ($status === 'pending') {
        $args['post_status'] = $status;
      }

      if (!empty($searchs[0]) && $searchs[0] !== ' ') {
        $s = $searchs[0];
        $meta_query[] = [
          "relation" => "OR",
          [
            'key' => 'itjob_offer_post',
            'value' => $s,
            'compare' => 'LIKE'
          ],
          [
            'key' => 'itjob_offer_reference',
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

      $filterDate = isset($searchs[3]) ? $searchs[3] : '';
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

      $ratePlan = isset($searchs[4]) ? $searchs[4] : '';
      if ($ratePlan !== '' && !empty($ratePlan)) {
        $rate_query = [
          'key' => 'itjob_offer_rateplan',
          'value' => $ratePlan,
          'compare' => '='
        ];

        if ($ratePlan === 'standard') {
          $query = ['relation' => 'OR'];
          $query[] = $rate_query;
          $query[] = [
            'key' => 'itjob_offer_rateplan',
            'compare' => 'NOT EXISTS'
          ];
        } else {
          $query = $rate_query;
        }
        $meta_query[] = $query;
      }

      $args = array_merge($args, ['meta_query' => $meta_query]);
      if (!empty($tax_query))
        $args = array_merge($args, ['tax_query' => $tax_query]);
    }
    $the_query = new WP_Query($args);
    if ($the_query->have_posts()) {
      $offers = array_map(function ($offer) {
        $response = new \includes\post\Offers($offer->ID, true);

        $Company =  $response->getCompany();
        $rspCompany = new \stdClass();
        $rspCompany->name = $Company->post_title;
        $rspCompany->ID = $Company->ID;
        $response->_company = $rspCompany;

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

add_action('rest_api_init', function () {
  $post_type = "offers";
  $formation_meta = ["activated", "itjob_offer_rateplan", "itjob_offer_paid", "itjob_offer_post",
    "itjob_offer_featured", "itjob_offer_featured_position", "itjob_offer_featured_datelimit", "itjob_offer_reference",
    "itjob_offer_datelimit", 'itjob_offer_contrattype', 'itjob_offer_profil', 'itjob_offer_mission',
    'itjob_offer_proposedsallary', 'itjob_offer_otherinformation', 'itjob_offer_locationcible', "itjob_offer_company",
    "itjob_offer_abranch"];
  foreach ($formation_meta as $meta):
    register_rest_field($post_type, $meta, array(
      'update_callback' => function ($value, $object, $field_name) {
        return update_field($field_name, $value, (int) $object->ID);
      },
      'get_callback' => function ($object, $field_name) {
        if (!is_user_logged_in()) return null;
        $post_id = $object['id'];
        return get_field($field_name, $post_id);
      },
    ));
  endforeach;
});
