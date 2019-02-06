<?php

use includes\model\Model_Request_Formation;

if (!defined('ABSPATH')) {
  exit;
}

class apiRequestFormation
{
  public
  function __construct ()
  {
    add_action('rest_api_init', [&$this, 'init']);
  }

  public function init() {
    register_rest_route('it-api', '/request-formations', [
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => [&$this, 'get_request_formations']
      )
    ]);

    register_rest_route('it-api', '/request-formations/(?P<id>\d+)', [
      array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => function (WP_REST_Request $rq) {
          $rf_id = (int) $rq['id'];
          if ($rf_id === 0) return new WP_Error(404, "L'identifiant de la demande n'est pas valide");
          $request_formations = Model_Request_Formation::get_resources($rf_id);

          return new WP_REST_Response($request_formations);
        }
      ),
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => function (WP_REST_Request $rq) {
          global $wpdb;
          $id = (int)$rq['id'];
          $formation = stripslashes_deep($_REQUEST['formation']);
          $objFormation = json_decode($formation);

          $result = $wpdb->update( $wpdb->prefix . "request_training", [ 'subject' => $objFormation->subject ],
            [ 'ID' => (int)$id ], [ '%s' ], [ '%d' ] );
          $response = $result ? "Mise à jours effectuer avec succès" : false;
          return new WP_REST_Response($response);
        },
        'permission_callback' => function () {
          return current_user_can('edit_posts');
        },
      )
    ]);

    register_rest_route('it-api', '/request-formation/(?P<id>\d+)/(?P<action>\w+)', [
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => function (WP_REST_Request $rq) {
          $request_formation_id = (int)$rq['id'];
          $action = stripslashes_deep($rq['action']);
          switch ($action) {
            case 'disabled':
              $disabled = Http\Request::getValue('disabled', null);
              if (!is_null($disabled)) {
                $disabled = intval($disabled);
                $result = Model_Request_Formation::update_activation($request_formation_id, $disabled);
                Model_Request_Formation::update_validation($request_formation_id, 1);
                return new WP_REST_Response(['success' => $result]);
              } else {
                return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite." ]);
              }
              break;
          }
        },
        'permission_callback' => function () {
          return current_user_can('edit_posts');
        }
      )
    ]);
  }

  /**
   * Récuperer les demande d'offres
   * @return WP_REST_Response
   */
  public
  function get_request_formations ()
  {
    $length = (int)$_REQUEST['length'];
    $start = (int)$_REQUEST['start'];
    $formations = Model_Request_Formation::collect_resources($start, $length);

    return [
      "recordsTotal"    => (int)$formations->founds,
      "recordsFiltered" => (int)$formations->founds,
      'data'            => $formations->results
    ];

  }

}

new apiRequestFormation();