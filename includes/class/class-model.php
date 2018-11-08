<?php

namespace includes\model;


use includes\post\Company;

final class itModel {
  public function __construct() {
  }

  /**
   * @param int $id_candidat
   * @param int $id_offer
   * @param null $id_company
   *
   * @return bool|false|int
   */
  public function added_interest($id_candidat, $id_offer, $id_company = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'cv_request';
    $data = ['id_candidat' => $id_candidat, 'id_offer' => $id_offer];
    $format = ['%d', '%d', '%d'];

    if ($this->exist_interest($id_candidat, $id_offer)) {
      return false;
    }
    if (!is_null($id_company)) {
      $data = array_merge($data, ['id_company' => $id_company]);
    } else {
      $User = wp_get_current_user();
      if (!in_array('company', $User->roles)) return false;
      $Company = Company::get_company_by($User->ID);
      $data = array_merge($data, ['id_company' => $Company->getId()]);
    }
    $results = $wpdb->insert($table, $data, $format);
    return $results;
  }

  /**
   * @param int $id_request
   * @param int $status
   *
   * @return bool|false|int
   */
  public function update_interest_status($id_request, $status = 0) {
    global $wpdb;
    if (!is_int($id_request)) return false;
    $table = $wpdb->prefix . 'cv_request';
    $results = $wpdb->update($table, ['status' => $status], ['id_request' => $id_request], ['%d'], ['%d']);
    return $results;
  }

  /**
   * @param int $id_candidat
   * @param int $id_offer
   *
   * @return null|string
   */
  public function exist_interest($id_candidat = 0, $id_offer = 0) {
    global $wpdb;
    if (!$id_offer || !$id_candidat) return null;
    $table = $wpdb->prefix . 'cv_request';
    $prepare = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id_candidat = %d AND id_offer = %d", (int)$id_candidat, (int)$id_offer);
    $rows = $wpdb->get_var($prepare);
    return $rows;
  }

  /**
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