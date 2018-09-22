<?php
function itjob_filter_engine( $Engine ) {
  if ( ! $Engine instanceof Twig_Environment ) {
    return false;
  }
  $Engine->addFilter( new Twig_SimpleFilter( 'get_permalink', function ( $post_id ) {
    return get_the_permalink( (int) $post_id );
  } ) );

  $Engine->addFilter(new Twig_SimpleFilter('wp_get_attachment_url', function ($attach_id) {
    return wp_get_attachment_url( (int)$attach_id );
  }));

  $Engine->addFilter(new Twig_SimpleFilter('explode_terms', function ($terms) {
    $exp = [];
    foreach ( $terms as $term ) : array_push( $exp, $term->name ); endforeach;
    return ! empty( $exp ) ? implode( ', ', $exp ) : 'Aucun';
  }));

  $Engine->addFilter(new Twig_SimpleFilter('explode_array', function ($tabs) {
    $exp = [];
    foreach ( $tabs as $tab ) : array_push( $exp, $tab['label'] ); endforeach;
    return ! empty( $exp ) ? implode( ', ', $exp ) : 'Aucun';
  }));

  $Engine->addFilter(new Twig_SimpleFilter('sinceHome', function ($url) {
    return home_url($url);
  }));

  return true;
}
?>
