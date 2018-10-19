<?php
namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

trait Types {
  public $lists = [
    ['code' => 0, 'value' => 'confirmation'], // Confirmation de publication de votre articles (offre, travail etc...)
    ['code' => 1, 'value' => 'candidate'], // Notification sur un candidat publier qui correspond à la demande
    ['code' => 2, 'value' => 'account'], // Offre d'abonnement
    ['code' => 3, 'value' => 'update'], // Changement d'adresse etc...
    ['code' => 4, 'value' => 'alert'],
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