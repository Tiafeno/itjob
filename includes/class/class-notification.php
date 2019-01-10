<?php
namespace includes\object;

use includes\model\itModel;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;

if (!defined('ABSPATH')) {
  exit;
}

// Un nouveau CV portant la référence « ".$cvs->reference." » a été inséré 
final class NotificationTpl
{
  public $tpls;
  public function __construct()
  {
    $this->tpls[1] = "<b>%1\$s</b> viens d'ajouter un CV pour reference <b>%2\$s</b>";
    $this->tpls[2] = "Inscription d'une nouvelle entreprise - <b>%1\$s</b>";
    $this->tpls[3] = "Une offre viens d'être ajouter sur le site: <b>%1\$s</b>";
    $this->tpls[4] = "<b>%1\$s</b> viens de modifier son CV (<b>%2\$s</b>)";
    $this->tpls[5] = "Votre CV viens d'être validé";
    $this->tpls[6] = "%1\$s a postulé pour l'offre <b>%2\$s</b>";
    $this->tpls[7] = "Un candidat viens de postuler sur <b>%1\$s</b>";
    $this->tpls[8] = "Une entreprise s'interesse à votre CV sur l'offre: <b>%1\$s</b>";
    $this->tpls[9] = "<b>%1\$s</b> s'interesse à un candidat pour réference <b>%2\$s</b>";
    $this->tpls[10] = "L'entreprise « %1\$s » à effectuer une demande de compte premium";
    $this->tpls[11] = "Le CV portant la référence « %1\$s » est maintenant disponible, sur l'offre <b>%2\$s</b>";
    $this->tpls[12] = "Votre CV a été selectionné sur l'offre « <b>%1\$s</b> »";
    $this->tpls[13] = "Le CV portant la référence « %1\$s » a été réjeté sur l'offre <b>%2\$s</b>";
    $this->tpls[14] = "Votre candidature a été rejeté sur l'offre « <b>%1\$s</b> »";
    $this->tpls[15] = "Votre compte a été validée";
    $this->tpls[16] = "Votre offre « <b>%1\$s</b> » a bien été validée";
    $this->tpls[17] = "Votre CV viens d'être selectionné sur l'offre: <b>%1\$s</b>";
    $this->tpls[18] = "Votre photo n'a pas été validée. Veuillez ajouter une photo professionnelle";
  }
}

final class NotificationHelper
{
  public function __construct()
  {
    add_action('init', function () {
      add_action('notice-candidate-postuled', [&$this, 'notice_candidate_postuled'], 10, 2);
      add_action('notice-interest', [&$this, 'notice_interest'], 10, 1);
      add_action('notice-publish-cv', [&$this, 'notice_publish_cv'], 10, 1);
      add_action('notice-publish-offer', [&$this, 'notice_publish_offer'], 10, 1);
      add_action('notice-candidate-selected-cv', [&$this, 'notice_candidate_selected_cv'], 10, 2);
      add_action('notice-request-featured-image', [&$this, 'notice_request_featured_image'], 10, 1);

      add_action('notice-change-request-status', [&$this, 'notice_change_request_status'], 10, 2);
      add_action('notice-change-company-status', [&$this, 'notice_change_company_status'], 10, 2);
      add_action('notice-change-offer-status', [&$this, 'notice_change_offer_status'], 10, 2);
      add_action('request-premium-account', [&$this, 'request_premium_account'], 10, 1);

      add_action('notice-admin-create-cv', [&$this, 'notice_admin_create_cv'], 10, 1);
      add_action('notice-admin-new-offer', [&$this, 'notice_admin_new_offer'], 10, 1);
      add_action('notice-admin-update-cv', [&$this, 'notice_admin_update_cv'], 10, 1);
      add_action('notice-admin-new-company', [&$this, 'notice_admin_new_company'], 10, 1);

      // On change la status d'une notification
      if (isset($_GET['ref'])) {
        $ref = $_GET['ref'];
        if ($ref === 'notif') {
          $id_notice = (int)$_GET['notif_id'];
          if (!$id_notice) return false;
          $Model = new itModel();
          $Model->change_notice_status($id_notice);
        }
      }
    });
  }

  public function notice_admin_create_cv($id_cv)
  {
    $Model = new itModel();
    $Candidate = new Candidate((int)$id_cv);

    $Author = $Candidate->getAuthor();
    $firstname = $Candidate->getFirstName();

    $Notice = new \stdClass();
    $Notice->tpl_msg = 1;
    $Notice->needle = [$firstname, $Candidate->title];
    $Notice->guid = "/candidate/{$id_cv}/edit";

    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }

  public function notice_admin_new_company($id_company)
  {
    $Model = new itModel();
    $Company = new Company((int)$id_company);

    $Notice = new \stdClass();
    $Notice->tpl_msg = 2;
    $Notice->needle = [$Company->title];
    $Notice->guid = "/company-lists";

    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }

  public function notice_admin_new_offer($id_offer)
  {
    $Model = new itModel();
    $Offer = new Offers((int)$id_offer);

    $Notice = new \stdClass();
    $Notice->tpl_msg = 3;
    $Notice->needle = [$Offer->title];
    $Notice->guid = "/offer/{$id_offer}/edit";

    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }

  public function notice_admin_update_cv($id_cv)
  {
    $Model = new itModel();
    // Company
    $Candidate = new Candidate((int)$id_cv);

    $Notice = new \stdClass();
    $Author = $Candidate->getAuthor();
    $firstname = $Candidate->getFirstName();

    $Notice->tpl_msg = 4;
    $Notice->needle = [$firstname, $Candidate->title];
    $Notice->guid = "/candidate/{$id_cv}/edit";

    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }

  public function notice_publish_cv($id_cv)
  {
    $id_cv = (int)$id_cv;
    if (!$id_cv) return false;
    $Model = new itModel();
    $Candidate = new Candidate($id_cv);
    $Notice = new \stdClass();

    $Notice->tpl_msg = 5;
    $Notice->needle = [];
    $Notice->guid = $Candidate->candidate_url . '?ref=notif';
    $Author = $Candidate->getAuthor();
    $Model->added_notice($Author->ID, $Notice);

    return true;
  }

  public function notice_candidate_postuled($id_cv, $id_offer)
  {
    $Model = new itModel();

    // Company
    $Candidate = new Candidate((int)$id_cv);
    $Offer = new Offers((int)$id_offer);

    if ($Offer->rateplan === 'standard' || $Offer->rateplan === 'premium') {
      $postCompany = $Offer->getCompany();
      $Company = new Company($postCompany->ID);

      $companyNotice = new \stdClass();

      $companyNotice->tpl_msg = 6;
      $companyNotice->needle = [$Candidate->title, $Offer->title];
      $companyNotice->guid = $Candidate->candidate_url . "?ref=notif";
      $Model->added_notice($Company->author->ID, $companyNotice);
    }

    // To admin
    $Notice = new \stdClass();
    $Notice->tpl_msg = 7;
    $Notice->needle = [$Offer->title];
    $Notice->guid = "/offer/{$id_offer}/edit";

    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }

    return true;
  }

  public function notice_interest($id_cv_request)
  {
    if (!is_numeric($id_cv_request)) return false;
    $Interest = itModel::get_request($id_cv_request);
    if (is_null($Interest)) return null;
    $Model = new itModel();
    $Offer = new Offers((int)$Interest->id_offer);
    $Company = new Company((int)$Interest->id_company);

    // Candidate
    $Candidate = new Candidate((int)$Interest->id_candidate);
    $Author = $Candidate->getAuthor();

    $candidateNotice = new \stdClass();

    $candidateNotice->tpl_msg = 8;
    $candidateNotice->needle = [$Offer->title];
    $candidateNotice->guid = $Offer->offer_url . "?ref=notif";
    $Model->added_notice($Author->ID, $candidateNotice);

    // To admin
    $Notice = new \stdClass();

    $Notice->tpl_msg = 9;
    $Notice->needle = [$Company->title, $Candidate->title];
    $Notice->guid = "/offer/{$Interest->id_offer}/edit";

    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }

    return true;
  }

  // Deprecate: Ne plus mettre les professionels en mode premium
  public function request_premium_account($Company)
  { // Company object
    $Model = new itModel();
    $Notice = new \stdClass();
    $id = $Company->getId();

    $Notice->tpl_msg = 10;
    $Notice->needle = [$Company->title];
    $Notice->guid = "/company-lists/?s={$Company->title}";

    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }

  /**
   * @param $id_cv_interest
   * @param null $status
   *
   * @return bool|null
   */
  public function notice_change_request_status($id_cv_interest, $status = null)
  {
    if (!is_numeric($id_cv_interest)) return false;
    $Interest = itModel::get_request($id_cv_interest);

    if (is_null($Interest)) return null;

    // Instance
    $Candidate = new Candidate((int)$Interest->id_candidate);
    $Company = new Company((int)$Interest->id_company);
    $Offer = new Offers((int)$Interest->id_offer);
    $Model = new itModel();

    $companyNotice = new \stdClass();
    $companyNotice->guid = $Candidate->candidate_url . "?ref=notif";


    $candidateNotice = new \stdClass();
    $candidateNotice->guid = $Offer->offer_url . "?ref=notif";


    switch ($Interest->status) {
      case 'validated':

        $companyNotice->tpl_msg = 11;
        $companyNotice->needle = [$Candidate->reference, $Offer->title];

        $candidateNotice->tpl_msg = 12;
        $candidateNotice->needle = [$Offer->title];
        break;

      case 'reject':
        $companyNotice->tpl_msg = 13;
        $companyNotice->needle = [$Candidate->reference, $Offer->title];

        if ($Interest->type === 'apply') {
          $companyNotice->tpl_msg = 14;
          $companyNotice->needle = [$Offer->title];
        }
        break;
    }
    $Model->added_notice($Company->author->ID, $companyNotice);
    if ($Interest->status === 'validated' || $Interest->type === 'apply') {
      $Author = $Candidate->getAuthor();
      $Model->added_notice($Author->ID, $candidateNotice);
    }

    return true;
  }

  public function notice_change_company_status($id_company, $status = null)
  {
    if (!is_numeric($id_company) || is_null($status)) return false;
    $Model = new itModel();
    $Company = new Company($id_company);
    $Notice = new \stdClass();
    $Notice->guid = "?ref=notif";
    $Notice->tpl_msg = 15;
    $Notice->needle = [];

    $Model->added_notice($Company->author->ID, $Notice);
  }

  public function notice_change_offer_status($Offer, $status)
  {
    if ($Offer instanceof Offers) {
      $Model = new itModel();
      $Notice = new \stdClass();
      $postCompany = $Offer->getCompany();
      $Company = new Company($postCompany->ID);

      $Notice->guid = "{$Offer->offer_url}?ref=notif";
      $Notice->tpl_msg = 16;
      $Notice->needle = [$Offer->title];


      $Model->added_notice($Company->author->ID, $Notice);
    } else {
      return false;
    }
  }

  public function notice_candidate_selected_cv($id_candidate, $id_offer)
  {
    // Candidate
    $Model = new itModel();
    $Offer = new Offers((int)$id_offer);
    $Candidate = new Candidate((int)$id_candidate);
    $Author = $Candidate->getAuthor();
    $Notice = new \stdClass();
    $Notice->guid = $Offer->offer_url . "?ref=notif";
    $Notice->tpl_msg = 17;
    $Notice->needle = [$Offer->title];

    $Model->added_notice($Author->ID, $Notice);
  }

  public function notice_request_featured_image($id_candidate)
  {
    // Candidate
    $Model = new itModel();
    $Candidate = new Candidate((int)$id_candidate);
    $Author = $Candidate->getAuthor();
    $Notice = new \stdClass();
    $espace_client_url = get_the_permalink( ESPACE_CLIENT_PAGE );
    $Notice->guid = $espace_client_url. "?ref=notif";
    $Notice->tpl_msg = 18;
    $Notice->needle = [];

    $Model->added_notice($Author->ID, $Notice);
  }

  public function get_user_administrator()
  {
    $user_query = new \WP_User_Query(array('role__in' => ['Administrator', 'Editor']));
    return $user_query->get_results();
  }

  public function notice_publish_offer($id_offer)
  {
  }
}

new NotificationHelper();