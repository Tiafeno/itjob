<?php

trait OfferHelper {
  /** @var int $id_offer - Contient l'identification de l'offre */
  protected $id_offer;

  /** @var object $company - Contient les informations de l'auteur de l'offre */
  protected $company;

  public $candidat_apply = [];

  public function getPrivateInformations() {
    $itModel   = new \includes\model\itModel();
    $interests = $itModel->get_offer_interests( $this->id_offer );
    foreach ( $interests as $interest ) {
      $this->candidat_apply[] = [
        'status'        => $interest->status,
        'type'          => $interest->type,
        'id_candidate'  => (int) $interest->id_candidate,
        'id_cv_request' => (int) $interest->id_cv_request
      ];
    }
  }

  public function getCompany() {
    return $this->company;
  }

}