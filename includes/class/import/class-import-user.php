<?php
namespace includes\classes\import;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class importUser {
  private $user_id = 0;
  public function __construct($user_id) {
    $this->user_id = (int)$user_id;
  }

  /**
   * @param object $rows
   *
   * @return bool
   */
  public function update_user_meta($rows) {
    if ($this->user_id === 0) return false;
    update_user_meta($this->user_id, '__id_user', $rows->id_user);
    update_user_meta($this->user_id, '__pwd_change', $rows->change_pwd);
    update_user_meta($this->user_id, '__created', $rows->created);
    update_user_meta($this->user_id, '__password', $rows->password);
    update_user_meta($this->user_id, '__last_login', $rows->last_login);
    update_user_meta($this->user_id, '__description', $rows->description === 'NULL' ? '' : $rows->description);
    // Ce champ definie si l'utilisateur doit changer son mot de passe
    update_user_meta($this->user_id, '__recovery_password', 1);

    return true;
  }

}