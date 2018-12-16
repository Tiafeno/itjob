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
    $the_query = new WP_Query($args);
    $candidates = [];
    if ($the_query->have_posts()) {
      while ($the_query->have_posts()) {
        $the_query->the_post();
        if (!is_array($the_query->posts)) return false;
        $candidates = array_map(function ($candidate) {
          if (!isset($candidate->ID)) return $candidate;
          $objCandidate = new \includes\post\Candidate($candidate->ID);
          $objCandidate->isActive = $objCandidate->is_activated();
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

  // TODO: Mettre à jours le candidat et le validé
  public function update_candidate(WP_REST_Request $request)
  {
    $candidate_id = (int)$request['id'];
    $candidate = stripslashes($_REQUEST['candidate']);

    
    return new WP_REST_Response('OK');
  }

  public function update_module_candidate(WP_REST_Request $request)
  {
    $ref = $request['ref'];
    $candidate_id = (int)$request['candidate_id'];
    $content = stripslashes($_REQUEST["content"]);
    $contents = json_decode($content);
    $Candidate = new \includes\post\Candidate($candidate_id);
    switch ($ref) {
      case 'training':
        $new_trainings = [];
        foreach ($contents as $content) {
          $new_trainings[] = [
            'training_dateBegin' => $content->training_dateBegin,
            'training_dateEnd' => $content->training_dateEnd,
            'training_diploma' => $content->training_diploma,
            'training_city' => $content->training_city,
            'training_country' => $content->training_country,
            'training_establishment' => $content->training_establishment,
            'validated' => $contentn->validated // S'il y a une autre formation qui n'est pas validé?
          ];
        }
        update_field('itjob_cv_trainings', $new_trainings, $Candidate->getId());
        $fields = get_field('itjob_cv_trainings', $Candidate->getId());

        break;
      case 'experience':
        $new_experiences = [];
        foreach ($contents as $content) {
          $new_experiences[] = [
            'exp_dateBegin' => $content->exp_dateBegin,
            'exp_dateEnd' => $content->exp_dateEnd,
            'exp_positionHeld' => $content->exp_positionHeld,
            'exp_company' => $content->exp_company,
            'exp_city' => $content->exp_city,
            'exp_country' => $content->exp_country,
            'exp_mission' => $content->exp_mission,
            'exp_branch_activity' => $content->exp_branch_activity,
            'validated' => $content->validated // S'il y a une autre formation qui n'est pas validé?
            // exp_branch_activity
          ];
        }
        update_field('itjob_cv_experiences', $new_experiences, $Candidate->getId());
        $fields = get_field('itjob_cv_experiences', $Candidate->getId());
        break;
    }

    return new WP_REST_Response($fields);
  }
}