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

  return true;
}
?>
