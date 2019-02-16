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
   * @param string $status  pending|validated|reject
   * @param string $type - interested|apply (apply - Signifie qu'un candidat à postuler pour une offre)
   *
   * @return bool|int
   */
  public function added_interest( $id_candidat, $id_offer, $id_company = null, $status = 'pending', $type = 'interested', $id_attachment = 0 ) {
    global $wpdb;
    if ( ! is_user_logged_in() ) {
      return false;
    }
    $table  = $wpdb->prefix . 'cv_request';

    /**
     * pending, validated and reject
     */
    $status = $status === 'pending' ? 'pending' : 'validated';
    $format = [ '%d', '%d', '%s', '%s', '%d', '%d' ];
    // Annuler si l'entreprise à déja ajouter le candidat à cette offre
    if ( $this->exist_interest( $id_candidat, $id_offer ) ) {
      return false;
    }

    if ( is_null( $id_company ) || empty( $id_company ) ) {
      $User = wp_get_current_user();
      if ( ! in_array( 'company', $User->roles ) ) {
        return false;
      }
      $Company    = Company::get_company_by( $User->ID );
      $id_company = $Company->getId();
    }
    $data    = [
      'id_candidate'  => $id_candidat,
      'id_offer'      => $id_offer,
      'status'        => $status,
      'type'          => $type,
      'id_company'    => $id_company,
      'id_attachment' => $id_attachment
    ];
    $results = $wpdb->insert( $table, $data, $format );

    return $results;
  }

  /**
   * Mettre a jour le status de la requete
   *
   * @param int $id_request
   * @param int $status - [pending, validated, reject]
   *
   * @return bool|int
   */
  public function update_interest_status( $id_request, $status = 'pending' ) {
    global $wpdb;
    if ( ! is_user_logged_in() || ! is_numeric( $id_request ) ) {
      return false;
    }
    $results = $wpdb->update( $this->requestTable, [ 'status' => $status ], [ 'id_cv_request' => $id_request ], [ '%s' ], [ '%d' ] );
    if ($results && $status === 'validated') {
      // TODO: Envoyer un mail de confirmation que le demande est validé
      $request = self::get_request($id_request);
      if (null === $request) return false;
      // Envoyer un mail pour informer la validation de cette offre
      do_action("email_application_validation", $request);
    }
    // Crée une notification
    do_action("notice-change-request-status", (int)$id_request, $status);
    return $results;
  }

  /**
   * Cette fonction verifie si le candidat a déja postuler ou selectionner sur l'offre
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
    $prepare = $wpdb->prepare( "SELECT * FROM $table WHERE id_candidate = %d AND id_offer = %d", (int) $id_candidat, (int) $id_offer );
    $rows    = $wpdb->get_row( $prepare );

    return $rows;
  }

  /**
   * Cette fonction permet d'effacer les requetes sur une offre
   * 
   * @param $id_offer - ID du post offer
   * @return array
   */
  public function remove_interest( $id_offer ) {
    global $wpdb;
    if (!is_user_logged_in() || !$id_offer) {
      return false;
    }
    $prepare = $wpdb->prepare("DELETE FROM {$this->requestTable} WHERE id_offer = %d", $id_offer);
    $rows = $wpdb->get_results($prepare);
    return $rows;
  }

  public function remove_candidate_request( $id_candidate ) {
    global $wpdb;
    if (!is_user_logged_in() || !$id_candidate) {
      return false;
    }
    $id_candidate = (int) $id_candidate;
    $prepare = $wpdb->prepare("DELETE FROM {$this->requestTable} WHERE id_candidate = %d", $id_candidate);
    $rows = $wpdb->get_results($prepare);
    return $rows;
  }

  /**
   * Effacer le piéce joint d'un candidat
   * 
   * @param int $id_attachment - ID de l'objet d'attachment
   * @return $result - false|int
   */
  public function remove_attachment( $id_attachment ) {
    global $wpdb;
    $result = $wpdb->update($this->requestTable, ['id_attachment' => 0], ['id_attachment' => (int)$id_attachment], ['%d'], ['%d']);
    return $result;
  }

  /**
   * Cette fonction permet de retourner une requete
   *
   * @param int $id_candidat
   * @param int $id_offer
   *
   * @return array|bool|null|object
   */
  public function collect_interest_candidate( $id_candidat = 0, $id_offer = 0 ) {
    global $wpdb;
    if ( ! is_user_logged_in() ) {
      return false;
    }
    if ( ! $id_offer || ! $id_candidat ) {
      return null;
    }
    $prepare = $wpdb->prepare( "SELECT * FROM $this->requestTable WHERE id_candidate = %d AND id_offer = %d", (int) $id_candidat, (int) $id_offer );
    $rows    = $wpdb->get_row( $prepare );

    return $rows;
  }

  /**
   * Récuperer les offres qu'un candidat à envoyer ces candidatures
   * @param int $id_candidate
   * @param string $status
   *
   * @return array|bool|null|object
   */
  public function collect_candidate_request($id_candidate = 0, $status = 'apply') {
    global $wpdb;
    if (!is_user_logged_in() || !$id_candidate) {
      return false;
    }
    $prepare = $wpdb->prepare( "SELECT * FROM $this->requestTable WHERE id_candidate = %d AND type = %s", (int) $id_candidate, $status );
    $rows    = $wpdb->get_results( $prepare );

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
    $prepare = $wpdb->prepare( "SELECT COUNT(*) FROM $this->requestTable WHERE id_candidate = %d AND id_company = %d AND status = %s",
      (int) $id_candidat, (int) $id_company, 'validated' );
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
   * @return array|bool|null
   */
  public function get_offer_interests( $id_offer = null ) {
    global $wpdb;
    if ( is_null( $id_offer ) || empty( $id_offer ) || ! is_numeric( $id_offer ) || ! is_user_logged_in() ) {
      return false;
    }
    $interests = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cv_request WHERE id_offer = {$id_offer}" );

    return $interests;
  }

  // Retourne tous les requetes dans la base de donnée
  public static function get_all() {
    global $wpdb;
    $interests = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cv_request" );
    // TODO: Une erreur peut être provoquer s'il a des milliers de requete dans la base de donnée
    return $interests;
  }

  // Retourne une requete
  public static function get_request($id_request) {
    global $wpdb;
    if (!is_numeric($id_request)) return false;
    $interests = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}cv_request WHERE {$wpdb->prefix}cv_request.id_cv_request = {$id_request}" );

    return $interests;
  }
}