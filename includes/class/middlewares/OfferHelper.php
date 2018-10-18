<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 26/09/2018
 * Time: 14:10
 */

trait OfferHelper {

  /** @var WP_User $author - Contient les informations de l'utilisateur */
  public $author;

  /** @var object $company - Contient les informations de l'entreprise  */
  public $company;

  public $my_offer = false;
  public $count_candidat_apply = 0;
  public $candidat_apply = [];

  public function isMyOffer($offer_id) {
    if ( ! is_user_logged_in() || ! is_int($offer_id)) return false;
    $User = wp_get_current_user();
    if ($this->company instanceof WP_Post) {
      $Company = new \includes\post\Company($this->company->ID);
      if ($User->ID === $Company->author->ID) {
        $this->my_offer = true;
        // Array of user id
        $applyField = get_field('itjob_users_apply', $offer_id);
        if ($applyField) {
          $this->count_candidat_apply = count($applyField);
          $this->candidat_apply = $applyField;
        }
      }
    } else return false;
  }

  public function __admin_access(){

  }

}