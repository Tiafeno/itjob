<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 10/02/2019
 * Time: 08:24
 */

namespace includes\post;

use includes\object\jobServices;

if (!defined('ABSPATH')) {
  exit;
}

final
class Annonce
{
  private static    $error   = false;
  private           $email   = null;
  private $author            = null;
  public $ID                 = 0;
  public $status             = '';
  public static $post_type   = 'annonce';
  public $activated          = false;
  public $title              = null;
  public $price              = 0;
  public $reference          = null;
  public $featured           = 0;
  public $featured_datelimit = null;
  public $description        = null;
  public $type               = null;
  public $region             = null;
  public $town               = null;
  public $address            = null;
  public $categorie          = null;
  public $cellphone          = null;
  public $gallery            = [];
  public $featured_image     = null;
  public $url                = '';
  public $date_create      = '';
  public $date_publication = '';
  public $contact_sender = [];

  public
  function __construct ($annonce_id = null, $private_access = false)
  {
    if (is_null($annonce_id)) {
      self::$error = new \WP_Error("broken", "L'identification de l'annonce est introuvable");
      return false;
    }
    /**
     * @func get_post
     * (WP_Post|array|null) Type corresponding to $output on success or null on failure.
     * When $output is OBJECT, a WP_Post instance is returned.
     */
    $output = get_post((int)$annonce_id);
    if (is_null($output)) {
      self::$error = new \WP_Error('broken', "L'annonce est introuvable dans le systeme");
      return false;
    }

    if (!self::is_annonce($annonce_id)) {
      self::$error = new \WP_Error('broken', "Le post n'est pas une annonce");
      return false;
    }

    $this->ID = $output->ID;
    $this->description = apply_filters('the_content', $output->post_content);
    $this->excerpt = apply_filters('the_content', $output->post_excerpt);
    $this->title  = $output->post_title;
    $this->status = $output->post_status;
    $this->date_publication = $output->post_date;
    $this->date_publication_format = get_the_date('j F, Y', $output);
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
    $contact_sender = get_post_meta($this->ID, 'sender_contact', true);
    $this->contact_sender = empty($contact_sender) ? [] : $contact_sender;

  }

  public function get_author() {
    if (is_null($this->author)) {
      $User = get_field('annonce_author', $this->ID);
      $this->author = $User;
    }
    return $this->author;
  }

  public function get_mail() {
    return $this->email;
  }

  public static
  function is_annonce ($annonce_id)
  {
    $post_type = get_post_type($annonce_id);
    return $post_type === self::$post_type;
  }

  private
  function get_tax_field ()
  {
    $regions   = wp_get_post_terms($this->ID, 'region', ["fields" => "all"]);
    $towns     = wp_get_post_terms($this->ID, 'city', ["fields" => "all"]);
    $categorie = wp_get_post_terms($this->ID, 'categorie', ["fields" => "all"]);

    $this->region = is_array($regions) && !empty($regions) ? $regions[0] : null;
    $this->town   = is_array($towns) && !empty($towns) ? $towns[0] : null;
    $this->categorie = is_array($categorie) && !empty($categorie) ? $categorie[0] : null;
  }

  private
  function get_acf_field ()
  {
    $this->featured = get_field('featured', $this->ID); // false|true
    $this->featured_datelimit = get_field('featured_datelimit', $this->ID); // Y-m-d H:i:s
    $this->type     = get_field('type', $this->ID); // ['value' => <string>, 'label' => <string>]
    $this->cellphone = get_field('cellphone', $this->ID); // number
    $gallery   = get_field('gallery', $this->ID);
    $this->gallery = is_array($gallery) ? $gallery : [];
    $this->activated = get_field('activated', $this->ID);
    $this->price     = get_field('price', $this->ID);
    $this->reference = get_field('reference', $this->ID);
    $this->address   = get_field('address', $this->ID);
    $this->featured_image = wp_get_attachment_image_src(get_post_thumbnail_id($this->ID), 'medium');
  }

  public static
  function is_wp_error ()
  {
    if (is_wp_error(self::$error)) {
      return self::$error->get_error_message();
    } else {
      return false;
    }
  }

  public
  function is_activated ()
  {
    return $this->activated ? 1 : 0;
  }

  public function add_contact_sender( $user_id ) {
    $senders = get_post_meta($this->ID, 'sender_contact', true);
    $senders = is_array($senders) ? $senders : [];
    if (intval($user_id) === 0) return false;
    $senders[] = intval($user_id);

    update_post_meta($this->ID, 'sender_contact', $senders);
    return true;
  }

}