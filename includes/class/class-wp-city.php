<?php

namespace includes\object;

if ( ! class_exists( 'WP_City' ) ) {
  final class WP_City {
    public $postal_code;

    public $term_id;
    public $filter = 'raw';
    public $count = 0;
    public $parent_id = 0;
    public $parent_name;
    public $description = '';
    public $taxonomy = '';
    public $term_taxonomy_id = 0;
    public $term_group = '';
    public $slug = '';
    public $name = '';

    public static function get_instance( $term_id, $taxonomy = 'city' ) {
      $term_id = (int) $term_id;
      if ( ! $term_id ) {
        return false;
      }
      $term = get_term( $term_id, $taxonomy );
      if ( is_wp_error( $term ) || is_null( $term ) ) {
        return false;
      }

      return new WP_City( $term );
    }

    public function __construct( $term ) {
      foreach ( get_object_vars( $term ) as $key => $value ) {
        if ( $key === 'parent' ) {
          if ( $term->$key !== 0 ) {
            $pterm             = get_term( $value, $this->taxonomy );
            $this->parent_id   = $value;
            $this->parent_name = $pterm->name;
            $this->postal_code = (int) $pterm->slug;
            continue;
          }
        }
        $this->$key = $value;
      }

    }

    public function getString() {
      return sprintf( '%s - %s', $this->parent_name, $this->name );
    }
  }
}