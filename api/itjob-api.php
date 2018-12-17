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
      'callback' => function (WP_REST_Request $request) {
        $ref = isset($_REQUEST['ref']) ? stripslashes($_REQUEST['ref']) : false;
        if ($ref) {
          $candidate_id = (int)$request['id'];
          $Candidate = new \includes\post\Candidate($candidate_id);
          if (is_null($Candidate->title)) {
            return new WP_Error('no_candidate', 'Aucun candidate ne correpond à cette id', array('status' => 404));
          }
          switch ($ref) {
            case 'collect':
              $Candidate->__get_access();
              $Candidate->isActive = $Candidate->is_activated();
              return new WP_REST_Response($Candidate);

              break;

            case 'activated':
              $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
              if (is_null($status)) new WP_REST_Response('Parametre manquant');
              update_field('activated', (int)$status, $Candidate->getId());

              return new WP_REST_Response("Offre mis à jour avec succès");
              break;

            default:
              break;
          }
        } else {
          return new WP_REST_Response(false);
        }


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
    // Mettre à jours un candidat
    array(
      'methods' => WP_REST_Server::CREATABLE,
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

  register_rest_route('it-api', '/offers/', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [new apiOffer(), 'get_offers'],
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
        $ref = isset($_REQUEST['ref']) ? stripslashes(urldecode($_REQUEST['ref'])) : false;
        if ($ref) {
          switch ($ref) {
            case 'collect':
              $companyPost = $Offer->getCompany();
              $Offer->__info = new \includes\post\Company($companyPost->ID);
              return new WP_REST_Response($Offer);

              break;

            case 'activated':
              $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
              if (is_null($status)) return new WP_REST_Response('Parametre manquant');
              update_field('activated', (int)$status, $Offer->ID);
              return new WP_REST_Response("Offre mis à jour avec succès");

              break;
              
            case 'update_request':
              $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
              $id_request = isset($_REQUEST['id_request']) ? $_REQUEST['id_request'] : null;
              if (is_null($status) || (is_null($id_request) && is_numeric($id_request)))
                return new WP_Error("params", 'Parametre manquant');
              $Model = new \includes\model\itModel();
              $result = $Model->update_interest_status($id_request, $status);

            case 'request':
              $Model = new \includes\model\itModel();
              $Interests = $Model->get_offer_interests($offer_id);
              if (is_array($Interests) && !empty($Interests)) {
                $Response = [];
                foreach ($Interests as $Interest) {
                  $It = new stdClass();
                  $It->type = $Interest->type;
                  $It->status = $Interest->status;
                  $It->id_request = $Interest->id_cv_request;
                  $It->candidate = new \includes\post\Candidate($Interest->id_candidate, true);
                  $Response[] = $It;
                }
                return new WP_REST_Response($Response);
              }
              return new WP_REST_Response(false);

              break;

            default:
              break;
          }
        } else {
          return new WP_REST_Response(false);
        }

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
          if (is_wp_error($result)) {
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

  register_rest_route('it-api', '/dashboard/', [
    array(
      'methods' => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $request) {
        $ref = isset($_REQUEST['ref']) ? stripslashes(urldecode($_REQUEST['ref'])) : false;
        if ($ref) {
          $apiModel = new apiModel();
          switch ($ref) {
            case 'collect':
              $countCompany = $apiModel->count_post_type('company');
              $countActiveCompany = $apiModel->count_post_active('company');
              $Entreprise = [
                'count' => $countCompany,
                'countActive' => $countActiveCompany,
                'countFeatured' => $apiModel->count_featured_company()
              ];

              $countCandidates = $apiModel->count_post_type('candidate');
              $countActiveCandidate = $apiModel->count_post_active('candidate');
              $Candidates = [
                'count' => $countCandidates,
                'countActive' => $countActiveCandidate,
                'countFeatured' => $apiModel->count_featured_candidates()
              ];

              $countOffers = $apiModel->count_post_type('offers');
              $countActiveOffers = $apiModel->count_post_active('offers');
              $Offres = [
                'count' => $countOffers,
                'countActive' => $countActiveOffers,
                'countFeatured' => $apiModel->count_featured_offers()
              ];

              return new WP_REST_Response([
                'company' => $Entreprise,
                'candidate' => $Candidates,
                'offer' => $Offres
              ]);
              break;
            
            case 'header':
              $response = [
                'candidate' => $apiModel->count_post_status('candidate', 'pending'),
                'company' => $apiModel->count_post_status('company', 'pending'),
                'offers' => $apiModel->count_post_status('offers', 'pending')
              ];
              return WP_REST_Response($response);
              
              break;
              

            default:
              break;
          }
        } else {
          return new WP_REST_Response(false);
        }

      },
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