<?php

use includes\post\Company;

trait ModelInterest {

  private $requestTable;

  public function __construct() {
    global $wpdb;
    $this->requestTable = $wpdb->prefix . 'cv_request';
  }

  /**
   * Ajouter un candidat dans une offre
   *
   * @param int $id_candidat
   * @param int $id_offer
   * @param null $id_company
   *
   * @return bool|int
   */
  public function added_interest( $id_candidat, $id_offer, $id_company = null, $status = 0 ) {
    global $wpdb;
    if ( ! is_user_logged_in() ) {
      return false;
    }
    $table  = $wpdb->prefix . 'cv_request';
    $data   = [ 'id_candidate' => $id_candidat, 'id_offer' => $id_offer ];
    $format = [ '%d', '%d', '%d' ];
    // Annuler si l'entreprise à déja ajouter le candidat à cette offre
    if ( $this->exist_interest( $id_candidat, $id_offer ) ) {
      return false;
    }

    // Ajouter une requete pré-activé
    if ( $status ) {
      $format = array_merge( $format, [ '%d' ] );
      $data   = array_merge( $data, [ 'status' => 1 ] );
    }

    if ( ! is_null( $id_company ) || ! empty( $id_company ) ) {
      $data = array_merge( $data, [ 'id_company' => $id_company ] );
    } else {
      $User = wp_get_current_user();
      if ( ! in_array( 'company', $User->roles ) ) {
        return false;
      }
      $Company = Company::get_company_by( $User->ID );
      $data    = array_merge( $data, [ 'id_company' => $Company->getId() ] );
    }
    $results = $wpdb->insert( $table, $data, $format );

    return $results;
  }

  /**
   * Mettre a jour le status de la requete
   *
   * @param int $id_request
   * @param int $status
   *
   * @return bool|int
   */
  public function update_interest_status( $id_request, $status = 0 ) {
    global $wpdb;
    if ( ! is_user_logged_in() || ! is_int( $id_request ) ) {
      return false;
    }
    $table   = $wpdb->prefix . 'cv_request';
    $results = $wpdb->update( $table, [ 'status' => $status ], [ 'id_request' => $id_request ], [ '%d' ], [ '%d' ] );

    return $results;
  }

  /**
   * Cette fonction verifie si est deja ajouter dans l'offre
   *
   * @param int $id_candidat
   * @param int $id_offer
   *
   * @return null|string
   */
  public function exist_interest( $id_candidat = 0, $id_offer = 0 ) {
    global $wpdb;
    if ( ! is_user_logged_in() ) {
      return false;
    }
    if ( ! $id_offer || ! $id_candidat ) {
      return null;
    }
    $table   = $wpdb->prefix . 'cv_request';
    $prepare = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE id_candidate = %d AND id_offer = %d", (int) $id_candidat, (int) $id_offer );
    $rows    = $wpdb->get_var( $prepare );

    return $rows;
  }

  /**
   * Cette fonction verifie si l'entreprise a l'acces a cette candidat
   *
   * @param int $id_candidat
   * @param int $id_offer
   *
   * @return bool|null
   */
  public function interest_access( $id_candidat, $id_company ) {
    global $wpdb;
    if ( ! is_user_logged_in() ) {
      return false;
    }
    if ( ! $id_company || ! $id_candidat ) {
      return null;
    }
    $table   = $wpdb->prefix . 'cv_request';
    $prepare = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE id_candidate = %d AND id_company = %d AND status = 1",
      (int) $id_candidat, (int) $id_company );
    $rows    = $wpdb->get_var( $prepare );

    return $rows ? true : false;
  }

  /**
   * Recuperer les candidats qui interese l'entreprise.
   * Soit on le recuper par le parametre $company_id ou l'entreprise en ligne sur le site
   *
   * @param null $company_id
   *
   * @return array|null|object
   */
  public function get_interests( $company_id = null ) {
    global $wpdb;
    if ( is_null( $company_id ) || empty( $company_id ) ) {
      if ( ! is_user_logged_in() ) {
        return [];
      }
      $User    = wp_get_current_user();
      $Company = Company::get_company_by( $User->ID );
    } else {
      if ( ! is_int( $company_id ) ) {
        return [];
      }
      $Company = new Company( $company_id );
    }
    $company_id = $Company->getId();
    $interests  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cv_request WHERE id_company = {$company_id}" );

    return $interests;
  }

  /**
   * Récuperer les requetes via un offre
   *
   * @param int|null $id_offer
   *
   * @return array|bool|null|object
   */
  public function get_offer_interests( $id_offer = null ) {
    global $wpdb;
    if ( is_null( $id_offer ) || empty( $id_offer ) || ! is_numeric( $id_offer ) || ! is_user_logged_in() ) {
      return false;
    }
    $interests = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cv_request WHERE id_offer = {$id_offer}" );

    return $interests;
  }
}