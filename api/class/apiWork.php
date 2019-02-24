<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 24/02/2019
 * Time: 19:24
 */

class apiWork {
  public function __construct() {
    add_action('rest_api_init', [&$this, 'init']);
  }

  public function init() {
    register_rest_route('it-api', '/works', [
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => [&$this, 'get_works']
      )
    ]);

    register_rest_route('it-api', '/work/(?P<id>\d+)/(?P<action>\w+)', [
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => function (WP_REST_Request $rq) {
          $work_id = (int)$rq['id'];
          $action = stripslashes_deep($rq['action']);
          switch ($action) {
            case 'activated':
              $activated = Http\Request::getValue('activated', null);
              if (!is_null($activated)) {
                $activated = intval($activated);
                update_field('activated', $activated, $work_id);
                $work_status = get_post_status($work_id);
                $result = true;
                if ($work_status !== 'publish')
                  $result = wp_update_post(['ID' => $work_id, 'post_status' => 'publish'], true);
                if (!is_wp_error($result) && $activated) {
                  // Envoyer un mail de confirmation de publication
                }
                return new WP_REST_Response(['success' => $result]);
              } else {
                return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite." ]);
              }
              break;
            case 'remove':
              if (!is_numeric($work_id)) return new WP_REST_Response(['success' => false, 'message' => "Parametre manquante (id)"]);
              $result = wp_delete_post($work_id, true);
              if ( ! $result ) {
                return new WP_REST_Response(['success' => false, 'message' => $result->get_error_message()]);
              } else {
                return new WP_REST_Response(['success' => true]);
              }
              break;
          }
        },
        'permission_callback' => function () {
          return current_user_can('delete_posts');
        }
      )
    ]);
  }

  public function get_works() {
    $length = (int)$_POST['length'];
    $start = (int)$_POST['start'];
    $find = $_POST['search'];
    $args = [
      'post_type'      => 'works',
      'post_status'    => 'any',
      "offset"         => $start,
      'order'          => 'DESC',
      'orderby'        => 'ID',
      "number"         => $length
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
            'key'     => 'reference',
            'value'   => "({$s}).*$",
            'compare' => 'REGEXP'
          ]
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
      $works = array_map(function ($work) {
        $response = new \includes\post\Works($work->ID, true);
        return $response;
      }, $the_query->posts);

      return [
        "recordsTotal"    => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data'            => $works
      ];
    } else {

      return [
        "recordsTotal"    => (int)$the_query->found_posts,
        "recordsFiltered" => (int)$the_query->found_posts,
        'data'            => []
      ];
    }

  }
}

new apiWork();