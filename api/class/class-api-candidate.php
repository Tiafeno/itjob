<?php

final class apiCandidate
{
  public function __construct()
  {

  }

  private function add_filter_search($search)
  {
    add_filter('posts_where', function ($where) use ($search) {
      global $wpdb;
      //global $wp_query;
      $s = $search;
      if (!is_admin()) {
        $where .= " AND {$wpdb->posts}.ID IN (
                      SELECT
                        pt.ID
                      FROM {$wpdb->posts} as pt
                      INNER JOIN {$wpdb->postmeta} as pm1 ON (pt.ID = pm1.post_id)
                      WHERE pt.post_type = 'candidate'
                        AND (pt.ID IN (
                          SELECT {$wpdb->postmeta}.post_id as post_id
                          FROM {$wpdb->postmeta}
                          WHERE {$wpdb->postmeta}.meta_key = 'itjob_cv_hasCV' AND {$wpdb->postmeta}.meta_value = 1
                        ))
                        AND (pt.ID IN(
                          SELECT trs.object_id as post_id
                          FROM {$wpdb->terms} as terms
                            INNER JOIN {$wpdb->term_relationships} as trs
                            INNER JOIN {$wpdb->term_taxonomy} as ttx ON (trs.term_taxonomy_id = ttx.term_taxonomy_id)
                          WHERE terms.term_id = ttx.term_id
                          AND ttx.taxonomy = 'job_sought'
                          AND terms.name LIKE '%{$s}%'
                        ))
                        OR (pt.ID IN (
                          SELECT {$wpdb->postmeta}.post_id
                          FROM {$wpdb->postmeta}
                          WHERE {$wpdb->postmeta}.meta_key = '_old_job_sought' AND {$wpdb->postmeta}.meta_value LIKE '%{$s}%'
                        ))";
      $where .= ")"; //  .end AND
        // Si une taxonomie n'est pas definie on ajoute cette condition dans la recherche
      $where .= "  OR (
                        {$wpdb->posts}.post_title LIKE  '%{$s}%'
                        AND {$wpdb->posts}.post_type = 'candidate'
                        AND ({$wpdb->posts}.ID IN (
                          SELECT {$wpdb->postmeta}.post_id as post_id
                          FROM {$wpdb->postmeta}
                          WHERE {$wpdb->postmeta}.meta_key = 'itjob_cv_hasCV' AND {$wpdb->postmeta}.meta_value = 1
                        ))
                      )";
      }
      
      return $where;
    });
  }


  /**
   * Récuperer seulement les utilisateurs ou les candidats qui ont un CV
   */
  public function get_candidates(WP_REST_Request $rq)
  {
    $length = (int)$_POST['length'];
    $start = (int)$_POST['start'];
    $paged = isset($_POST['start']) ? ($start === 0) ? 0 : $start / $length : 1;
    $posts_per_page = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $args = [
      'post_type' => 'candidate',
      'post_status' => 'any',
      "posts_per_page" => $posts_per_page,
      "paged" => $paged,
    ];
    $meta_query = [];
    $meta_query[] = ['relation' => "AND"];
    if (isset($_POST['search']) && !empty($_POST['search']['value'])) {
      $search = stripslashes($_POST['search']['value']);
      $searchs = explode('|', $search);
      $s = '';
      $activated = trim($searchs[1]) !== '' && trim($searchs[1]) !== ' ' ? (int)$searchs[1] : '';
      if ($activated === 1 || $activated === 0) {
        $meta_query[] = [
          'key' => 'activated',
          'value' => (int)$activated,
          'compare' => '='
        ];
      }

      if (!empty($searchs[2]) && $searchs[2] !== ' ') {
        $args['post_status'] = $searchs[2];
      }

      if (!empty($searchs[0]) && $searchs[0] !== ' ') {
        $s = $searchs[0];
        $this->add_filter_search($s);
      }
      
    } else {
      $meta_query[] = [
        'key' => 'itjob_cv_hasCV',
        'value' => 1
      ];
    }

    $args = array_merge($args, ['meta_query' => $meta_query]);
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
    $candidate = stripslashes($_REQUEST['candidat']);
    $objCandidate = json_decode(candidate);

    // Update ACF field
    $activated = is_string($objCandidate->activated) ? ($objCandidate->activated == 'true' ? 1 : 0) : (bool)$objCandidate->activated;
    update_field( 'activated', $activated, $candidate_id );

    $centerInterest = [
      'various' => $objCandidate->divers,
      'projet'  => $objCandidate->projet
    ];
    update_field( 'itjob_cv_centerInterest', $centerInterest, $candidate_id );

    $driveLicences = array_map(function ($dL) {
      return $dl->id;
    }, $objCandidate->driveLicences);
    $driveLicences = empty($driveLicences) ? '' : implode( ',', $driveLicences );
    update_field( 'itjob_cv_driveLicence', $driveLicences, $candidate_id );

    $datetimeBd = DateTime::createFromFormat('m/d/Y', $objCandidate->birthday);
    $bdACF = $dateTime->format('Ymd');
    $form = (object)[
      'firstname' => $objCandidate->firstname,
      'lastname' => $objCandidate->lastname,
      'birthdayDate' => $bdACF,
      'address' => $objCandidate->address,
      'greeting' => $objCandidate->greeting,
    ];

    foreach (get_object_vars($form) as $key => $value) {
      update_field("itjob_cv_" . $key, $value, $candidate_id);
    }

    // Ajouter les emplois rechercher par le candidat (Existant et qui n'existe pas encore dans la base de donnée)
    $jobIds = is_array($objCandidate->jobs) ? $objCandidate->jobs : [];
    wp_set_post_terms( $candidate_id, $jobIds, 'job_sought' );
    // Ajouter les logiciels
    $softwareIds = is_array($objCandidate->softwares) ? $objCandidate->softwares : [];
    wp_set_post_terms( $candidate_id, $softwareIds, 'software' );
    // Ajouter les languages
    $languagesIds = is_array($objCandidate->languages) ? $objCandidate->languages : [];
    wp_set_post_terms( $candidate_id, $languagesIds, 'language' );

    $regionIds = is_array($objCandidate->region) ? $objCandidate->region : [];
    wp_set_post_terms( $candidate_id, $regionIds, 'region' );

    $cityIds = is_array($objCandidate->town) ? $objCandidate->town : [];
    wp_set_post_terms( $candidate_id, $cityIds, 'city' );

    return new WP_REST_Response('Candidat mis à jour avec succès');
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