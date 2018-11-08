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
    $Candidate->__client_premium_access();
    return new WP_REST_Response($Candidate);
  }

  public function get_candidates(WP_REST_Request $rq)
  {
    $args = [
      'post_type' => 'candidate',
      'post_status' => 'any',
      'posts_per_page' => -1
    ];
    $candidats = get_posts($args);
    $candidats = array_map(function ($candidat) {
      $cdt = new \includes\post\Candidate($candidat->ID);
      $cdt->__get_access();
      return $cdt;
    }, $candidats);
    return $candidats;
  }

  public function update_candidate(WP_REST_Request $request)
  {
    return new WP_REST_Response('OK');
  }
}