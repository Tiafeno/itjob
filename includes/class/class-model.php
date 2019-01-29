<?php

namespace includes\model;

if (!defined('ABSPATH')) {
  exit;
}

final class itModel {
  use \ModelInterest {
    \ModelInterest::__construct as private __interestConstruct;
  }

  use \ModelCVLists {
    \ModelCVLists::__construct as private __listConstruct;
  }

  use \ModelNotice {
    \ModelNotice::__construct as private __noticeConstruct;
  }

  use \ModelAds {
    \ModelAds::__construct as private __adsConstruct;
  }

  public function __construct() {
    $this->__listConstruct();
    $this->__interestConstruct();
    $this->__noticeConstruct();
    $this->__adsConstruct();
  }

  public function get_candidate_id_by_email( $email ) {
    global $wpdb;
    $prepare = $this->getPrepareSql('candidate','itjob_cv_email', $email );
    $id    = $wpdb->get_var( $prepare );
    return $id;
  }

  public function get_company_id_by_email( $email ) {
    global $wpdb;
    $prepare = $this->getPrepareSql('company','itjob_company_email', $email );
    $id    = $wpdb->get_var( $prepare );
    return $id;
  }

  public function repair_table() {
    global $wpdb;
    $prepare = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cv_request WHERE type = %s AND status = %s", 'apply', 'validated' );
    $rows    = $wpdb->get_results( $prepare );

    foreach ($rows as $row) {
      $this->add_list((int)$row->id_candidate, (int)$row->id_company);
    }
  }

  private function getPrepareSql($post_type, $meta_key, $meta_value) {
    global $wpdb;
    $sql = "SELECT pst.ID 
              FROM {$wpdb->posts} as pst
              WHERE pst.post_type = '%s'
                AND (pst.ID IN (
                  SELECT {$wpdb->postmeta}.post_id as post_id
                  FROM {$wpdb->postmeta}
                  WHERE {$wpdb->postmeta}.meta_key = '%s' AND {$wpdb->postmeta}.meta_value = '%s'
                ))";
    return $wpdb->prepare($sql, $post_type, $meta_key, $meta_value);
  }

}