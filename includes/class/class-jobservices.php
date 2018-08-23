<?php

namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
if ( ! class_exists( 'jobServices' ) ) :
  class jobServices {
    public function __construct() {
    }


    /**
     * Récuperer les informations nécessaire d'un utilisateur
     *
     * @param int $userId - ID d'un utilisateur
     *
     * @return stdClass
     */
    public static function getUserData( $userId ) {
      $user             = new \WP_User( $userId );
      $userClass        = new \stdClass();
      $userClass->roles = $user->roles;
      unset( $user->data->user_login, $user->data->user_nicename );
      $userClass->data = $user->data;

      return $userClass;
    }

  }
endif;

return new jobServices();