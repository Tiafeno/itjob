<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 10/02/2019
 * Time: 22:01
 */

namespace includes\post;

if (!defined('ABSPATH')) {
  exit;
}

class Works {
  private static $error = false;
  public $ID = 0;
  public $status = '';
  public $post_type = 'works';
  public $activated = false;
  public $author = null;
  public $title = null;
  public $price = 0;
  public $reference = null;
  public $featured = 0;
  public $featured_position = null;
  public $featured_datelimit = null;
  public $description = null;
  public $type = null;
  public $region = null;
  public $town = null;
  public $activity_area = null;
  public $address = null;
  public $cellphone = null;
  public $gallery = [];
  public $url = '';
  public $date_create = '';
  public $date_publication = '';
  public $count_view = 0;
  private $email = null;

  public function __construct($work_id = null, $private_access = false) {
    if (is_null($work_id)) {
      self::$error = new \WP_Error("broken", "L'identification du travail est introuvable");
      return false;
    }
    /**
     * @func get_post
     * (WP_Post|array|null) Type corresponding to $output on success or null on failure.
     * When $output is OBJECT, a WP_Post instance is returned.
     */
    $output = get_post((int) $work_id);
    $this->ID = $output->ID;

    if (is_null($output) || $output->ID === 0) {
      self::$error = new \WP_Error('broken', "Travail introuvable dans le systeme");
      return false;
    }

    if (!$this->is_work()) {
      self::$error = new \WP_Error('broken', "Le post n'est pas un travail temporaire");
      return false;
    }

    $this->title = $output->post_type;
    $this->description = apply_filters('the_content', $output->post_content);
    $this->excerpt = $output->post_excerpt;
    $this->title = $output->post_title;
    $this->status = $output->post_status;
    $this->date_publication = $output->post_date;
    $this->date_publication_format = get_the_date('j F Y', $output);
    $this->url = get_the_permalink($output->ID);

    $this->email = get_field('email', $this->ID);
    $User = get_field('annonce_author', $this->ID);
    if (!$User) {
      self::$error = new \WP_Error('broken', "L'utilisateur est introuvable");
      return false;
    }
    if ($private_access)
      $this->author = $User;

    $this->get_tax_field();
    $this->get_acf_field();

    $this->date_create = get_post_meta($this->ID, 'date_create', true);
    $view = get_post_meta($this->ID, 'count_view', true);
    // Récuperer les utilisateurs qui ont contacté l'annnonce
    $contact_sender = get_post_meta($this->ID, 'sender_contact', true);
    $this->count_view = $view ? (int) $view : 0;
    $this->contact_sender = empty($contact_sender) ? [] : $contact_sender;
  }

  public function is_work() {
    return get_post_type($this->ID) === $this->post_type;
  }

  private function get_tax_field() {
    $regions = wp_get_post_terms($this->ID, 'region', ["fields" => "all"]);
    $towns = wp_get_post_terms($this->ID, 'city', ["fields" => "all"]);
    $activity_area = wp_get_post_terms($this->ID, 'branch_activity', ["fields" => "all"]);
    $this->region = is_array($regions) && !empty($regions) ? $regions[0] : null;
    $this->town = is_array($towns) && !empty($towns) ? $towns[0] : null;
    $this->activity_area = is_array($activity_area) && !empty($activity_area) ? $activity_area[0] : null;

  }

  private function get_acf_field() {
    $this->featured = get_field('featured', $this->ID); // false|true
    $this->featured_datelimit = get_field('featured_datelimit', $this->ID); // Y-m-d H:i:s
    $this->type = get_field('type', $this->ID); // ['value' => <string>, 'label' => <string>]
    $this->cellphone = get_field('cellphone', $this->ID); // number
    $this->gallery = get_field('gallery', $this->ID);
    $this->activated = get_field('activated', $this->ID);
    $this->price = get_field('price', $this->ID);
    $this->reference = get_field('reference', $this->ID);
    $this->address = get_field('address', $this->ID);
    $position = get_field('featured_position', $this->ID);
    $this->featured_position = intval($position) === 0 ? null : intval($position); // 1: A la une, 2 : Dans la liste
  }

  public static function is_wp_error() {
    if (is_wp_error(self::$error)) {
      return self::$error->get_error_message();
    } else {
      return false;
    }
  }

  public function get_mail() {
    return $this->email;
  }

  public function get_user() {
    $User = get_field('annonce_author', $this->ID); // WP_User
    return $this->author = $User;
  }

  public function is_activated() {
    return $this->activated ? 1 : 0;
  }

  public function increment_view() {
    $this->count_view += 1;
    update_post_meta($this->ID, 'count_view', $this->count_view);
  }

  /**
   * Cette fonction permet d'ajouter les utilisateurs qui ont contacter l'annonceur
   *
   * @param $user_id {int}
   * @return bool
   */
  public function add_contact_sender($user_id) {
    $senders = get_post_meta($this->ID, 'sender_contact', true);
    $senders = is_array($senders) ? $senders : [];
    if (intval($user_id) === 0) return false;
    $senders[] = intval($user_id);

    update_post_meta($this->ID, 'sender_contact', $senders);
    return true;
  }

  public function has_contact($user_id) {
    if (empty($this->contact_sender) || !is_array($this->contact_sender)) return false;
    return in_array(intval($user_id), $this->contact_sender);
  }

}