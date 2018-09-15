<?php

namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'jobServices' ) ) :
  class jobServices {
    public $args;

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

    public function getRecentlyPost( $class_name, $numberposts = 3, $meta_query = [] ) {
      $recentlyContainer = [];
      $this->args        = [
        'post_type'      => $class_name,
        'post_status'    => [ 'publish', 'pending' ],
        'posts_per_page' => $numberposts,
        'orderby'        => 'DATE'
      ];
      if ( ! empty($meta_query) ) {
        $this->args['meta_query'] = [];
        array_push($this->args['meta_query'], $meta_query);
      }
      $this->getPostContents( $recentlyContainer, $class_name );

      return $recentlyContainer;
    }

    public function getFeaturedPost( $class_name, $meta_query_value, $meta_query = [] ) {
      $featuredContainer = [];
      $this->args        = [
        'post_type'      => $class_name,
        'post_status'    => [ 'publish', 'pending' ],
        'posts_per_page' => 4,
        'orderby'        => 'DATE',
        'meta_query'     => [
          [
            'key'     => $meta_query_value,
            'compare' => '=',
            'value'   => 1,
            'type'    => 'NUMERIC'
          ]
        ]
      ];
      if ( ! empty($meta_query) ) {
        array_push($this->args['meta_query'], $meta_query);
      }
      $this->getPostContents( $featuredContainer, $class_name );

      return $featuredContainer;
    }

    protected function getPostContents( &$container, $class_name ) {
      $posts = get_posts( $this->args );
      foreach ( $posts as $post ) {
        try {
          $class_name = ucfirst( $class_name );
          $cls        = new \ReflectionClass( "includes\\post\\$class_name" );
          $instance   = $cls->newInstanceArgs( [ $post->ID ] );
          array_push( $container, $instance );
        } catch ( \ReflectionException $e ) {
          return [];
        }
      }
    }


  }
endif;

return new jobServices();