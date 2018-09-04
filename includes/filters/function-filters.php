<?php
function itjob_filter_engine( $Engine ) {
  if ( ! $Engine instanceof Twig_Environment ) {
    return false;
  }
  $Engine->addFilter( new Twig_SimpleFilter( 'get_permalink', function ( $post_id ) {
    return get_the_permalink( (int) $post_id );
  } ) );

  return true;
}
?>
