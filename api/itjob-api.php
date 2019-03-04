<?php

use includes\model\itModel;
use includes\post\Candidate;
use includes\post\Company;

if (!defined('ABSPATH')) {
  exit;
}

require_once 'model/class-api-model.php';
require_once 'class/class-permission-callback.php';
require_once 'class/class-api-candidate.php';
require_once 'class/class-api-offer.php';
require_once 'class/class-api-helper.php';
require_once 'class/class-api-company.php';
require_once 'class/class-api-formation.php';
require_once 'class/apiRequestFormation.php';
require_once 'class/apiSmallAd.php';
require_once 'class/apiWork.php';
require_once 'class/apiWallet.php';

function post_updated_values ($post_ID)
{
  $post_type = get_post_type($post_ID);
  if ($post_type === 'candidate') {
    $featured_image = (int)get_post_meta($post_ID, '_thumbnail_id', true);
    if (!$featured_image) {
      do_action('notice-request-featured-image', $post_ID);
    }
  }
}

add_action('post_updated', 'post_updated_values', 10, 3);


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

  /**
   * Récuperer la liste des candidates
   */
  register_rest_route('it-api', '/candidate/', [
    array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [new apiCandidate(), 'get_candidates'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => []
    ),
  ]);

  register_rest_route('it-api', '/candidate/archive/', [
    array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [new apiCandidate(), 'get_candidate_archived'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => []
    ),
  ]);

  // @route {POST} http://[DOMAINE_URL]/wp-json/it-api/candidate/<id>
  register_rest_route('it-api', '/candidate/(?P<id>\d+)', [
    // Recuperer un candidat
    array(
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => function (WP_REST_Request $request) {
        $ref = isset($_REQUEST['ref']) ? stripslashes($_REQUEST['ref']) : false;
        if ($ref) {
          $candidate_id = (int)$request['id'];
          $Candidate = new Candidate($candidate_id, true);
          if (is_null($Candidate->title)) {
            return new WP_Error('no_candidate', 'Aucun candidate ne correpond à cette id', array('status' => 404));
          }
          switch ($ref) {
            case 'collect':
              $Candidate->isActive = $Candidate->is_activated();
              return new WP_REST_Response($Candidate);

              break;

            case 'activated':
              $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
              if (is_null($status)) new WP_REST_Response('Parametre manquant');
              // Seul les comptes adminstrateur peuvent modifier cette option
              if (!current_user_can('delete_users')) return new WP_REST_Response(['success' => false, 'msg' => 'Accès refusé']);
              $post = get_post($candidate_id);
              $post_date = $Candidate->date_create;
              $post_date = date('Y-m-d H:i:s', strtotime($post_date));
              $status = (int)$status;
              if ($status === 1) {
                do_action('confirm_validate_candidate', $Candidate->getId());
              }
              update_field('activated', (int)$status, $Candidate->getId());
              wp_update_post([
                'ID'          => $Candidate->getId(),
                'post_date'   => $post_date,
                'post_author' => $post->post_author,
                'post_status' => 'publish'
              ], true);

              return new WP_REST_Response("Candidate mis à jour avec succès");
              break;

            case 'featured':
              $featured = isset($_REQUEST['val']) ? $_REQUEST['val'] : null;
              $dateLimit = isset($_REQUEST['datelimit']) ? $_REQUEST['datelimit'] : null;

              // Seul l'adminstrateur peuvent modifier cette option
              if (!current_user_can('delete_users')) return new WP_REST_Response(['success' => false, 'msg' => 'Accès refusé']);
              if (is_null($featured) || is_null($dateLimit)) new WP_REST_Response(['success' => false, 'msg' => 'Parametre manquant']);
              $featured = (int)$featured;
              update_field('itjob_cv_featured', $featured, $Candidate->getId());
              if ($featured) {
                update_field('itjob_cv_featured_datelimit', date("Y-m-d H:i:s", (int)$dateLimit), $Candidate->getId());
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

            case 'coverletter':
              $Model = new itModel();
              $lm = []; // Lettre de motivation
              $requests = $Model->collect_candidate_request($Candidate->getId());
              foreach ($requests as $request) {
                if ((int)$request->id_attachment !== 0) {
                  $attachment = get_post((int)$request->id_attachment);
                  $lm[] = $attachment;
                }
              }

              return new WP_REST_Response($lm);
              break;

            case 'downloadpdf':
              $link = DOWNLOAD_CV_PAGE;
              return new WP_REST_Response([
                'link' => $link,
                'key'  => get_field('bo_key', 'option')
              ]);
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
      'args'                => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ),
    ),
    // Mettre à jours un candidat
    array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [new apiCandidate(), 'update_candidate'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => array(
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
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [new apiCandidate(), 'update_module_candidate'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => [
        'ref'          => array(
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
   * Récuperer la liste des entreprises
   */
  register_rest_route('it-api', '/company/', [
    array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [new apiCompany(), 'get_companys'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => []
    )
  ]);

  register_rest_route('it-api', '/company/(?P<id>\d+)', [
    array(
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => function (WP_REST_Request $request) {
        $ref = isset($_REQUEST['ref']) ? stripslashes($_REQUEST['ref']) : false;
        if ($ref) {
          $company_id = (int)$request['id'];
          $Company = new \includes\post\Company($company_id, true);
          if (is_null($Company->title)) {
            return new WP_Error('no_company', 'Aucun candidate ne correpond à cette id', array('status' => 404));
          }
          switch ($ref) {
            case 'collect':
              $Company->isValid = $Company->isValid();
              return new WP_REST_Response($Company);

              break;

            case 'activated':
              $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
              if (is_null($status)) new WP_REST_Response('Parametre manquant');
              $status = (int)$status;
              if ($Company->post_status === 'pending' && $status === 1) {
                // Entreprise désactiver
              }
              $status = (int)$status;
              update_field('activated', $status, $Company->getId());
              if ($status) {
                do_action('notice-change-company-status', $Company->getId(), $status);
                do_action('confirm_validate_company', $Company->getId());
              }
              $post_date = get_the_date('Y-m-d H:i:s', $Company->getId());
              $post = get_post($company_id);
              wp_update_post(['ID' => $Company->getId(), 'post_date' => $post_date, 'post_author' => $post->post_author, 'post_status' => 'publish'], true);
              return new WP_REST_Response(['success' => true, 'msg' => "Entreprise mis à jour avec succès"]);
              break;

            // Absolete: Paramétre transferer pour les offres
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

            case 'collect_offers':
              global $shortcode;
              return new WP_REST_Response([
                'companyInfo' => [
                  'id'        => $Company->getId(),
                  'name'      => $Company->title,
                  'activated' => $Company->isValid(),
                ],
                'offers'      => $shortcode->scClient->__get_company_offers($Company)
              ]);
              break;

            default:
              break;
          }
        } else {
          return new WP_REST_Response(false);
        }
      },
      'permission_callback' => function ($data) {
        return current_user_can('delete_posts');
      },
      'args'                => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ),
    ),
    array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => function (WP_REST_Request $request) {
        $company_id = (int)$request['id'];
        $company = stripslashes($_REQUEST['company']);
        $company = json_decode($company);
        $currentCompany = get_post($company_id);
        $form = [
          //'itjob_meta_account' => (int)$company->account,
          'itjob_company_address'  => $company->address,
          'itjob_company_greeting' => $company->greeting,
          'itjob_company_name'     => $company->name,
          'itjob_company_nif'      => $company->nif,
          'itjob_company_stat'     => $company->stat
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
      'args'                => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param) && $param !== 0;
          }
        ),
      ),
    )
  ]);

  register_rest_route('it-api', '/offers/', [
    array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [new apiOffer(), 'get_offers'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => []
    ),
  ]);


  register_rest_route('it-api', '/formations', [
    array(
      'methods'  => WP_REST_Server::CREATABLE,
      'callback' => [new apiFormation(), 'get_formations']
    )
  ]);

  register_rest_route('it-api', '/formation/(?P<id>\d+)', [
    array(
      'methods'  => WP_REST_Server::READABLE,
      'callback' => [new apiFormation(), 'formation_resources']
    ),
    array(
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => [new apiFormation(), 'update_formation'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
    )
  ]);

  /**
   * Récuperer la liste des offres
   */
  register_rest_route('it-api', '/offer/(?P<id>\d+)', [
    // Recuperer un offre
    array(
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => function (WP_REST_Request $request) {
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
                do_action('confirm_validate_offer', $offer_id);
              }
              return new WP_REST_Response("Offre mis à jour avec succès");

              break;

            case 'update_request':
              $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
              $id_request = isset($_REQUEST['id_request']) ? $_REQUEST['id_request'] : null;
              if (is_null($status) || is_null($id_request))
                return new WP_Error("params", 'Parametre manquant');
              $Model = new \includes\model\itModel();
              // Récupérer la réquete
              $Request = $Model::get_request((int)$id_request);
              // Modifier la status de la requete
              if ($Request->type === 'apply' && $status === "validated") {
              } else {
                $Model->update_interest_status((int)$id_request, $status);
              }
              // Ajouter le CV dans la liste de l'entreprise
              if ($Request->type === 'apply') {
                //$Model->add_list((int)$Request->id_candidate, (int)$Request->id_company);
                // Afficher le candidat pour l'entreprise
                $view = $status === 'validated' ? 1 : 0;
                $Model->render_view_candidat((int) $id_request, $view);
              }

            case 'request':
              $Model = new \includes\model\itModel();
              $Interests = $Model->get_offer_interests($offer_id);
              if (is_array($Interests) && !empty($Interests)) {
                $Response = [];
                foreach ($Interests as $Interest) {
                  $It = new stdClass();
                  $It->type = $Interest->type;
                  $It->view = (int)$Interest->view;
                  $It->status = $Interest->status;
                  $It->id_request = $Interest->id_cv_request;
                  $It->lm_link = $Interest->type === 'apply' ? ($Interest->id_attachment ? parse_url(wp_get_attachment_url(((int)$Interest->id_attachment))) : false) : false;
                  $It->lm_name = $Interest->type === 'apply' ? ($Interest->id_attachment ? get_the_title((int)$Interest->id_attachment) : false) : false;
                  $It->candidate = new \includes\post\Candidate($Interest->id_candidate, true);
                  $It->date_add = $Interest->date_add;
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
              update_field('itjob_offer_featured', $featured, $Offer->ID);
              if ($featured) {
                update_field('itjob_offer_featured_datelimit', date("Y-m-d H:i:s", (int)$dateLimit), $Offer->ID);
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
        return true;
        $ref = isset($_REQUEST['ref']) ? stripslashes(urldecode($_REQUEST['ref'])) : false;
        if (!$ref) return false;
        return ($ref === 'update_date_limit' || $ref === 'collect' || $ref === 'request') ? current_user_can('edit_posts') : current_user_can('remove_users');
      },
      'args'                => array(
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ),
    ),
    // Mettre à jours une offre
    array(
      'methods'             => WP_REST_Server::EDITABLE,
      'callback'            => function (WP_REST_Request $request) {
        $offer = stripslashes($_REQUEST['offer']);
        $offer = json_decode($offer);
        $currentOffer = get_post($offer->ID);
        $dateTime = DateTime::createFromFormat("m/d/Y", $offer->date_limit);
        $acfDateLimit = $dateTime->format('Ymd');
        $form = [
          'post'             => $offer->post,
          'contrattype'      => (int)$offer->contract,
          'proposedsallary'  => $offer->proposedsalary,
          'abranch'          => $offer->branch_activity,
          'datelimit'        => $acfDateLimit,
          'mission'          => $offer->mission,
          'profil'           => $offer->profil,
          'otherinformation' => $offer->otherInformation,
        ];

        // Activation et publication
        $a = &$offer->status;
        $activated = $a === 'pending' ? 'pending' : intval($a);
        if ($activated === 0 || $activated === 1) {
          update_field('activated', $activated, $currentOffer->ID);
          if ($activated && $currentOffer->post_status !== 'publish')
            do_action('confirm_validate_offer', $currentOffer->ID);
          $post_date = get_the_date('Y-m-d H:i:s', $offer->ID);
          $result = wp_update_post(['ID' => $currentOffer->ID, 'post_date' => $post_date, 'post_author' => $currentOffer->post_author, 'post_status' => 'publish'], true);
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
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => array(
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
      'methods'  => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $request) {
        $ref = isset($_REQUEST['ref']) ? stripslashes(urldecode($_REQUEST['ref'])) : false;
        if ($ref) {
          $apiModel = new apiModel();
          switch ($ref) {
            case 'collect':
              $countCompany = $apiModel->count_post_type('company');
              $countActiveCompany = $apiModel->count_post_active('company');
              $Entreprise = [
                'count'         => $countCompany,
                'countActive'   => $countActiveCompany,
                'countFeatured' => $apiModel->count_featured_company()
              ];

              $countCandidates = $apiModel->count_post_type('candidate');
              $countActiveCandidate = $apiModel->count_post_active('candidate');
              $Candidates = [
                'count'         => $countCandidates,
                'countActive'   => $countActiveCandidate,
                'countFeatured' => $apiModel->count_featured_candidates()
              ];

              $countOffers = $apiModel->count_post_type('offers');
              $countActiveOffers = $apiModel->count_post_active('offers');
              $Offres = [
                'count'         => $countOffers,
                'countActive'   => $countActiveOffers,
                'countFeatured' => $apiModel->count_featured_offers()
              ];

              return new WP_REST_Response([
                'company'   => $Entreprise,
                'candidate' => $Candidates,
                'offer'     => $Offres
              ]);
              break;

            case 'header':
              $response = [
                'candidate' => (int)$apiModel->count_post_status('candidate', 'pending'),
                'company'   => (int)$apiModel->count_post_status('company', 'pending'),
                'offers'    => (int)$apiModel->count_post_status('offers', 'pending')
              ];
              return new WP_REST_Response($response);

              break;

            case 'notice':
              $User = wp_get_current_user();
              if ($User->ID === 0) return new WP_REST_Response(['success' => false, 'body' => "Utilisateur non definie"]);
              global $wpdb;
              $sql = "SELECT * FROM {$wpdb->prefix}notices WHERE id_user = %d ORDER BY date_create DESC LIMIT 100";
              $prepare = $wpdb->prepare($sql, $User->ID);
              $rows = $wpdb->get_results($prepare);
              $Notifications = [];
              $Template = new \includes\object\NotificationTpl();
              foreach ($rows as $row) {
                // Récuper les variables
                $needle = unserialize($row->needle);

                $Notice = new stdClass();
                $Notice->ID = $row->id_notice;
                $Notice->date_create = $row->date_create;
                $Notice->status = $row->status;
                $Notice->guid = $row->guid;
                $Notice->type = $row->template;
                $tpl = $Template->tpls[(int)$row->template];
                set_error_handler(function ($errno, $errstr) {
                });
                $Notice->title = @vsprintf($tpl, $needle);
                if (!$Notice->title) continue;
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
      'args'     => array(
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
      'methods'             => WP_REST_Server::ALLMETHODS,
      'callback'            => [new apiHelper(), 'get_taxonomy'],
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => [
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
      'methods'  => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $rq) {
        if (!isset($_REQUEST['length']) || !isset($_REQUEST['start'])) return;
        global $wpdb;

        $length = (int)$_REQUEST['length'];
        $start = (int)$_REQUEST['start'];

        $taxonomy = filter_var($rq['taxonomy'], FILTER_SANITIZE_STRING);
        if (!taxonomy_exists($taxonomy)) return false;
        $args = [
          'taxonomy'   => $taxonomy,
          'hide_empty' => false,
          'meta_key'   => 'activated',
          'orderby'    => 'meta_value_num',
          'order'      => 'ASC',
          'number'     => $length,
          'offset'     => $start
        ];

        if (isset($_REQUEST['search']) && !empty($_REQUEST['search']['value'])) {
          $s = $_REQUEST['search']['value'];
          $s = wp_unslash($s);
          if (strpos($s, "|") !== false) {
            $params = explode('|', $s);
            $activated = (int)$params[1];
            $args = array_merge($args, [
              "meta_query" => [
                [
                  'key'   => "activated",
                  'value' => (int)$activated
                ]
              ]
            ]);

            if (!empty($params[0]) && $params[0] !== ' ') {
              $args = array_merge($args, ['search' => $params[0]]);
            }

          } else {
            $args = array_merge($args, ['search' => $s]);
          }
        }
        $contents = [];

        $term_query = new WP_Term_Query($args);
        $rows = $wpdb->get_results($term_query->request);
        foreach ($term_query->get_terms() as $term) {
          if ($taxonomy !== 'language') {
            $activated = get_term_meta((int)$term->term_id, 'activated', true);
            $activated = boolval($activated);
          } else {
            $activated = 1;
          }
          $candidates = [];
          if (!$activated) {
            $candidates = get_posts(array(
              'post_type'        => 'candidate',
              'post_status'      => 'any',
              'suppress_filters' => 0,
              'numberposts'      => -1,
              'tax_query'        => array(
                array(
                  'taxonomy'         => $taxonomy,
                  'field'            => 'id',
                  'terms'            => (int)$term->term_id, // Where term_id of Term 1 is "1".
                  'include_children' => false
                )
              )
            ));
          }
          $contents[] = [
            'term_id'    => $term->term_id,
            'name'       => $term->name,
            'slug'       => $term->slug,
            'candidates' => $candidates,
            'activated'  => intval($activated)
          ];
        }
        if (!empty($term_query) && !is_wp_error($term_query)) {
          return [
            "recordsTotal"    => count($rows),
            "recordsFiltered" => count($rows),
            'offset'          => $start,
            'data'            => $contents
          ];
        } else {
          return [
            "recordsTotal"    => 0,
            "recordsFiltered" => 0,
            'data'            => []
          ];
        }

      },
      'args'     => [
        'taxonomy' => array(
          'validate_callback' => function ($param, $request, $key) {
            return !empty($param);
          }
        ),
      ]
    ),
    array(
      'methods'  => WP_REST_Server::CREATABLE,
      'callback' => function (WP_REST_Request $rq) {
        $taxonomy = $rq['taxonomy'];
        $term_name = stripslashes($_POST['title']);
        if (empty($term_name) && !taxonomy_exists($taxonomy))
          return new WP_REST_Response(['success' => false, 'message' => "Information manquant ou erroné"]);
        // Vérifier si le term existe déja
        $term = term_exists($term_name, $taxonomy);
        if (0 !== $term && null !== $term) {
          return new WP_REST_Response(['success' => true, 'message' => "Le term existe déja dans la base de donnée"]);
        }
        // Inserer le term
        $result = wp_insert_term($term_name, $taxonomy);
        if (is_wp_error($result)) {
          return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite. Si l'erreur persiste contacter l'administrateur"]);
        }
        // Activer par default les termes ajouter dans le BO
        update_term_meta($result['term_id'], 'activated', 1);
        return new WP_REST_Response(['success' => true, 'message' => 'Le term a bien été ajouter']);
      },
      'args'     => [
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
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => function (WP_REST_Request $request) {
        $term_id = $request['id'];
        $action = $request['action'];
        if ($action) {
          switch ($action) {
            // Metre à jour une term
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

            // Supprimer une terme dans la liste
            case 'delete':
              $term = $_REQUEST['term'];
              $term = json_decode(stripslashes($term));
              $result = wp_delete_term($term_id, $term->taxonomy);
              if (is_wp_error($result))
                return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite. Si l'erreur persiste contacter l'administrateur"]);
              return new WP_REST_Response(['success' => true, 'message' => 'Le term a bien été effacer dans la base de donnée', 'data' => $term]);

              break;

            // Remplacer le term par une autre
            case 'replace':
              $params = $_REQUEST['params'];
              $params = json_decode(stripslashes($params));

              $result = wp_delete_term($term_id, $params->taxonomy, ['default' => $params->by, true]);
              if (is_wp_error($result))
                return new WP_REST_Response(['success' => false, 'message' => "Une erreur s'est produite. Si l'erreur persiste contacter l'administrateur"]);
              return new WP_REST_Response(['success' => true, 'message' => 'Le term a bien été remplacer']);

              break;
          }

        } else {
          return new WP_REST_Response(['success' => false, 'message' => 'Une erreur s\'est produite']);
        }
      },
      'permission_callback' => function ($data) {
        return current_user_can('edit_posts');
      },
      'args'                => [
        'id'     => array(
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

  // Ce route permet de modifier un post ou un custom post
  register_rest_route('it-api', '/post/(?P<id>\d+)', [
    array(
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => function (WP_REST_Request $request) {
        $post_id = $request['id'];
        $action = isset($_REQUEST['action']) ? stripslashes(urldecode($_REQUEST['action'])) : false;
        if ($action) {
          switch ($action) {
            case 'change_status':
              $status    = $_REQUEST['val'];
              $activated = $status === 'pending' ? 'pending' : intval($status);
              $post      = get_post($post_id);
              $post_type = $post->post_type;
              if (is_numeric( $activated )) {
                update_field('activated', $activated, $post_id);
                if ($activated) {
                  do_action("confirm_validate_{$post_type}", $post_id);
                }
                wp_update_post(['ID' => $post_id, 'post_author' => $post->post_author, 'post_status' => 'publish'], true);
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
      'args'                => [
        'id' => array(
          'validate_callback' => function ($param, $request, $key) {
            return is_numeric($param);
          }
        ),
      ]
    )
  ]);


  register_rest_route('it-api', '/newsletters/',
    [
      // Récuperer les newsletters
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => function (WP_REST_Request $request) {
          $length = (int)$_REQUEST['length'];
          $start = (int)$_REQUEST['start'];
          $paged = isset($_REQUEST['start']) ? ($start === 0 ? 1 : ($start + $length) / $length) : 1;
          $posts_per_page = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 20;
          $args = [
            'post_type'      => 'post',
            'post_status'    => ['publish'],
            "posts_per_page" => $posts_per_page,
            'order'          => 'DESC',
            'orderby'        => 'ID',
            'tax_query'      => [
              [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => 'newsletter'
              ]
            ],
            "paged"          => $paged
          ];
          if (isset($_REQUEST['search']) && !empty($_REQUEST['search']['value'])) {
            $search = stripslashes($_REQUEST['search']['value']);
            $args['s'] = $search;
          }

          $the_query = new WP_Query($args);
          if ($the_query->have_posts()) {
            return [
              "recordsTotal"    => (int)$the_query->found_posts,
              "recordsFiltered" => (int)$the_query->found_posts,
              'data'            => $the_query->posts
            ];
          } else {

            return [
              "recordsTotal"    => (int)$the_query->found_posts,
              "recordsFiltered" => (int)$the_query->found_posts,
              'data'            => []
            ];
          }

        },
        'permission_callback' => function ($data) {
          return current_user_can('edit_posts');
        }
      ],
      // Ajouter un newsletter
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => function () {
          $error = false;
          $no_reply = "no-reply@itjobmada.com";
          $subject = \Http\Request::getValue('subject');
          $content = \Http\Request::getValue('content');
          $to = \Http\Request::getValue('to', null); // Post type value (candidat, company ou '')
          $role__in = ['company', 'candidate'];
          if (!empty($to) && !is_null($to)) {
            if (!in_array($to, $role__in)) return new WP_Error(403, 'Utilisateur non definie');
          } else {
            $to = &$role__in;
          }

          $user_total = 0;
          $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
          $argUsers = ['role' => $to, 'fields' => 'all', 'number' => -1];
          $Users = new \WP_User_Query($argUsers);
          if (!empty($Users->get_results())) {
            foreach ($Users->get_results() as $user) {
              if (empty($user->user_email)) continue;
              $mail->addBCC($user->user_email);
              $user_total += 1;
            }
          }

          $mail->addAddress('david@itjobmada.com', 'David Andrianaivo');
          $mail->addReplyTo('david@itjobmada.com', 'David Andrianaivo');
          $mail->CharSet = 'UTF-8';
          $mail->isHTML(true);
          $mail->setFrom($no_reply, "Equipe ITJob");
          $mail->Body = $content;
          $mail->Subject = $subject;
          try {
            // Envoyer le mail
            $mail->send();
          } catch (\PHPMailer\PHPMailer\Exception $e) {
            $error = $e->getMessage();
          }

          return new WP_REST_Response(['success' => true, 'total' => $user_total, 'error' => $error]);
        },
        'permission_callback' => function ($data) {
          return current_user_can('edit_posts');
        }
      ]
    ]
  );

  register_rest_route('it-api', '/blogs/', [
      // Récuperer les blogs
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => function (WP_REST_Request $request) {
          $length = (int)$_REQUEST['length'];
          $start = (int)$_REQUEST['start'];
          $paged = isset($_REQUEST['start']) ? ($start === 0 ? 1 : ($start + $length) / $length) : 1;
          $posts_per_page = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 20;
          $args = [
            'post_type'      => 'post',
            'post_status'    => ['publish'],
            "posts_per_page" => $posts_per_page,
            'order'          => 'DESC',
            'orderby'        => 'ID',
            'tax_query'      => [
              [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => 'blog' // article de categorie blog
              ]
            ],
            "paged"          => $paged
          ];
          if (isset($_REQUEST['search']) && !empty($_REQUEST['search']['value'])) {
            $search = stripslashes($_REQUEST['search']['value']);
            $args['s'] = $search;
          }

          $the_query = new WP_Query($args);
          if ($the_query->have_posts()) {
            return [
              "recordsTotal"    => (int)$the_query->found_posts,
              "recordsFiltered" => (int)$the_query->found_posts,
              'data'            => $the_query->posts
            ];
          } else {

            return [
              "recordsTotal"    => (int)$the_query->found_posts,
              "recordsFiltered" => (int)$the_query->found_posts,
              'data'            => []
            ];
          }

        },
        'permission_callback' => function ($data) {
          return current_user_can('edit_posts');
        }
      ],
    ]);

  register_rest_route('it-api', '/users/', [
    [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => function () {
        $result = count_users();
        return new WP_REST_Response($result);
      },
      'permission_callback' => function ($data) {
        return current_user_can('delete_users');
      }
    ]
  ]);

  register_rest_route('it-api', '/ads', [
    [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => function () {
        global $wpdb;

        $Model = new \includes\model\itModel();
        $length  = Http\Request::getValue('length', 0);
        $offset = Http\Request::getValue('offset', 0);
        if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
          $start = $_REQUEST['start'];
          $end = $_REQUEST['end'];
          $start = date("Y-m-d H:i:s", (int)$start);
          $end = date("Y-m-d H:i:s", (int)$end);
          $ads = $Model->get_beetween_ads($start, $end);
          $results = [
            "recordsTotal"    => (int)$wpdb->num_rows,
            "recordsFiltered" => (int)$wpdb->num_rows,
            'data'            => $ads
          ];
        } else {
          $ads = $Model->collect_ads($length, $offset);
          $results = [
            "recordsTotal"    => (int)$wpdb->num_rows,
            "recordsFiltered" => (int)$wpdb->num_rows,
            'data'            => $ads
          ];
        }
        return new WP_REST_Response($results);
      },
      'permission_callback' => function ($data) {
        return current_user_can('delete_posts');
      }
    ],
    [ // Ajouter une publicité
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => function () {
        global $wpdb;
        $ads = stripslashes($_POST['ads']);
        $ads = json_decode($ads);
        $table = $wpdb->prefix . 'ads';
        $data = [
          'title'         => $ads->title,
          'id_attachment' => (int)$ads->id_attachment,
          'img_size'      => $ads->img_size,
          'start'         => date($ads->start),
          'end'           => date($ads->end),
          'classname'     => isset($ads->className) ? $ads->className : null,
          'id_user'       => (int)$ads->id_user,
          'position'      => $ads->position,
          'paid'          => (int)$ads->paid,
          'bill'          => $ads->bill
        ];
        $format = ['%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s'];
        $result = $wpdb->insert($table, $data, $format);

        return new WP_REST_Response($result);
      },
      'permission_callback' => function ($data) {
        return current_user_can('delete_posts');
      }
    ]
  ]);

  register_rest_route('it-api', '/ads/(?P<id>\d+)', [
    [
      'methods'  => WP_REST_Server::DELETABLE,
      'callback' => function (WP_REST_Request $rq) {
        global $wpdb;
        $table = $wpdb->prefix . 'ads';
        $adsId = (int)$rq['id'];
        $ads = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id_ads = %d", $adsId));
        if ($ads) {
          if ($ads->id_attachment !== 0 && !empty($ads->id_attachment) && !is_null($ads->id_attachment)) {
            wp_delete_attachment((int)$ads->id_attachment, true);
          }
        }
        $result = $wpdb->delete($table, array('id_ads' => $adsId));
        return new WP_REST_Response($result);
      }
    ],
    [
      'methods'  => WP_REST_Server::CREATABLE,
      'callback' => function (WP_REST_Request $rq) {
        global $wpdb;
        $adsId = (int)$rq['id'];
        $ads = stripslashes($_POST['ads']);
        $ads = json_decode($ads);
        $table = $wpdb->prefix . 'ads';
        $data = [
          'title'     => $ads->title,
          'start'     => date($ads->start),
          'end'       => date($ads->end),
          'img_size'  => $ads->img_size,
          'classname' => isset($ads->className) ? $ads->className : null,
          'position'  => $ads->position,
          'paid'      => (int)$ads->paid
        ];
        $result = $wpdb->update($table, $data, ['id_ads' => $adsId], ['%s', '%s', '%s', '%s', '%s', '%s', '%d'], ['%d']);
        return new WP_REST_Response($result);
      }
    ]
  ]);

  register_rest_route('it-api', '/get-company/(?P<query>\w+)', [
    [
      'methods'  => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $rq) {
        global $wpdb;
        $s = $rq['query'];
        $s = $wpdb->esc_like($s);
        $s = implode('|', explode(' ', $s));
        $sql
          = /** @lang sql */
          <<<SQL
SELECT pts.ID FROM $wpdb->posts pts 
   WHERE pts.post_title REGEXP '({$s}).*$' 
      AND post_type = 'company'
      AND post_status = 'publish'
      AND pts.ID IN  (
         SELECT {$wpdb->postmeta}.post_id as post_id
         FROM {$wpdb->postmeta}
            WHERE {$wpdb->postmeta}.meta_key = 'activated' AND {$wpdb->postmeta}.meta_value = 1
      )
SQL;

        $posts = $wpdb->get_results($sql);
        $entreprises = [];
        foreach ($posts as $post) {
          $entreprises[] = new Company((int)$post->ID);
        }
        return new WP_REST_Response($entreprises);
      },
    ]
  ]);

  register_rest_route('it-api', '/tax/search/(?P<taxonomy>\w+)/(?P<QUERY>\w+)', [
    [
      'methods'  => WP_REST_Server::READABLE,
      'callback' => function (WP_REST_Request $rq) {
        $terms = [];
        $term_args = array(
          'taxonomy'   => $rq['taxonomy'],
          'hide_empty' => false,
          'fields'     => 'all',
          'count'      => true,
          'name__like' => $rq['QUERY']
        );
        $term_query = new WP_Term_Query($term_args);

        foreach ($term_query->terms as $term) {
          $activated = get_term_meta($term->term_id, 'activated', true);
          if (!$activated) continue;
          $terms[] = [
            'activated' => $activated,
            'name'      => $term->name,
            'slug'      => $term->slug,
            'term_id'   => $term->term_id
          ];
        }

        return new WP_REST_Response($terms);
      }
    ]
  ]);

}); // end rest api init