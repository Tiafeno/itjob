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

    /**
     * Vérifier dans la base de donnée si la page existe
     * @param $title
     * @return int
     */
    public static function page_exists( $title ) {
      global $wpdb;

      $post_title = wp_unslash( sanitize_post_field( 'post_title', $title, 0, 'db' ) );
      $query = "SELECT ID FROM $wpdb->posts WHERE post_type='%s'";
      $args = [];
      $args[] = 'page';

      if ( !empty ( $title ) ) {
        $query .= ' AND post_title = %s';
        $args[] = $post_title;
      }
      if ( !empty ( $args ) )
        return (int) $wpdb->get_var( $wpdb->prepare($query, $args) );

      return 0;
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