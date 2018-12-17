<?php

final class apiModel
{
  public function __construct()
  {

  }

  public function count_post_type($post_type) {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }
    if (!$post_type && !post_type_exists($post_type)) {
      return null;
    }
    $sql = "SELECT COUNT(*) FROM $wpdb->posts pts WHERE pts.post_type = %s";
    $prepare = $wpdb->prepare($sql, $post_type);
    $rows = $wpdb->get_var($prepare);

    return $rows;
  }

  public function count_post_active($post_type) {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }
    if (!$post_type && !post_type_exists($post_type)) {
      return null;
    }
    $sql = "SELECT COUNT(*) FROM $wpdb->posts pts WHERE 
              pts.post_type = %s 
              AND pts.ID IN (
                SELECT {$wpdb->postmeta}.post_id as post_id
                FROM {$wpdb->postmeta}
                WHERE {$wpdb->postmeta}.meta_key = 'activated' AND {$wpdb->postmeta}.meta_value = 1
              ) 
              AND  pts.ID IN (
                SELECT {$wpdb->postmeta}.post_id as post_id
                FROM {$wpdb->postmeta}
                WHERE {$wpdb->postmeta}.meta_key = 'itjob_cv_hasCV' AND {$wpdb->postmeta}.meta_value = 1
              )";
    $prepare = $wpdb->prepare($sql, $post_type);
    $rows = $wpdb->get_var($prepare);

    return $rows;
  }

  public function count_post_status($post_type, $post_status = 'publish') {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }
    if (!$post_type && !post_type_exists($post_type)) {
      return 0;
    }
    $sql = "SELECT COUNT(*) FROM $wpdb->posts pts WHERE pts.post_type = %s AND pts.post_status = %s";
    $prepare = $wpdb->prepare($sql, $post_type, $post_status);
    $rows = $wpdb->get_var($prepare);

    return $rows;
  }

  public function count_featured_candidates() {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }
    $sql = "SELECT COUNT(*) FROM $wpdb->posts pts WHERE pts.post_type = %s AND pts.ID IN (
      SELECT {$wpdb->postmeta}.post_id as post_id
      FROM {$wpdb->postmeta}
      WHERE {$wpdb->postmeta}.meta_key = 'itjob_cv_featured' AND {$wpdb->postmeta}.meta_value = %d
    )";
    $prepare = $wpdb->prepare($sql, 'candidate', 1);
    $rows = $wpdb->get_var($prepare);
    
    return $rows;
  }

  public function count_featured_company() {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }
    $sql = "SELECT COUNT(*) FROM $wpdb->posts pts WHERE pts.post_type = %s AND pts.ID IN (
      SELECT {$wpdb->postmeta}.post_id as post_id
      FROM {$wpdb->postmeta}
      WHERE {$wpdb->postmeta}.meta_key = 'itjob_meta_account' AND {$wpdb->postmeta}.meta_value = %d
    )";
    $prepare = $wpdb->prepare($sql, 'company', 1);
    $rows = $wpdb->get_var($prepare);
    
    return $rows;
  }

  public function count_featured_offers() {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }
    $sql = "SELECT COUNT(*) FROM $wpdb->posts pts WHERE pts.post_type = %s AND pts.ID IN (
      SELECT {$wpdb->postmeta}.post_id as post_id
      FROM {$wpdb->postmeta}
      WHERE {$wpdb->postmeta}.meta_key = 'itjob_offer_rateplan' AND {$wpdb->postmeta}.meta_value LIKE '%s'
    )";
    $prepare = $wpdb->prepare($sql, 'offers', 'sereine');
    $rows = $wpdb->get_var($prepare);
    
    return $rows;
  }
}