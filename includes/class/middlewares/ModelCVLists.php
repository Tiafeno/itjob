<?php
trait ModelCVLists {
  private $listTable;

  public function __construct() {
    global $wpdb;
    $this->listTable = $wpdb->prefix . 'cv_lists';
  }

  /**
   * Cette fonction permet de verifier si le candidat a atteint la limite ou pas.
   * L'entreprise de membre premium a tous les acces.
   *
   * @param int|null $company_id
   *
   * @return bool
   */
  public function limit_interest_access( $company_id = null ) {
    global $wpdb;
    if ( is_null( $company_id ) || empty( $company_id ) ) {
      if ( ! is_user_logged_in() ) {
        return false;
      }
      $User = wp_get_current_user();
      if ( ! in_array( 'company', $User->roles ) ) {
        return false;
      }
      $Company = \includes\post\Company::get_company_by( $User->ID );
    } else {
      $Company = new \includes\post\Company( (int) $company_id );
    }
    // Ici on verifie seulement les entrer mais si le status est actif ou le contraire
    $prepare = $wpdb->prepare( "SELECT COUNT(*) FROM $this->listTable WHERE id_company = %d", $Company->getId() );
    $rows    = $wpdb->get_var( $prepare );

    // Verifier pour les même CV sur des differents offre
    return $rows <= 5 ? false : ( $Company->isPremium() ? false : true );
  }

}