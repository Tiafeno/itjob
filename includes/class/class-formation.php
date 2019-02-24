<?php

namespace includes\post;

use includes\model\Model_Subscription_Formation;

if (!defined('ABSPATH')) {
  exit;
}

final
class Formation
{
  public  $ID                 = 0;
  public  $activation         = 0;
  public  $title              = null;
  public  $featured           = 0;
  public  $featured_datelimit = null;
  public  $formation_url      = '';
  public  $diploma;
  public  $status             = null;
  public  $establish_name     = null;
  public  $region             = null;
  public  $activity_area      = null;
  public  $duration           = null;
  public  $price              = 0;
  public  $description        = null;
  public  $date_limit         = null;
  public  $date_create        = null;
  public  $reference          = null;
  public  $distance_learning  = false;
  public  $address            = null;
  private $email              = null;

  public
  function __construct ($formation_id = null, $private_access = false)
  {
    if (is_null($formation_id)) return false;
    $formation_id = (int)$formation_id;
    $post_type = get_post_type($formation_id);
    if ($post_type !== 'formation') return false;

    $post_formation = get_post((int)$formation_id);
    $this->ID = $post_formation->ID;
    $this->title = $post_formation->post_title;
    $this->status = $post_formation->post_status;
    $this->description = $post_formation->post_content;
    $this->date_create = $post_formation->post_date;
    $this->formation_url = get_the_permalink($post_formation->ID);
    $activation    = get_field('activated', $formation_id);
    $this->activation = boolval($activation);
    $this->establish_name = get_field('establish_name', $formation_id);
    $this->address = get_field('address', $formation_id);
    $this->email = get_field('email', $formation_id);
    $this->duration = get_field('duration', $formation_id);
    $price = get_field('price', $formation_id);
    $this->price = $price ? intval($price) : 0;
    $this->date_limit = get_field('date_limit', $formation_id); // Format: Y-m-d
    $this->reference = get_field('reference', $formation_id);
    $this->diploma = get_field('diploma', $formation_id);
    $distance_learning = get_field('distance_learning', $formation_id);
    $this->distance_learning = boolval($distance_learning);
    $featured = get_field('featured', $formation_id);
    $this->featured = boolval($featured);
    if ($featured) {
      $this->featured_datelimit = get_field('featured_datelimit', $formation_id); // Format: Y-m-d H:i:s
    }

    $this->region = wp_get_post_terms($formation_id, 'region', ["fields" => "all"]);
    $this->activity_area = wp_get_post_terms($formation_id, 'branch_activity', ["fields" => "all"]);

    if ($private_access) {
      $this->get_private_informations();
    }
  }

  public function getSubscription() {

  }

  public function getId() {
    return $this->ID;
  }

  private function get_private_informations ()
  {
    $this->__ = [
      'address' => $this->get_address(),
      'author'  => $this->get_author(),
      'subscription' => $this->get_subscription()
    ];
  }

  private function get_address ()
  {
    return $this->address;
  }

  private function get_author ()
  {
    if (!filter_var($this->email, FILTER_SANITIZE_EMAIL)) return null;
    $User = get_user_by('email', $this->email);
    $user_data = get_userdata($User->ID);

    return $user_data;
  }

  private function get_subscription() {
    $subscription  = Model_Subscription_Formation::get_subscription($this->ID);
    return $subscription;
  }
}