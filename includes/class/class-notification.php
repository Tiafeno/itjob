<?php
namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

trait Types {
  public $lists = [
    "confirmation_publish_offer", // Confirmation de publication d'offre
    "upgrade_account", // Notification pour la nature du compte
    "alert_new_user", // Alert si un utlisateur vient d'etre publier sur la même secteur d'activité que cette entreprise
    "alert_publication", // Alerte en générale (offre, candidat, etc..)
    "candidate_postuled" // Si un candiat à postuler
  ];
}

final class Notification {
  use Types;
  /** @var int $code - Contient le type du notification */
  public $code;
  public $title;

  public function __construct() {
  }

}