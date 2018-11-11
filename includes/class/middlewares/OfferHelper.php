<?php

trait OfferHelper {
  /** @var int $id_offer - Contient l'identification de l'offre */
  protected $id_offer;

  /** @var WP_User $author - Contient les informations de l'entreprise */
  protected $author;

  /** @var object $company - Contient les informations de l'auteur de l'offre  */
  protected $company;

  public $candidat_apply = [];
  public $interests = [];

  public function getPrivateInformations() {
    // Array of user id
    $applyField = get_field('itjob_users_apply', $this->id_offer);
    if ($applyField && is_array($applyField)):
      $contents = array_map(function ($user_id) {
        $Candidate = \includes\post\Candidate::get_candidate_by($user_id);
        $content = ['status' => 1, 'id_candidate' => $Candidate->getId()];
        return $content;
      }, $applyField);
      $this->candidat_apply = $contents;
    endif;
    $this->getOfferInterests();
  }

  private function getOfferInterests() {
    $itModel = new \includes\model\itModel();
    $interests = $itModel->get_offer_interests($this->id_offer);
    foreach ($interests as $interest) {
      $this->candidat_apply[] = ['status' => (int)$interest->status, 'id_candidate' => (int)$interest->id_candidat];
    }
  }

  public function getAuthor() {
    return $this->author;
  }

  public function getCompany() {
    return $this->company;
  }

}