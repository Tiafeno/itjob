<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 10/02/2019
 * Time: 22:01
 */

namespace includes\post;

use includes\object\jobServices;

if (!defined('ABSPATH')) {
  exit;
}

class Works
{
  private static $error = false;
  private        $email = null;
  public $ID                 = 0;
  public $status             = '';
  public $post_type          = 'works';
  public $activated          = false;
  public $author             = null;
  public $title              = null;
  public $price              = 0;
  public $reference          = null;
  public $featured           = 0;
  public $featured_datelimit = null;
  public $description        = null;
  public $type               = null;
  public $region             = null;
  public $town               = null;
  public $activity_area      = null;
  public $address            = null;
  public $cellphone          = null;
  public $gallery            = [];
  public $url                = '';
  public $date_create      = '';
  public $date_publication = '';

  public
  function __construct ($work_id = null, $private_access = false)
  {
    if (is_null($work_id)) {
      self::$error = new \WP_Error("broken", "L'identification du travail est introuvable");
      return false;
    }
    /**
     * @func get_post
     * (WP_Post|array|null) Type corresponding to $output on success or null on failure.
     * When $output is OBJECT, a WP_Post instance is returned.
     */
    $output = get_post((int)$work_id);
    $this->ID = $output->ID;

    if (is_null($output)) {
      self::$error = new \WP_Error('broken', "Travail introuvable dans le systeme");
      return false;
    }

    if (!$this->is_work()) {
      self::$error = new \WP_Error('broken', "Le post n'est pas une travail temporaire");
      return false;
    }

    $this->title   = $output->post_type;
    $this->description = apply_filters('the_content', $output->post_content);
    $this->excerpt = $output->post_excerpt;
    $this->title  = $output->post_title;
    $this->status = $output->post_status;
    $this->date_publication = $output->post_date;
    $this->date_publication_format = get_the_date('j F Y', $output);
    $this->url = get_the_permalink($output->ID);

    $this->email = get_field('email', $this->ID);
    $user = get_user_by('email', trim($this->email));
    if (!$user) {
      self::$error = new \WP_Error('broken', "L'utilisateur est introuvable");
      return false;
    }
    if ($private_access)
      $this->author = jobServices::getUserData($user->ID);

    $this->get_tax_field();
    $this->get_acf_field();
  }

  public
  function is_work ()
  {
    return get_post_type($this->ID) === $this->post_type;
  }

  private
  function get_tax_field ()
  {
    $regions   = wp_get_post_terms($this->ID, 'region', ["fields" => "all"]);
    $towns     = wp_get_post_terms($this->ID, 'city', ["fields" => "all"]);
    $activity_area     = wp_get_post_terms($this->ID, 'branch_activity', ["fields" => "all"]);
    $this->region = is_array($regions) && !empty($regions) ? $regions[0] : null;
    $this->town   = is_array($towns) && !empty($towns) ? $towns[0] : null;
    $this->activity_area   = is_array($activity_area) && !empty($activity_area) ? $activity_area[0] : null;
  }

  private
  function get_acf_field ()
  {
    $this->featured           = get_field('featured', $this->ID); // false|true
    $this->featured_datelimit = get_field('featured_datelimit', $this->ID); // Y-m-d H:i:s
    $this->cellphone          = get_field('cellphone', $this->ID); // number
    $this->gallery            = get_field('gallery', $this->ID);
    $this->activated          = get_field('activated', $this->ID);
    $this->price              = get_field('price', $this->ID);
    $this->reference          = get_field('reference', $this->ID);
    $this->address            = get_field('address', $this->ID);
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

}