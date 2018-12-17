<?php

namespace includes\object;

class JHelper {
  public function __construct() {
  }

  public function get_candidate_by_email( $email ) {
    if ( empty( $email ) || is_null( $email ) || ! email_exists( $email ) ) {
      return false;
    }
    $candidate_query = get_posts( [
      'post_type'    => 'candidate',
      'post_status'  => 'any',
      'meta_key'     => 'itjob_cv_email',
      'meta_value'   => $email,
      'meta_compare' => '='
    ] );
    if ( is_array( $candidate_query ) && ! empty( $candidate_query ) ) {
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

  public function is_cv( $cv_id ) {
    if ( empty( $cv_id ) || ! isset( $cv_id ) || ! is_numeric( $cv_id ) ) {
      return false;
    }
    $candidat_post = get_posts( [
        'post_type'   => 'candidate',
        'post_status' => 'any',
        'fields'      => 'ids',
        'meta_key'    => '__id_cv',
        'meta_value'  => $cv_id
      ]
    );
    if ( is_array( $candidat_post ) && ! empty( $candidat_post ) ) {
      return $candidat_post[0];
    }
    return false;
  }

  public function is_applicant( $id_demandeur ) {
    if ( empty( $id_demandeur ) || ! isset( $id_demandeur ) || ! is_numeric( $id_demandeur ) ) {
      return false;
    }
    $candidat_post = get_posts( [
        'post_type'   => 'candidate',
        'post_status' => 'any',
        'fields'      => 'ids',
        'meta_key'    => '__cv_id_demandeur',
        'meta_value'  => $id_demandeur
      ]
    );
    if ( is_array( $candidat_post ) && ! empty( $candidat_post ) ) {
      return $candidat_post[0];
    }

    return false;
  }

  public function has_old_offer_exist( $old_id_offer = 0 ) {
    global $wpdb;
    $sql = "SELECT COUNT(*)
            FROM {$wpdb->posts} pst
            WHERE
              pst.post_type = 'offers'
              AND pst.ID IN (
                SELECT pmt.post_id
                FROM {$wpdb->postmeta} pmt
                WHERE pmt.meta_key = '__id_offer' AND pmt.meta_value = %d)";
    $prepare = $wpdb->prepare($sql, (int)$old_id_offer);
    $rows = $wpdb->get_var($prepare);
    return $rows ? true : false;
  }


  public function parse_csv( $file ) {
    $csv    = array_map( function ( $f ) {
      return str_getcsv( mb_convert_encoding( $f, "UTF-8", "auto" ), ';' );
    }, file( $file ) );
    $header = &$csv[0];
    array_walk( $csv, function ( &$el ) use ( $header ) {
      $el = array_combine( $header, $el );
    } );
    array_shift( $csv ); # remove column header

    return $csv;
  }
}


//$Helper = new JHelper();
//$Helper->get_candidate_by_email( 'fanevaparticu@shasama.com' );