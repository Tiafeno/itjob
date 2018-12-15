<?php
require_once 'model/class-api-model.php';
require_once 'class/class-permission-callback.php';
require_once 'class/class-api-candidate.php';
require_once 'class/class-api-offer.php';
require_once 'class/class-api-helper.php';
require_once 'class/class-api-company.php';

/**
 * WP_REST_Server::READABLE = ‘GET’
 * WP_REST_Server::EDITABLE = ‘POST, PUT, PATCH’
 * WP_REST_Server::CREATABLE = ‘POST’
 * WP_REST_Server::DELETABLE = ‘DELETE’
 * WP_REST_Server::ALLMETHODS = ‘GET, POST, PUT, PATCH, DELETE’
 */
add_action('rest_api_init', function () {
  // @route {POST} http://[DOMAINE_URL]/wp-json/it-api/candidate/<id>
  register_rest_route('it-api', '/candidate/(?P<id>\d+)', [
    // Recuperer un candidat
    array(
      'methods' => WP_REST_Server::READABLE,
      'callback' => [new apiCandidate(), 'get_candidate'],
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ),
    ),
    // Mettre à jours un candidat
    array(
      'methods' => WP_REST_Server::EDITABLE,
      'callback' => [new apiCandidate(), 'update_candidate'],
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ),
    )
  ]);

  /**
   * Mettre à jour les expériences et les formations
   */
  register_rest_route('it-api', '/candidate/update/(?P<ref>\w+)/(?P<candidate_id>\d+)', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [new apiCandidate(), 'update_module_candidate'],
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => [
        'ref' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
        'candidate_id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ]
    ),
  ]);

  /**
   * Récuperer la liste des candidates
   */
  register_rest_route('it-api', '/candidate/', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [new apiCandidate(), 'get_candidates'],
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => []
    ),
  ]);


  /**
   * Récuperer la liste des entreprises
   */
  register_rest_route('it-api', '/company/', [
    array(
      'methods' => WP_REST_Server::READABLE,
      'callback' => [new apiCompany(), 'get_companys'],
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => []
    ),
  ]);

  /**
   * Récuperer la liste des offres
   */
  register_rest_route('it-api', '/offer/(?P<id>\d+)', [
    // Recuperer un offre
    array(
      'methods' => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $request) {
        $offer_id = $request['id'];
        $Offer = new \includes\post\Offers($offer_id, true);
        if (!$Offer->is_offer()) {
          return new WP_Error('no_offer', 'Aucun offre ne correpond à cette id', array('status' => 404));
        }
        $Offer->enterprise = $Offer->getCompany();
        return new WP_REST_Response($Offer);
      },
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ),
    ),
    // Mettre à jours une offre
    array(
      'methods' => WP_REST_Server::EDITABLE,
      'callback' => function (WP_REST_Request $request) {
        $updatePost = false;
        $offer = stripslashes($_REQUEST['offer']);
        $offer = json_decode($offer);
        remove_filter('acf/update_value/name=itjob_offer_abranch', 'update_offer_reference');
        $currentOffer = new \includes\post\Offers($offer->ID);
        $dateTime = DateTime::createFromFormat("m/d/Y", $offer->date_limit);
        $acfDateLimit = $dateTime->format('Ymd');
        $form = [
          'post' => $offer->post,
          'reference' => $offer->reference,
          'contrattype' => (int)$offer->contract,
          'rateplan' => $offer->plan,
          'proposedsallary' => $offer->proposedsalary,
          'abranch' => $offer->branch_activity,
          'datelimit' => $acfDateLimit,
          'mission' => nl2br($offer->mission),
          'profil' => nl2br($offer->profil),
          'otherinformation' => nl2br($offer->otherInformation),
        ];

        wp_set_post_terms($offer->ID, [(int)$offer->region], 'region');
        wp_set_post_terms($offer->ID, [(int)$offer->town], 'city');

        foreach ($form as $itemKey => $item) {
          update_field("itjob_offer_{$itemKey}", $item, $offer->ID);
        }

        // Activation et publication
        if ($currentOffer->offer_status !== $offer->status ||
          (bool)$currentOffer->activated !== (bool)$offer->activated) {
          $updatePost = true;
        }
        if ($updatePost) {
          update_field('activated', (bool)$offer->activated, $offer->ID);
          if ($currentOffer->offer_status !== $offer->status && $offer->status === 'publish') {
            // notification de validation
            do_action('confirm_validate_offer', $offer->ID);
          }
          $result = wp_update_post(['ID' => $offer->ID, 'post_status' => $offer->status], true);
          if ( is_wp_error( $result ) ) {
            return new WP_REST_Response($result->get_error_message());
          } else {
            $message = $offer->status === 'publish' ? "publier" : "mise en attente";
            return new WP_REST_Response("Offre {$message} avec succès");
          }
        }

        return new WP_REST_Response('Offre mise à jour avec succès');
      },
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
      ),
    )
  ]);

  register_rest_route('it-api', '/offers/', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [new apiOffer(), 'get_offers'],
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => []
    ),
  ]);

  register_rest_route('it-api', '/taxonomies/(?P<taxonomy>\w+)', [
    array(
      'methods' => WP_REST_Server::ALLMETHODS,
      'callback' => [new apiHelper(), 'get_taxonomy'],
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => [
        'taxonomy' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
      ]
    ),
  ]);

});