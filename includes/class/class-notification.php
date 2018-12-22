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


final class Notification
{
  public $ID = 0;
  public $title;
  public $date_create;
  public $url;

  public function __construct()
  {
  }

  public static function getInstance($id_notice)
  {
    $Model = new itModel();
    return $Model->collect_notice($id_notice);
  }


}

final class NotificationHelper
{
  const BO_URL = "";
  public function __construct()
  {
    add_action('init', function () {
      add_action('notice-candidate-postuled', [&$this, 'notice_candidate_postuled'], 10, 2);
      add_action('notice-interest', [&$this, 'notice_interest'], 10, 1);
      add_action('notice-publish-cv', [&$this, 'notice_publish_cv'], 10, 1);
      add_action('notice-publish-offer', [&$this, 'notice_publish_offer'], 10, 1);
      add_action('notice-change-request-status', [&$this, 'notice_change_request_status'], 10, 2);

      add_action('notice-admin-create-cv', [&$this, 'notice_admin_create_cv'], 10, 1);
      add_action('notice-admin-new-offer', [&$this, 'notice_admin_new_offer'], 10, 1);
      add_action('notice-admin-update-cv', [&$this, 'notice_admin_update_cv'], 10, 1);

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

  public function notice_admin_create_cv($id_cv) {
    $Model = new itModel();
    // Company
    $Candidate = new Candidate((int)$id_cv);
    $Notice = new Notification();
    $Author = $Candidate->getAuthor();
    $Firstname = $Candidate->getFirstName();

    $Notice->title = "<b>$Firstname</b> viens d'ajouter un CV pour reference <b>{$Candidate->title}</b>";
    $Notice->url = "/candidate/{$id_cv}/edit";
    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }
  
  public function notice_admin_new_offer($id_offer) {
    $Model = new itModel();
    $Offer = new Offers((int)$id_offer);
    $Notice = new Notification();
    $Notice->title = "Une offre viens d'être ajouter sur le site: <b>{$Offer->title}</b>";
    $Notice->url = "/offer/{$id_offer}/edit";
    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }
  public function notice_admin_update_cv($id_cv) {
    $Model = new itModel();
    // Company
    $Candidate = new Candidate((int)$id_cv);
    $Notice = new Notification();
    $Author = $Candidate->getAuthor();
    $Firstname = $Candidate->getFirstName();

    $Notice->title = "<b>$Firstname</b> viens de modifier son CV pour reference <b>{$Candidate->title}</b>";
    $Notice->url = "/candidate/{$id_cv}/edit";
    $Administrators = $this->get_user_administrator();
    foreach ($Administrators as $admin) {
      $Model->added_notice($admin->ID, $Notice);
    }
  }


  public function notice_publish_cv($id_cv) {
    $id_cv = (int)$id_cv;
    if (!$id_cv) return false;
    $Model = new itModel();
    $Candidate = new Candidate($id_cv);
    $Notice = new Notification();
    $Notice->title = "Votre CV viens d'être validé";
    $Notice->url = $Candidate->candidate_url . '?ref=notif';
    $Author = $Candidate->getAuthor();
    $Model->added_notice($Author->ID, $Notice);

    return true;
  }
  public function notice_publish_offer($id_offer) {}
  public function notice_candidate_postuled($id_cv, $id_offer) {
    $Model = new itModel();
    // Company
    $Candidate = new Candidate($id_cv);
    $Author = $Candidate->getAuthor();
    $Offer = new Offers($id_offer);
    $companyNotice = new Notification();
    $companyNotice->title = "$Candidate->title a postulé pour l'offre <b>{$Offer->title}</b>";
    $companyNotice->url = $Candidate->candidate_url . "?ref=notif";
    $Model->added_notice($Author->ID, $companyNotice);

    return true;
  }
  public function notice_interest($id_cv_request) {
    if (!is_numeric($id_cv_request)) return false;
    $Interest = itModel::get_request($id_cv_request);
    if (is_null($Interest)) return null;
    $Model = new itModel();
    $Offer = new Offers((int)$Interest->id_offer);

    // Candidate
    $Candidate = new Candidate((int)$Interest->id_candidate);
    $Author = $Candidate->getAuthor();
    $candidateNotice = new Notification();
    $candidateNotice->title = "Une entreprise s'interesser à votre CV sur l'offre: <b>{$Offer->title}</b>";
    $candidateNotice->url = $Offer->offer_url . "?ref=notif";
    $Model->added_notice($Author->ID, $candidateNotice);

    return true;
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

    $companyNotice = new Notification();
    $candidateNotice = new Notification();
    $companyNotice->url = $Candidate->candidate_url . "?ref=notif";
    $candidateNotice->url = $Offer->offer_url . "?ref=notif";
    switch ($Interest->status) {
      case 'validated':
        $companyNotice->title = "Le CV portant la référence « {$Candidate->reference} » est maintenant disponible, sur l'offre <b>{$Offer->title}</b>";
        $candidateNotice->title = "Votre CV a été selectionné sur l'offre « {$Offer->title} »";
        break;
      case 'reject':
        $companyNotice->title = "CV portant la référence « {$Candidate->reference} » a été réjeté sur l'offre <b>{$Offer->title}</b>";
        if ($Interest->type === 'apply') {
          $candidateNotice->title = "Votre candidature a été rejeté sur l'offre « {$Offer->title} »";
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

  public function get_user_administrator() {
    $user_query = new \WP_User_Query( array( 'role__in' => ['Administrator', 'Editor']) );
    return $user_query->get_results();
  }
}

new NotificationHelper();