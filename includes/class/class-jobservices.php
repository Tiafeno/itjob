<?php

namespace includes\object;
use includes\post\Offers;
use Underscore\Types\Arrays;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'jobServices' ) ) :
  class jobServices {
    public $args;
    private $User = null;
    private $Client = false;

    public function __construct() {
      if (is_user_logged_in()) {
        $this->User = wp_get_current_user();
      }
    }

    public function isClient() {
      if ( ! $this->User instanceof \WP_User)
        $this->User = wp_get_current_user();
      if ($this->User->ID !== 0) {
        $this->Client = isset($this->User->roles[0]) ? $this->User->roles[0] : false;
      }
      return $this->Client;
    }

    public function getUser() {
      if (is_null($this->User)) return new \WP_Error('broke', "Votre session a expirer");
      return $this->User;
    }

    // Récuperer le prixe des plan tarifaire pour les offres
    public function get_plan_option( $slug ) {
      if (empty($slug)) return new \WP_Error('broke', "Parametre manquant (slug)");
      $publication_plan = get_field("publication_tariff", 'option');
      $offer_plan = $publication_plan['offer'];
      $query_plan = Arrays::find($offer_plan, function ($value) use ($slug) { return $value['_id'] === $slug; });
      return $query_plan ? intval($query_plan['_p']) : false;
    }

    // Récuperer le prix d'un credit
    public function get_option_wallet() {
      $wallet_price = get_field("product_wallet", 'option');
      return $wallet_price ? intval($wallet_price) : false;
    }

    /**
     * Cette fonction permet de convertir une offre en produit woocommerce
     *
     * @param int $offer_id
     * @param null|string $rateplan
     *
     * @return int|\WP_Error
     */
    public function register_offer_same_product($offer_id, $rateplan = null ) {
      if ( ! is_numeric($offer_id) || empty($offer_id) ) return new \WP_Error('error', "Parameter error (offer_id)");
      $Offer = new Offers($offer_id, true);
      $rateplan = strtoupper($rateplan);
      $args = [
        'post_title' => "{$rateplan} ({$Offer->postPromote})",
        'post_status' => "publish",
        'post_type' => 'product',
      ];

      $current_post_id = wp_insert_post($args, true);
      if ( ! is_wp_error($current_post_id) ) {
        $plan_price = $this->get_plan_option(strtolower($rateplan));
        // Add product meta here ...
        wp_set_object_terms($current_post_id, 'simple', 'product_type');
        update_post_meta( $current_post_id, '_visibility', 'visible');
        update_post_meta( $current_post_id, '_stock_status', 'instock');
        update_post_meta( $current_post_id, 'total_sales', '0');
        update_post_meta( $current_post_id, '_downloadable', 'no');
        update_post_meta( $current_post_id, '_virtual', 'yes');
        update_post_meta( $current_post_id, '_regular_price', $plan_price);
        update_post_meta( $current_post_id, '_featured', 'no');
        update_post_meta( $current_post_id, '_sku', "{$rateplan}{$current_post_id}");
        update_post_meta( $current_post_id, '_price', $plan_price);
        update_post_meta( $current_post_id, '_sold_individually', 'yes');
        update_post_meta( $current_post_id, '_manage_stock', 'no');
        update_post_meta( $current_post_id, '_backorders', 'no');
        // Custom field ...
        update_post_meta( $current_post_id, '__type', 'offers');
        update_post_meta( $current_post_id, '__id', $offer_id);

        return $current_post_id;
      } else return false;
    }

    public function register_formation_same_product($formation_id) {

    }

    public function set_billing() {

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
    public function getRecentlyPost( $class_name, $numberposts = 3, $meta_query = [], $orderby = 'DATE', $order = 'DESC' ) {
      $recentlyContainer = [];
      $this->args        = [
        'post_type'      => $class_name,
        'post_status'    => [ 'publish' ],
        'posts_per_page' => $numberposts,
        'orderby'        => $orderby,
        'order'          => $order
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
        'orderby'        => 'ID',
        'order'          => 'DESC'
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
      $Types = ['offers', 'candidate', 'company', 'annonce', 'works'];
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