<?php
function itjob_filter_engine($Engine) {
  if (!$Engine instanceof Twig_Environment) {
    return false;
  }

  $Engine->addFilter(new Twig_SimpleFilter('get_permalink', function ($post_id) {
    return get_the_permalink((int) $post_id);
  }));

  $Engine->addFilter(new Twig_SimpleFilter('wp_get_attachment_url', function ($attach_id) {
    return wp_get_attachment_url((int) $attach_id);
  }));

  $Engine->addFilter(new Twig_SimpleFilter('explode_terms', function ($terms) {
    $exp = [];
    if (!is_array($terms)) return 'Aucun';
    foreach ($terms as $term) :
      if (!is_object($term)) continue;
      array_push($exp, $term->name);
    endforeach;
    return !empty($exp) ? implode(', ', $exp) : 'Aucun';
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
    $tabs = array_filter($tabs, function ($tab) {
      return !empty($tab);
    });
    foreach ($tabs as $tab) :
      array_push($exp, $tab['label']);
    endforeach;
    return !empty($exp) ? implode(', ', $exp) : 'Aucun';
  }));

  $Engine->addFilter(new Twig_SimpleFilter('dateLimited', function ($dateLimit) {
    $badge = '';
    $today = strtotime('today');
    $dteLimit = DateTime::createFromFormat( 'm/d/Y', $dateLimit )->format( 'Y-m-d' );
    $limited = strtotime($dteLimit) < $today;
    if ($limited) {
      $badge .= '<span class="badge badge-danger">Date limite atteinte</span>';
    }
    return $badge;
  }));

  $Engine->addFilter(new Twig_SimpleFilter('datei18n', function ($date) {
    if (!strpos($date, '/')) return $date;
    $date = date_i18n('F Y', strtotime($date));
    return $date;
  }));

  $Engine->addFilter(new Twig_SimpleFilter('dateFormat', function ($date, $format = "l, j F Y") {
    setlocale(LC_MONETARY, 'fr_FR');
    $date = date_i18n($format, strtotime($date));
    return $date;
  }));

  $Engine->addFilter(new Twig_SimpleFilter('html_entity_decode', 'html_entity_decode'));

  $Engine->addFilter(new Twig_SimpleFilter('base64_image', function ($img) {
    // Read image path, convert to base64 encoding
    $imgData = base64_encode(file_get_contents($img));
    $type = pathinfo($img, PATHINFO_EXTENSION);
    $src = 'data: ' . $type . ';base64,' . $imgData;
    return $src;
  }));

  $Engine->addFilter(new Twig_SimpleFilter('currency', function ($number) {
    return number_format($number);
  }));

  return true;
}

?>
