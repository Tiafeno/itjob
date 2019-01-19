<?php

final class apiHelper
{
  public function __construct()
  {
  }
  public function get_taxonomy(WP_REST_Request $rq)
  {
    // TODO: Envoyer seulement les terms activé pour certains taxonomy
    $taxonomy = $rq['taxonomy'];
    $rTerm = [];
    $terms = get_terms($taxonomy, [
      'hide_empty' => false,
      'meta_key' => 'activated',
      'orderby' => 'meta_value_num',
      'order' => 'DESC',
      'fields' => 'all'
    ]);
    if ($taxonomy === 'city') {
      $terms = array_filter($terms, function ($term, $key) use (&$rTerm) {
        if ($term->parent !== 0) {
          $rTerm[] = $term;
        }
        return $term;
      }, ARRAY_FILTER_USE_BOTH);
    } else {
      
      array_map(function ($term) use (&$rTerm, $taxonomy) {
        if ($taxonomy === "software" || $taxonomy === 'job_sought') {
          $activated = get_term_meta( $term->term_id, 'activated', true );
          $name = $activated ? $term->name : $term->name . ' (En attente)';
          $term->name = $name;
          $rTerm[] = $term;
        } else {
          $rTerm[] = $term;
        }
        
      }, $terms);
    }

    return new WP_REST_Response($rTerm);
  }
}