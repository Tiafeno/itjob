<?php
namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Token {
  protected $token;
  public function __construct($accessToken) {
    $this->token = $accessToken;
  }

  public function getClient() {
    $users = get_users(['role' => 'company', 'number' => -1]);
    foreach ($users as $user) {
      if ($user->data->user_pass === $this->token) {
        return $user;
      }
    }
    return false;
  }

  public function getToken() {
    return $this->token;
  }

}