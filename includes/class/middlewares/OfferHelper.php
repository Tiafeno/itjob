<?php

trait OfferHelper {
  /** @var int $id_offer - Contient l'identification de l'offre */
  protected $id_offer;

  /** @var WP_User $author - Contient les informations de l'entreprise */
  protected $author;

  /** @var object $company - Contient les informations de l'auteur de l'offre */
  protected $company;

  public $candidat_apply = [];

  public function getPrivateInformations() {
    $itModel   = new \includes\model\itModel();
    $interests = $itModel->get_offer_interests( $this->id_offer );
    foreach ( $interests as $interest ) {
      $this->candidat_apply[] = [
        'status'       => (int) $interest->status,
        'type'         => $interest->type,
        'id_candidate' => (int) $interest->id_candidate,
        'id_request'   => (int) $interest->id_request
      ];
    }
  }

  public function getAuthor() {
    return $this->author;
  }

  public function getCompany() {
    return $this->company;
  }

}