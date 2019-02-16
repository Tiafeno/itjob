<?php
global $works;
if (!$works->is_activated()) {
  return;
}
$current_url = get_the_permalink(get_the_ID());
?>

<li class="media pt-0">
  <div class="media-body d-flex">
    <div class="flex-1">
      <h5 class="media-heading">
        <a href="<?= $current_url ?>"><?= $works->title ?></a>
      </h5>
      <p class="font-13 text-light"><?= substr(strip_tags($works->description), 0, 250)  ?> [...]</p>
      <div class="font-13">
                  <span class="mr-4">Déposé le <span><?= $works->date_publication_format ?></span>
                  </span>
        <span class=" mr-4"><i class="fa fa-database mr-2"></i><?= $works->reference ?></span>
        <span class=""><i class="fa fa-map-marker mr-2"></i><?= $works->region->name ?></span>
      </div>
    </div>
    <div class="text-right" style="width:100px;">
      <h3 class="mb-1 font-strong text-primary"><?= $works->count_view ?></h3>
      <div class="text-muted">vue(s)</div>
    </div>
  </div>
</li>