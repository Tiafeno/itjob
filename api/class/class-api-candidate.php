<?php

final class apiCandidate
{
  public function __construct()
  {

  }

  /**
   * This is our callback function that embeds our resource in a WP_REST_Response
   *
   * @param WP_REST_Request $request
   *
   * @return WP_Error|WP_REST_Response
   */
  public function get_candidate(WP_REST_Request $request)
  {
    $candidate_id = $request['id'];
    $Candidate = new \includes\post\Candidate($candidate_id);
    if (is_null($Candidate->title)) {
      return new WP_Error('no_candidate', 'Aucun candidate ne correpond à cette id', array('status' => 404));
    }
    $Candidate->__get_access();

    return new WP_REST_Response($Candidate);
  }

  /**
   * Récuperer seulement les utilisateurs ou les candidats qui ont un CV
   */
  public function get_candidates(WP_REST_Request $rq)
  {
    $length = (int)$_POST['length'];
    $start = (int)$_POST['start'];
    $search = $_POST['search'];
    $paged = isset($_POST['start']) ? ($start === 0) ? 0 : $start / $length : 1;
    $posts_per_page = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $args = [
      'post_type' => 'candidate',
      'post_status' => 'any',
      "posts_per_page" => $posts_per_page,
      "paged" => $paged,
      'meta_query' => [
        [
          'key' => 'itjob_cv_hasCV',
          'value' => 1
        ]
      ]
    ];
    if (isset($search['value']) && !empty($search['value'])) {

    }
    $the_query = new WP_Query($args);
    $candidates = [];
    if ($the_query->have_posts()) {
      while ($the_query->have_posts()) {
        $the_query->the_post();
        if (!is_array($the_query->posts)) return false;
        $candidates = array_map(function ($candidate) {
          if (!isset($candidate->ID)) return $candidate;
          $objCandidate = new \includes\post\Candidate($candidate->ID);
          $objCandidate->__get_access();

          return $objCandidate;
        }, $the_query->posts);
      }

      return [
        "recordsTotal" => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data' => $candidates
      ];
    } else {

      return [
        "recordsTotal" => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data' => []
      ];
    }

  }

  public function update_candidate(WP_REST_Request $request)
  {
    return new WP_REST_Response('OK');
  }
}