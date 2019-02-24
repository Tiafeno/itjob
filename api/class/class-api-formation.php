<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 03/02/2019
 * Time: 15:14
 */

final
class apiFormation
{

  public
  function formation_resources (WP_REST_Request $rq)
  {
    $ref = isset($_REQUEST['ref']) ? stripslashes($_REQUEST['ref']) : false;
    if ($ref) {
      $formation_id = (int)$rq['id'];
      $Formation = new \includes\post\Formation($formation_id, true);
      if (is_null($Formation->title)) {
        return new WP_Error('no_formation', 'Aucune formation ne correpond à cette id', array('status' => 404));
      }

      switch ($ref) {
        case 'collect':
          return new WP_REST_Response($Formation);
          break;

        case 'subscription':
          $subscriptions = \includes\model\Model_Subscription_Formation::get_subscription($formation_id);
          $results = [];
          foreach ($subscriptions as $subscription) {
            $User = get_userdata((int)$subscription->user_id);
            if (in_array('candidate', $User->roles)) {
              $results[] = [
                'paid'      => (int)$subscription->paid,
                'candidate' => \includes\post\Candidate::get_candidate_by($User->ID, 'user_id', true)];
            }
          }
          return new WP_REST_Response($results);
          break;

        case 'activated':
          $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
          if (is_null($status)) new WP_Error(404, 'Parametre manquant');
          // Seul l'adminstrateur peuvent modifier cette option
          if (!current_user_can('delete_users')) return new WP_Error(403, "Votre compte ne vous permet pas de modifier cette fonctionnalité");
          $status = (int)$status;
          if ($status === 1) {
            $post_date = $Formation->date_create;
            $post_date = date('Y-m-d H:i:s', strtotime($post_date));
            wp_update_post(['ID' => $Formation->ID, 'post_date' => $post_date, 'post_status' => 'publish'], true);
            // Envoyer une email à l'entreprise
            do_action('confirm_validate_formation', $Formation->ID);
          }
          update_field('activated', (int)$status, $Formation->ID);

          return new WP_REST_Response(['success' => true, 'message' => "Formation mis à jour avec succès"]);
          break;

        case 'featured':
          $featured = isset($_REQUEST['val']) ? $_REQUEST['val'] : null;
          $dateLimit = isset($_REQUEST['datelimit']) ? $_REQUEST['datelimit'] : null;
          // Seul l'adminstrateur peuvent modifier cette option
          if (!current_user_can('delete_users')) return new WP_REST_Response(['success' => false, 'msg' => 'Accès refusé']);
          if (is_null($featured) || is_null($dateLimit)) new WP_REST_Response(['success' => false, 'msg' => 'Parametre manquant']);
          $featured = (int)$featured;
          update_field('featured', $featured, $Formation->getId());
          if ($featured) {
            update_field('featured_datelimit', date("Y-m-d H:i:s", (int)$dateLimit), $Formation->getId());
          }

          return new WP_REST_Response(['success' => true, 'msg' => "Position mise à jour avec succès"]);
          break;
      }
    } else {
      return new WP_Error('no_reference', 'Parametre (ref) manquant', array('status' => 403));
    }
  }

  public
  function get_formations (WP_REST_Request $rq)
  {
    $length = (int)$_POST['length'];
    $start = (int)$_POST['start'];
    $find = $_POST['search'];
    $paged = $start === 0 ? 1 : ($start + $length) / $length;
    $posts_per_page = $length ? $length : 20;
    $args = [
      'post_type'      => 'formation',
      'post_status'    => 'any',
      "posts_per_page" => $posts_per_page,
      'order'          => 'DESC',
      'orderby'        => 'ID',
      "paged"          => $paged
    ];
    if (!empty($find['value'])) {
      $search = stripslashes($find['value']);
      $searchs = explode('|', $search);
      $meta_query = [];
      $tax_query = [];
      $status = preg_replace('/\s+/', '', $searchs[1]);
      $status = empty($status) && $status !== '0' ? null : intval($status);
      if ($status === 1 || $status === 0) {
        $meta_query[] = ['relation' => "AND"];
        $meta_query[] = [
          'key'     => 'activated',
          'value'   => (int)$status,
          'compare' => '='
        ];
      }

      // Effectuer une recherche par mots
      if (!empty($searchs[0]) && $searchs[0] !== ' ') {
        $s = $searchs[0];
        $meta_query[] = [
          "relation" => "OR",
          [
            'key'     => 'establish_name',
            'value'   => "({$s}).*$",
            'compare' => 'REGEXP'
          ],
          [
            'key'     => 'diploma',
            'value'   => "({$s}).*$",
            'compare' => 'REGEXP'
          ],
          [
            'key'     => 'reference',
            'value'   => "({$s}).*$",
            'compare' => 'REGEXP'
          ],
        ];
      }

      // Recherche par secteur d'activité de la formation
      $activityArea = (int)$searchs[2];
      if ($activityArea !== 0) {
        $tax_query[] = [
          'taxonomy' => 'branch_activity',
          'field'    => 'term_id',
          'terms'    => [$activityArea]
        ];
      }

      // Filtre les formations par date
      $filterDate = isset($searchs[3]) ? $searchs[3] : '';
      if ($filterDate !== '' && !empty($filterDate)) {
        $date = explode('x', $filterDate);
        $date_query = [
          [
            'after'     => $date[0],
            'before'    => $date[1],
            'inclusive' => true,
          ]
        ];
        $args = array_merge($args, ['date_query' => $date_query]);
      }

      $args = array_merge($args, ['meta_query' => $meta_query]);
      if (!empty($tax_query))
        $args = array_merge($args, ['tax_query' => $tax_query]);
    }
    $the_query = new WP_Query($args);
    if ($the_query->have_posts()) {
      $formations = array_map(function ($formation) {
        $response = new \includes\post\Formation($formation->ID, true);
        return $response;
      }, $the_query->posts);

      return [
        "recordsTotal"    => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data'            => $formations
      ];
    } else {

      return [
        "recordsTotal"    => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data'            => []
      ];
    }
  }

  /**
   * Mettre à jour la formation
   * @param WP_REST_Request $rq
   * @return WP_Error|WP_REST_Response
   */
  public
  function update_formation (WP_REST_Request $rq)
  {
    $formation_id = (int)$rq['id'];
    $formation = stripslashes_deep($_REQUEST['formation']);
    $objFormation = json_decode($formation);

    $form = (object)[
      'diploma'        => $objFormation->diploma,
      'establish_name' => $objFormation->establish_name,
      'address'        => $objFormation->address,
      'duration'       => $objFormation->duration,
      'price'          => $objFormation->price,
      'date_limit'     => date('Ymd', strtotime($objFormation->date_limit))
    ];
    foreach (get_object_vars($form) as $key => $value) {
      update_field($key, $value, $formation_id);
    }

    $update_result = wp_update_post([
      'ID'           => $formation_id,
      'post_title'   => $objFormation->title,
      'post_content' => $objFormation->description], true);
    if (is_wp_error($update_result)) return new WP_Error($update_result->get_error_code(), $update_result->get_error_message());

    wp_set_post_terms($formation_id, [$objFormation->activity_area], 'branch_activity');
    wp_set_post_terms($formation_id, [$objFormation->region], 'region');

    update_field('reference', strtoupper("FOM{$formation_id}"), $formation_id);
    return new WP_REST_Response(['success' => true, 'message' => 'Formation mis à jour avec succès']);
  }
}

add_action('rest_api_init', function () {
  $post_type = "formation";
  $formation_meta = ["diploma", "activated", "featured", "featured_datelimit", "date_limit", "duration", "establish_name"];
  foreach ($formation_meta as $meta):
    register_rest_field($post_type, $meta, array(
      'update_callback' => function ($value, $object, $field_name) {
        return update_post_meta((int)$object->ID, $field_name, $value);
      },
      'get_callback'    => function ($object, $field_name) {
        $post_id = $object['id'];
        return get_post_meta($post_id, $field_name, true);
      },
    ));
  endforeach;

  // Registration training
  register_rest_field($post_type, 'registration', [
    'update_callback' => function ($value, $object, $field_name) {
      //e.g {'user_id': 15, 'paid': 1}
      //Possible value of paid: 1, 0 & 2
      $registrations = is_array($value) ? $value : [];
      if (!empty($registrations)) {
        $formation_id = (int)$object->ID;
        $Model = new \includes\model\Model_Subscription_Formation();
        foreach ($registrations as $registration) {
          $user_id = intval($registration['user_id']);
          $paid = $Model->get_paid($formation_id, $user_id);
          if ($paid) {
            $Model::update_paid($paid->registration_id, (int) $registration['paid']);
          }

          if (intval($registration['paid']) === 1) {
            // Envoyer un mail à l'utilisateur
            do_action('confirm_accept_registration_formation', $user_id, $formation_id);
          }
        }
      }

      return true;
    },
    'get_callback'    => function ($object, $field_name) {
      return \includes\model\Model_Subscription_Formation::get_subscription($object['id']);
    },
  ]);

});