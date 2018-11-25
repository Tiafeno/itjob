<?php

namespace includes\object;
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'jobServices' ) ) :
  class jobServices {
    public $args;
    private $User = null;
    private $Client = false;
    public function __construct() {

    }

    public function isClient() {
      if ( ! $this->User instanceof \WP_User)
        $this->User = wp_get_current_user();
      if ($this->User->ID !== 0) {
        $this->Client = isset($this->User->roles[0]) ? $this->User->roles[0] : false;
      }
      return $this->Client;
    }


    /**
     * Récuperer les informations nécessaire d'un utilisateur
     *
     * @param int $userId - ID d'un utilisateur
     *
     * @return \WP_User
     */
    public static function getUserData( $userId ) {
      return \get_userdata( $userId );
    }

    /**
     * Cette fonction permet d'afficher les post recement ajouter dans le site.
     * Le nombre de retour par default se limite à 3 posts
     *
     * @param string $class_name
     * @param int $numberposts - Definir le nombre de post à retourner
     * @param array $meta_query
     *
     * @return array
     */
    public function getRecentlyPost( $class_name, $numberposts = 3, $meta_query = [] ) {
      $recentlyContainer = [];
      $this->args        = [
        'post_type'      => $class_name,
        'post_status'    => [ 'publish' ],
        'posts_per_page' => $numberposts,
        'orderby'        => 'DATE'
      ];
      if ( ! empty( $meta_query ) ) {
        $this->args['meta_query'][] = $meta_query;
      }
      if ($class_name === 'candidate') {
        $this->args = array_merge($this->args,
          ['meta_query' => [
            'relation' => 'AND',
            [
              'key' => 'activated',
              'value' => 1
            ],
            [
              'key' => 'itjob_cv_hasCV',
              'value' => 1
            ]
        ]]);
      }
      $this->getPostContents( $recentlyContainer, $class_name );

      return $recentlyContainer;
    }

    /**
     * Cette function renvoie les post à la une (post pour status publier dans le site)
     * Les posts désactiver qui ne sont pas publier ne serons pas afficher,
     * seul les post publier et activer seront retourner par cette fonction
     * Voir le fichier class-itjob.php, action: pre_get_posts
     *
     * @param string $class_name
     * @param array $meta_query
     * @param int $numberposts - La valeur par default est 4
     *
     * @return array
     */
    public function getFeaturedPost( $class_name, $meta_query = [], $numberposts = 4 ) {
      $featuredContainer = [];
      $this->args        = [
        'post_type'      => $class_name,
        'post_status'    => [ 'publish' ],
        'posts_per_page' => $numberposts,
        'orderby'        => 'DATE'
      ];
      if ( ! empty( $meta_query ) ) {
        $this->args['meta_query'][] =  $meta_query;
      }
      $this->getPostContents( $featuredContainer, $class_name );

      return $featuredContainer;
    }

    /**
     * Vérifier dans la base de donnée si la page existe
     *
     * @param $title
     *
     * @return int
     */
    public static function page_exists( $title ) {
      global $wpdb;

      $post_title = wp_unslash( sanitize_post_field( 'post_title', $title, 0, 'db' ) );
      $query      = "SELECT ID FROM $wpdb->posts WHERE post_type='%s'";
      $args       = [];
      $args[]     = 'page';

      if ( ! empty ( $title ) ) {
        $query  .= ' AND post_title = %s';
        $args[] = $post_title;
      }
      if ( ! empty ( $args ) ) {
        return (int) $wpdb->get_var( $wpdb->prepare( $query, $args ) );
      }

      return 0;
    }

    protected function getPostContents( &$container, $class_name ) {
      $Types = ['offers', 'candidate', 'company'];
      $posts = get_posts( $this->args );
      foreach ( $posts as $post ) {
        try {
          // Ne pas afficher les posts qui ne sont pas activé
          if (in_array($class_name, $Types)) {
            $activated = get_field( 'activated', $post->ID );
            if ( ! $activated ) {
              continue;
            }
          }
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