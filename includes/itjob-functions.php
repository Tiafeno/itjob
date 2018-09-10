<?php

function itjob_pagination() {
  global $wp_query;
  $total = $wp_query->max_num_pages;

  echo '<div class="navigation">';
  echo paginate_links( array(
    'base'     => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
    'format'   => '?paged=%#%',
    'current'  => max( 1, get_query_var( 'paged' ) ),
    'total'    => $total,
    'mid_size' => 4,
    'type'     => 'list'
  ) );
  echo '</div>';
}