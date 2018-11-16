<?php

namespace includes\object;

class JHelper {
  public function __construct() {
  }

  public function get_candidate_by_email( $email ) {
    if ( empty( $email ) || is_null( $email ) || !email_exists($email)) {
      return false;
    }
    $candidate_query = get_posts( [
      'post_type'    => 'candidate',
      'meta_key'     => 'itjob_cv_email',
      'meta_value'   => $email,
      'meta_compare' => '='
    ] );
    if ( is_array($candidate_query) && ! empty( $candidate_query ) ) {
      return $candidate_query[0];
    }

    return false;
  }

  public function has_old_user( $user_id ) {
    if ( empty( $user_id ) || ! isset( $user_id ) || ! is_numeric( $user_id ) ) {
      return false;
    }
    $old_user_id = get_user_meta( $user_id, '__id_user', true );
    if ( empty( $old_user_id ) ) {
      return false;
    } else {
      return (int) $old_user_id;
    }
  }

  public function has_user( $old_user_id ) {
    if ( empty( $old_user_id ) || ! isset( $old_user_id ) || ! is_numeric( $old_user_id ) ) {
      return false;
    }
    $user_query = new \WP_User_Query( array( 'meta_key' => '__id_user', 'meta_value' => $old_user_id ) );
    // Get the results
    $authors = $user_query->get_results();
    if ( ! empty( $authors ) ) {
      $User = $authors[0];

      return $User;
    } else {
      return false;
    }
  }
}


//$Helper = new JHelper();
//$Helper->get_candidate_by_email( 'fanevaparticu@shasama.com' );