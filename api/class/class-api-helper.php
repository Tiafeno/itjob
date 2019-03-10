<?php

final class apiHelper
{
  public function __construct()
  {
  }
  public function get_taxonomy(WP_REST_Request $rq)
  {
    $taxonomy = $rq['taxonomy'];
    $rTerm = [];
    $terms = get_terms($taxonomy, [
      'hide_empty' => false,
      'fields' => 'all'
    ]);
    if ($taxonomy === 'city') {
      array_filter($terms, function ($term, $key) use (&$rTerm) {
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

// Convertir les caractéres en UTF-8 pour la taxonomie 'categorie'
function prepare_restful_categories($response, $item, $request) {
  // Do stuff to categorie
  $name = $response->data['name'];
  $response->data['name'] = html_entity_decode( $name, ENT_QUOTES, 'UTF-8');
  return $response;
}
add_filter('rest_prepare_categorie', 'prepare_restful_categories', 10, 3);