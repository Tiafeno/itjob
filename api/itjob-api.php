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

  // Ajouter des informations utilisateur dans la reponse
  add_filter('jwt_auth_token_before_dispatch', function ($data, $user) {
    // Tells wordpress the user is authenticated
    wp_set_current_user($user->ID);
    $user_data = get_userdata($user->ID);
    $data['data'] = $user_data;
    return $data;
  }, 10, 2);

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
              // Seul l'adminstrateur peuvent modifier cette option
              if (!current_user_can('delete_users')) return new WP_REST_Response(['success' => false, 'msg' => 'Accès refusé']);

              $status = (int)$status;
              if ($Candidate->state === 'pending' && $status === 1) {
                wp_update_post(['ID' => $Candidate->getId(), 'post_status' => 'publish'], true);
              }
              update_field('activated', (int)$status, $Candidate->getId());

              return new WP_REST_Response("Candidate mis à jour avec succès");
              break;

            case 'featured':
              $featured = isset($_REQUEST['val']) ? $_REQUEST['val'] : null;
              $dateLimit = isset($_REQUEST['datelimit']) ? $_REQUEST['datelimit'] : null;

              // Seul l'adminstrateur peuvent modifier cette option
              if (!current_user_can('delete_users')) return new WP_REST_Response(['success' => false, 'msg' => 'Accès refusé']);

              if (is_null($featured)) new WP_REST_Response(['success' => false, 'msg' => 'Parametre manquant']);
              $featured = (int)$featured;
              update_field('itjob_cv_featured', $featured, $Candidate->getId());
              if ($featured) {
                update_field('itjob_cv_featured_datelimit', date("Y-m-d H:i:s", strtotime($dateLimit)), $Candidate->getId());
              }

              return new WP_REST_Response(['success' => true, 'msg' => "Position mise à jour avec succès"]);
              break;

            case 'archived':
              $archived = isset($_REQUEST['val']) ? $_REQUEST['val'] : null;
              if (is_null($archived)) new WP_REST_Response(['success' => false, 'msg' => 'Parametre manquant']);
              $archived = intval($archived);
              update_field('archived', $archived, $Candidate->getId());

              return new WP_REST_Response(['success' => true, 'msg' => "Candidate mise à jour avec succès"]);
              break;

            default:
              break;
          }
        } else {
          return new WP_REST_Response(false);
        }
      },
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
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
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args' => []
    ),
  ]);

  register_rest_route('it-api', '/candidate/archive/', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [new apiCandidate(), 'get_candidate_archived'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args' => []
    ),
  ]);

  register_rest_route('it-api', '/offers/', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [new apiOffer(), 'get_offers'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args' => []
    ),
  ]);

  /**
   * Récuperer la liste des entreprises
   */
  register_rest_route('it-api', '/company/', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [new apiCompany(), 'get_companys'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args' => []
    )
  ]);

  register_rest_route('it-api', '/company/(?P<id>\d+)', [
    array(
      'methods' => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $request) {
        $ref = isset($_REQUEST['ref']) ? stripslashes($_REQUEST['ref']) : false;
        if ($ref) {
          $company_id = (int)$request['id'];
          $Company = new \includes\post\Company($company_id);
          if (is_null($Company->title)) {
            return new WP_Error('no_company', 'Aucun candidate ne correpond à cette id', array('status' => 404));
          }
          switch ($ref) {
            case 'collect':
              $Company->isValid = $Company->isValid();
              $Company->isPremium = $Company->isPremium();
              return new WP_REST_Response($Company);

              break;

            case 'activated':
              $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
              if (is_null($status)) new WP_REST_Response('Parametre manquant');
              $status = (int)$status;
              if ($Company->post_status === 'pending' && $status === 1) {
                wp_update_post(['ID' => $Company->getId(), 'post_status' => 'publish'], true);
              }
              $status = (int)$status;
              update_field('activated', $status, $Company->getId());
              if ($status) {
                do_action('notice-change-company-status', $Company->getId(), $status);
              }

              return new WP_REST_Response("Entreprise mis à jour avec succès");
              break;

            case 'account':
              $type = isset($_REQUEST['type']) ? (int)$_REQUEST['type'] : null;
              if (is_null($type)) new WP_REST_Response('Parametre manquant');
              if ($type !== $Company->account) {
                update_field('itjob_meta_account', $type, $Company->getId());
                return new WP_REST_Response(['success' => true, 'msg' => "Compte mise à jour avec succès"]);
              } else {
                return new WP_REST_Response(['success' => true, 'msg' => 'Ce compte est déja un compte ' . $type]);
              }
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
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => function (WP_REST_Request $request) {
        $company_id = (int)$request['id'];
        $company = stripslashes($_REQUEST['company']);
        $company = json_decode($company);
        $currentCompany = get_post($company_id);
        $form = [
          'itjob_meta_account' => (int)$company->account,
          'itjob_company_address' => $company->address,
          'itjob_company_greeting' => $company->greeting,
          'itjob_company_name' => $company->name,
          'itjob_company_nif' => $company->nif,
          'itjob_company_stat' => $company->stat
        ];

        foreach ($form as $itemKey => $item) {
          update_field($itemKey, $item, $currentCompany->ID);
        }

        wp_set_post_terms($currentCompany->ID, [(int)$company->region], 'region');
        wp_set_post_terms($currentCompany->ID, [(int)$company->town], 'city');
        wp_set_post_terms($currentCompany->ID, [(int)$company->area_activity], 'branch_activity');

        $valuePhone = [];
        foreach ($company->cellphones as $phone) {
          $valuePhone[] = ['number' => $phone];
        }
        update_field('itjob_company_cellphone', $valuePhone, $currentCompany->ID);

        $result = wp_update_post(['ID' => $currentCompany->ID, 'post_title' => $company->title]);
        if (is_wp_error($result)) {
          return new WP_REST_Response(['success' => false, 'msg' => $result->get_error_message()]);
        } else {
          return new WP_REST_Response(['success' => true, 'msg' => 'Entreprise mise à jour avec succès']);
        }
      },
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param) && $param !== 0;
          }
        ),
      ),
    )
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
              $status = (int)$status;
              if ($Offer->offer_status === 'pending' && $status === 1) {
                wp_update_post(['ID' => $Offer->ID, 'post_status' => 'publish'], true);
              }
              $status = (int)$status;
              update_field('activated', (int)$status, $Offer->ID);
              if ($status) {
                do_action('notice-change-offer-status', $Offer, $status);
              }
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

            case 'featured':
              $featured = isset($_REQUEST['val']) ? $_REQUEST['val'] : null;
              $dateLimit = isset($_REQUEST['datelimit']) ? $_REQUEST['datelimit'] : null;
              if (is_null($featured)) new WP_REST_Response(['success' => false, 'msg' => 'Parametre manquant']);
              $featured = (int)$featured;
              update_field('itjob_offer_featured', $featured, $Candidate->getId());
              if ($featured) {
                update_field('itjob_offer_featured_datelimit', date("Y-m-d H:i:s", strtotime($dateLimit)), $Candidate->getId());
              }

              return new WP_REST_Response(['success' => true, 'msg' => "Position mise à jour avec succès"]);

              break;

            case 'rateplan':
              $rateplan = isset($_REQUEST['val']) ? $_REQUEST['val'] : null;
              if (is_null($rateplan)) new WP_REST_Response(['success' => false, 'msg' => 'Parametre manquant']);
              update_field('itjob_offer_rateplan', $rateplan, $Offer->ID);

              return new WP_REST_Response(['success' => true, 'msg' => "Offre mise à jour avec succès"]);

              break;

            case 'update_date_limit':
              $dateLimit = isset($_REQUEST['datelimit']) ? $_REQUEST['datelimit'] : null;
              $dateTime = DateTime::createFromFormat("m/d/Y", $dateLimit);
              $acfDateLimit = $dateTime->format('Ymd');
              update_field('itjob_offer_datelimit', $acfDateLimit, $Offer->ID);
              return new WP_REST_Response(['success' => true, 'msg' => "Offre mise à jour avec succès"]);

              break;

            default:
              break;
          }
        } else {
          return new WP_REST_Response(false);
        }

      },
      'permission_callback' => function ($data) {
        $ref = isset($_REQUEST['ref']) ? stripslashes(urldecode($_REQUEST['ref'])) : false;
        if (!$ref) return false;
        return ($ref === 'update_date_limit') ? current_user_can('edit_posts') : current_user_can('remove_users');
      },
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
        $offer = stripslashes($_REQUEST['offer']);
        $offer = json_decode($offer);
        $currentOffer = get_post($offer->ID);
        $dateTime = DateTime::createFromFormat("m/d/Y", $offer->date_limit);
        $acfDateLimit = $dateTime->format('Ymd');
        $form = [
          'post' => $offer->post,
          'contrattype' => (int)$offer->contract,
          'proposedsallary' => $offer->proposedsalary,
          'abranch' => $offer->branch_activity,
          'datelimit' => $acfDateLimit,
          'mission' => $offer->mission,
          'profil' => $offer->profil,
          'otherinformation' => $offer->otherInformation,
        ];

        // Activation et publication
        $a = &$offer->status;
        $activated = $a === 'pending' ? 'pending' : intval($a);
        if ($activated === 0 || $activated === 1) {
          update_field('activated', $activated, $currentOffer->ID);
          if ($activated && $currentOffer->post_status !== 'publish')
            do_action('confirm_validate_offer', $currentOffer->ID);
          $result = wp_update_post(['ID' => $currentOffer->ID, 'post_status' => 'publish'], true);
        } else {
          update_field('activated', 0, $currentOffer->ID);
          if ($activated === 'pending')
            $result = wp_update_post(['ID' => $currentOffer->ID, 'post_status' => 'pending'], true);
          $result = true;
        }

        wp_set_post_terms($offer->ID, [(int)$offer->region], 'region');
        wp_set_post_terms($offer->ID, [(int)$offer->town], 'city');

        foreach ($form as $itemKey => $item) {
          update_field("itjob_offer_{$itemKey}", $item, $offer->ID);
        }

        if (is_wp_error($result)) {
          return new WP_REST_Response($result->get_error_message());
        } else {
          $message = $a === 1 ? "publier" : ($a === 0 ? "désactiver" : "mise en attente");
          return new WP_REST_Response("Offre {$message} avec succès");
        }
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
                'candidate' => (int)$apiModel->count_post_status('candidate', 'pending'),
                'company' => (int)$apiModel->count_post_status('company', 'pending'),
                'offers' => (int)$apiModel->count_post_status('offers', 'pending')
              ];
              return new WP_REST_Response($response);

              break;

            case 'notice':
              $User = wp_get_current_user();
              if ($User->ID === 0) return new WP_REST_Response(['success' => false, 'body' => "Utilisateur non definie"]);
              global $wpdb;
              $sql = "SELECT * FROM {$wpdb->prefix}notices WHERE id_user = %d ORDER BY date_create DESC LIMIT 15";
              $prepare = $wpdb->prepare($sql, $User->ID);
              $rows = $wpdb->get_results($prepare);
              $Notifications = [];
              foreach ($rows as $row) {
                $Notice = unserialize($row->notice);
                $Notice->ID = $row->id_notice;
                $Notice->date_create = $row->date_create;
                $Notice->status = $row->status;
                $Notifications[] = $Notice;
              }

              return new WP_REST_Response(['success' => true, 'body' => $Notifications]);
              break;

            default:
              break;
          }
        } else {
          return new WP_REST_Response(false);
        }

      },
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
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
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args' => [
        'taxonomy' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
      ]
    ),
  ]);

  register_rest_route('it-api', '/taxonomy/(?P<taxonomy>\w+)', [
    array(
      'methods' => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $rq) {
        if (!isset($_REQUEST['length']) || !isset($_REQUEST['start'])) return;
        global $wpdb;
        $length = (int)$_REQUEST['length'];
        $start = (int)$_REQUEST['start'];
        $terms_per_page = $length;
        $tpp = filter_var($terms_per_page, FILTER_VALIDATE_INT);
        $taxonomy = filter_var($rq['taxonomy'], FILTER_SANITIZE_STRING);

        if (!taxonomy_exists($taxonomy))
          return false;
        $term_count = wp_count_terms($taxonomy);

        $max_num_pages = ceil($term_count / $tpp);
        // We can now get our terms and paginate it
        $args = [
          'taxonomy' => $taxonomy,
          'hide_empty' => false,
          'orderby' => 'ID',
          'order' => 'DESC',
          'number' => $length,
          'offset' => $start
        ];

        if (isset($_REQUEST['search']) && !empty($_REQUEST['search']['value'])) {
          $s = $_REQUEST['search']['value'];
          $args = array_merge($args, ['search' => $s]);
        }
        $contents = [];
        $term_query = new WP_Term_Query($args);
        $rows = $wpdb->get_results($term_query->request);
        foreach ($term_query->terms as $term) {
          if ($taxonomy !== 'language') {
            $activated = (int)get_term_meta($term->term_id, 'activated', true);
          } else {
            $activated = 1;
          }
          $contents[] = [
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'activated' => $activated
          ];
        }
        if (!empty($term_query) && !is_wp_error($term_query)) {
          return [
            "recordsTotal" => count($rows) - 1,
            "recordsFiltered" => count($rows) - 1,
            'offset' => $start,
            'data' => $contents
          ];
        } else {
          return [
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            'data' => []
          ];
        }

      },
      'args' => [
        'taxonomy' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
      ]
    ),
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => function (WP_REST_Request $rq) {
        $taxonomy = $rq['taxonomy'];
        $term_name = stripslashes($_POST['title']);
        if (empty($term_name) && !taxonomy_exists($taxonomy))
          return new WP_REST_Response(['success' => false, 'message' => "Information manquant ou erroné"]);
        $result = wp_insert_term($term_name, $taxonomy);
        if (is_wp_error($result))
          return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite. Si l'erreur persiste contacter l'administrateur"]);
        return new WP_REST_Response(['success' => true, 'message' => 'Le term a bien été ajouter']);
      },
      'args' => [
        'taxonomy' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
      ]
    )
  ]);

  register_rest_route('it-api', '/taxonomy/(?P<id>\d+)/(?P<action>\w+)', [
    array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => function (WP_REST_Request $request) {
        $term_id = $request['id'];
        $action = $request['action'];
        if ($action) {
          switch ($action) {
            case 'update':
              $term = $_REQUEST['term'];
              $term = json_decode(stripslashes($term));
              $result = wp_update_term($term_id, $term->taxonomy, ['name' => $term->title]);
              if (!is_wp_error($result)) {
                update_term_meta($term->term_id, 'activated', $term->activated);
              } else {
                return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite. Si l'erreur persiste contacter l'administrateur"]);
              }
              return new WP_REST_Response(['success' => true, 'message' => 'Term mise à jour avec succès', 'data' => $term]);

              break;

            case 'delete':
              $term = $_REQUEST['term'];
              $term = json_decode(stripslashes($term));
              $result = wp_delete_term($term_id, $term->taxonomy);
              if (is_wp_error($result))
                return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite. Si l'erreur persiste contacter l'administrateur"]);
              return new WP_REST_Response(['success' => true, 'message' => 'Le term a bien été effacer dans la base de donnée', 'data' => $term]);

              break;
          }

        } else {
          return new WP_REST_Response(['success' => false, 'message' => 'Une erreur s\'est produite']);
        }
      },
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => [
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
        'action' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
      ]
    )
  ]);

  register_rest_route('it-api', '/post/(?P<id>\d+)', [
    array(
      'methods' => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $request) {
        $post_id = $request['id'];
        $action = isset($_REQUEST['action']) ? stripslashes(urldecode($_REQUEST['action'])) : false;
        if ($action) {
          switch ($action) {
            case 'change_status':
              $status = $_REQUEST['val'];
              $activated = $status === 'pending' ? 'pending' : intval($status);
              $post_status = get_post_status($post_id);
              $post_type = get_post_type($post_id);
              if (is_numeric($activated)) {
                update_field('activated', $activated, $post_id);
                if ($activated && $post_status !== 'publish') {
                  $action = "confirm_validate_{$post_type}";
                  do_action($action, $post_id);
                }
                wp_update_post(['ID' => $post_id, 'post_status' => 'publish'], true);
              } else {
                update_field('activated', 0, $post_id);
                wp_update_post(['ID' => $post_id, 'post_status' => 'pending'], true);
              }
              return new WP_REST_Response(['success' => true, 'msg' => 'Status mise à jour avec succès']);
              break;
          }

        } else {
          return new WP_REST_Response(['success' => false, 'msg' => 'Une erreur s\'est produite']);
        }
      },
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
      'args' => [
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ]
    )
  ]);

  register_rest_route('it-api', '/upload/', [
    [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => function (WP_REST_Request $req) {

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        if (empty($_FILES)) {
          return new WP_REST_Response(false);
        }
        $file = $_FILES["upload"];

      // Let WordPress handle the upload.
      // Remember, 'file' is the name of our file input in our form above.
      // @wordpress: https://codex.wordpress.org/Function_Reference/media_handle_upload
        $attachment_id = media_handle_upload('upload', 0);
        if (is_wp_error($attachment_id)) {
        // There was an error uploading the image.
          return new WP_REST_Response(['success' => false, 'msg' => $attachment_id->get_error_message()]);
        } else {
        // The image was uploaded successfully!
          return new WP_REST_Response(['attachment_id' => $attachment_id, 'success' => true]);
        }
      },
      'permission_callback' => [new permissionCallback(), 'private_data_permission_check'],
    ]
  ]);

});