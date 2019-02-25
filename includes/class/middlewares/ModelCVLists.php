<?php

if (!defined('ABSPATH')) {
  exit;
}
trait ModelCVLists
{
  private $listTable;

  public function __construct()
  {
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
  public function check_list_limit($company_id = null)
  {
    global $wpdb;
    if (is_null($company_id) || empty($company_id)) {
      if (!is_user_logged_in()) {
        return false;
      }
      $User = wp_get_current_user();
      if (!in_array('company', $User->roles)) {
        return false;
      }
      $Company = \includes\post\Company::get_company_by($User->ID);
    } else {
      $Company = new \includes\post\Company((int)$company_id);
    }
    // Ici on verifie seulement les entrer mais si le status est actif ou le contraire
    $prepare = $wpdb->prepare("SELECT COUNT(*) FROM $this->listTable WHERE id_company = %d", $Company->getId());
    $rows = $wpdb->get_var($prepare);

    // Verifier pour les même CV sur des differents offre
    return $rows <= 5 ? false : true;
  }

  /**
   * Récuperer la liste des candidats
   * @param null|int $company_id
   *
   * @return array|bool|null|object
   */
  public function get_lists($company_id = null)
  {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }

    if (empty($company_id) || is_null($company_id)) {
      $User = wp_get_current_user();
      if (!in_array('company', $User->roles)) {
        return false;
      }
      $Company = \includes\post\Company::get_company_by($User->ID);
    } else {
      $Company = new \includes\post\Company($company_id);
    }
    $prepare = $wpdb->prepare("SELECT * FROM $this->listTable WHERE id_company = %d", $Company->getId());
    $results = $wpdb->get_results($prepare);
    return $results;

  }

  public function list_exist($id_company, $id_candidat)
  {
    global $wpdb;
    if (!is_user_logged_in()) {
      return false;
    }
    if (!is_numeric($id_company) && !is_numeric($id_candidat)) {
      return null;
    }
    $prepare = $wpdb->prepare("SELECT COUNT(*) FROM $this->listTable WHERE id_candidate = %d AND id_company = %d", (int)$id_candidat, (int)$id_company);
    $rows = $wpdb->get_var($prepare);

    return $rows;
  }

  /**
   * Ajouter un CV dans la liste
   * @param int $id_candidat
   * @param null|int $id_company
   *
   * @return bool|false|int|null
   */
  public function add_list($id_candidat, $id_company = null)
  {
    global $wpdb;
    $Company = null;
    if (!is_numeric($id_candidat) || !is_user_logged_in()) return false;
    if (is_null($id_company) || empty($id_company)) {
      $User = wp_get_current_user();
      $userData = get_userdata($User->ID);
      if (in_array('company', $userData->roles)) {
        $Company = \includes\post\Company::get_company_by($User->ID);
      } else return false;
    } else {
      $Company = new \includes\post\Company((int)$id_company);
    }

    if ($this->list_exist($Company->getId(), $id_candidat)) return true;

    $data = ['id_candidate' => $id_candidat, 'id_company' => $Company->getId()];
    $format = ['%d', '%d'];
    $result = $wpdb->insert($this->listTable, $data, $format);
    return $result;
  }


}