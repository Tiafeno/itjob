<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

final class Offers implements iOffer {
  // Added Trait Class
  use Auth;

  /** @var int $ID - Identification de l'offre */
  public $ID;

  /** @var string $title - Titre de l'offre équivalent avec la variable `Poste pourvu` */
  public $title;

  /** @var date $datePublication - Date de la publication de l'offre */
  public $datePublication;

  /** @var date $dateLimit - Date limite de publication */
  public $dateLimit;

  /** @var  int $companyId - ID of company */
  public $companyId;

  /** @var string $positionFilled - Poste pourvu */
  public $positionFilled;

  /** @var string $reference -  Référence de l'offre */
  public $reference;

  /** @var int $proposedSalary - Salaire proposé (facultatif) */
  public $proposedSalary;

  /** @var string $region - Region où se trouve l'offre */
  public $region;

  /** @var string $contractType -  Type de contrat */
  public $contractType;

  /** @var string $profile - Type de profil réchercher pour l'offre */
  public $profile;

  /** @var string $mission - Les mission qui seront effectuer par le ou les candidats */
  public $mission;

  /** @var string $otherInformation - Autres information nécessaire (facultatif) */
  public $otherInformation;

  /** @var bool $featured - L'offre est à la une ou pas */
  public $featured;

  public function __construct( $postId = null ) {
    if ( is_null( $postId ) ) {
      return false;
    }
    /** @var Wp_User|0 authUser */
    $this->authUser = wp_get_current_user();
  }

  /** return all offers */
  public static function getOffers() {
    return;
  }

  public function getOffer() {

  }

  public function updateOffer() {

  }

  public function removeOffer() {

  }

  public function isFeatured() {

  }
}