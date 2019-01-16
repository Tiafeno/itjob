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
    if (!is_array($terms)) return 'Aucun';
    foreach ( $terms as $term ) :
      if (!is_object($term)) continue;
      array_push( $exp, $term->name );
    endforeach;
    return ! empty( $exp ) ? implode( ', ', $exp ) : 'Aucun';
  }));

  $Engine->addFilter(new Twig_SimpleFilter("activated", function ($terms) {
    $termValid = [];
    foreach ($terms as $term) {
      if ($term->activated)
        array_push($termValid, $term->name);
    }
    return $termValid;
  }));

  $Engine->addFilter(new Twig_SimpleFilter('explode_array', function ($tabs) {
    $exp = [];
    if (!is_array($tabs)) return 'Aucun';
    $tabs = array_filter($tabs, function ($tab) { return !empty($tab); });
    foreach ( $tabs as $tab ) :
      array_push( $exp, $tab['label'] );
    endforeach;
    return ! empty( $exp ) ? implode( ', ', $exp ) : 'Aucun';
  }));

  $Engine->addFilter(new Twig_SimpleFilter('dateLimited', function ($dateLimit) {
    $badge = '';
    $today = strtotime('today');
    $limited = strtotime($dateLimit) < $today;
    if ($limited) {
      $badge .= '<span class="badge badge-danger">Date limite atteinte</span>';
    }
    return $badge;
  }));

  $Engine->addFilter(new Twig_SimpleFilter('datei18n', function ($date) {
    $date = date_i18n('j F', strtotime($date));
    return $date;
  }));

  return true;
}
?>
