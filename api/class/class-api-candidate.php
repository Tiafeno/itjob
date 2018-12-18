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
    $paged = isset($_POST['start']) ? ($start === 0 ? 1 : ($start+$length) / $length) : 1;
    $posts_per_page = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $args = [
      'post_type' => 'candidate',
      'post_status' => 'any',
      'order' => 'DESC',
      'orderby' => 'ID',
      "posts_per_page" => $posts_per_page,
      "paged" => $paged,
    ];

    $meta_query = [];
    $meta_query[] = ['relation' => "AND"];

    $tax_query = [];

    if (isset($_POST['search']) && !empty($_POST['search']['value'])) {
      $search = stripslashes($_POST['search']['value']);
      $searchs = explode('|', $search);
      $s = '';
      
      $status = preg_replace('/\s+/', '', $searchs[1]);
      $status = $status === '0' ? 0 : ($status === '1' ? 1 : ($status === 'pending' ? 'pending' : ''));
      if ($status === 1 || $status === 0) {
        $meta_query[] = [
          'key' => 'activated',
          'value' => (int)$status,
          'compare' => '='
        ];
        $args['post_status'] = $status ? 'publish' : 'any';
      }

      if ($status === 'pending') {
        $args['post_status'] = $status;
      }

      $s = $status = preg_replace('/\s+/', '', $searchs[0]);
      if (!empty($s) && $s !== '') {
        $this->add_filter_search($s);
      } else {
        $meta_query[] = [
          'key' => 'itjob_cv_hasCV',
          'value' => 1
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
        add_filter('posts_where', function ($where) use ($filterDate) {
          $date = explode('x', $filterDate);
          global $wpdb;
          if (!is_admin()) {
            $where .= " AND {$wpdb->posts}.ID IN (
                          SELECT
                            pt.ID
                          FROM {$wpdb->posts} as pt
                          WHERE pt.post_type = 'candidate'
                            AND pt.post_date BETWEEN '{$date[0]}' AND '{$date[1]}'";
            $where .=  ")"; //  .end AND
         
          }
          
          return $where;
        });
      }
      
    } else {
      $meta_query[] = [
        'key' => 'itjob_cv_hasCV',
        'value' => 1
      ];
    }

    $args = array_merge($args, ['meta_query' => $meta_query]);
    $args = array_merge($args, ['tax_query' => $tax_query]);

    $the_query = new WP_Query($args);
    $candidates = [];
    if ($the_query->have_posts()) {
      $candidates = array_map(function ($candidate) {
        $objCandidate = new \includes\post\Candidate($candidate->ID);
        $objCandidate->isActive = $objCandidate->is_activated();
        $objCandidate->__get_access();

        return $objCandidate;
      }, $the_query->posts);

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
    $objCandidate = json_decode($candidate);

    // Update ACF field
    $centerInterest = [
      'various' => $objCandidate->divers,
      'projet'  => $objCandidate->projet
    ];
    update_field( 'itjob_cv_centerInterest', $centerInterest, $candidate_id );

    $driveLicences = array_map(function ($dL) {
      return $dL->id;
    }, $objCandidate->driveLicences);
    $driveLicences = empty($driveLicences) ? '' : $driveLicences;
    update_field( 'itjob_cv_driveLicence', $driveLicences, $candidate_id );

    $datetimeBd = DateTime::createFromFormat('m/d/Y', $objCandidate->birthday);
    $bdACF = $datetimeBd->format('Ymd');
    $form = (object)[
      'firstname' => $objCandidate->firstname,
      'lastname' => $objCandidate->lastname,
      'birthdayDate' => $bdACF,
      'address' => $objCandidate->address,
      'greeting' => $objCandidate->greeting,
      'status' => (int)$objCandidate->status
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

    $regionIds = [ $objCandidate->region ];
    wp_set_post_terms( $candidate_id, $regionIds, 'region' );

    $cityIds = [ $objCandidate->town ];
    wp_set_post_terms( $candidate_id, $cityIds, 'city' );

    $a = &$objCandidate->activated;
    $activated = !empty($a)  ? ($a === '1' ? 1 : ($a === '0' ? 0 : 'pending')) : null;
    $currentPost = get_post($candidate_id);
    if (is_numeric($activated)) {
      update_field( 'activated', $activated, $candidate_id );
      if ($activated && $currentPost->post_status !== 'publish')
        do_action('confirm_validate_candidate', $candidate_id);
      wp_update_post(['ID' => $candidate_id, 'post_status' => 'publish'], true);
    } else {
      update_field( 'activated', 0, $candidate_id );
      wp_update_post(['ID' => $candidate_id, 'post_status' => 'pending'], true);
    }


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