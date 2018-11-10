<?php

namespace includes\model;

use includes\post\Company;

final class itModel {
  public function __construct() {
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
  public function added_interest( $id_candidat, $id_offer, $id_company = null ) {
    global $wpdb;
    if ( ! is_user_logged_in() ) {
      return false;
    }
    $table  = $wpdb->prefix . 'cv_request';
    $data   = [ 'id_candidat' => $id_candidat, 'id_offer' => $id_offer ];
    $format = [ '%d', '%d', '%d' ];

    if ( $this->exist_interest( $id_candidat, $id_offer ) ) {
      return false;
    }
    if ( ! is_null( $id_company ) ) {
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
    $prepare = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE id_candidat = %d AND id_offer = %d", (int) $id_candidat, (int) $id_offer );
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
    $prepare = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE id_candidat = %d AND id_company = %d AND status = 1",
      (int) $id_candidat, (int) $id_company );
    $rows    = $wpdb->get_var( $prepare );

    return $rows ? true : false;
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
    $table = $wpdb->prefix . "cv_request";
    if ( is_null( $company_id ) ) {
      if ( ! is_user_logged_in() ) {
        return false;
      }
      $User = wp_get_current_user();
      if ( ! in_array( 'company', $User->roles ) ) {
        return false;
      }
      $Company = Company::get_company_by( $User->ID );
    } else {
      $Company = new Company( (int) $company_id );
    }
    // Ici on verifie seulement les entrer mais si le status est actif ou le contraire
    $prepare = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE id_company = %d", $Company->getId() );
    $rows    = $wpdb->get_var( $prepare );

    return $rows < 5 ? false : ( $Company->isPremium() ? false : true );
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
    if ( is_null( $company_id ) ) {
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
}