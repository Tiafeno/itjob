<?php
/**
 * Created by IntelliJ IDEA.
 * User: Tiafeno
 * Date: 16/08/2018
 * Time: 11:37
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( ! class_exists( 'jobServices' ) ) :
  class jobServices {
    public function __construct() {
    }


    /**
     * Récuperer les informations nécessaire d'un utilisateur
     * @param int $userId - ID d'un utilisateur
     * @return stdClass
     */
    public static function getUserData( $userId ) {
      $user                    = new WP_User( $userId );
      $userClass               = new stdClass();
      $userClass->user_login   = $user->user_login;
      $userClass->token        = $user->user_pass;
      $userClass->user_email   = $user->user_email;
      $userClass->display_name = $user->display_name;

      return $userClass;
    }

  }
endif;

return new jobServices();