<?php
global $annonce;
if (!$annonce->is_activated()) {
  return;
}
$current_url = get_the_permalink(get_the_ID());
?>
<li class="media">
  <div data-bg-image="<?= empty($annonce->featured_image) ? '' : $annonce->featured_image[0] ?>"  data-url="<?= $current_url ?>" class="media-img mr-3"
       data-height="170" data-width="240">
    <div class="d-none">
      <?php if (!empty($annonce->featured_image)): ?>
      <img src="<?= $annonce->featured_image[0] ?>" alt="<?= $annonce->title ?>" >
      <?php endif; ?>
    </div>
  </div>

  <div class="media-body d-flex">
    <div class="flex-1">
      <h5 class="media-heading font-18">
        <a href="<?= $current_url ?>"><?= ucfirst($annonce->title) ?></a>
      </h5>
      <p class="font-16">à partir de <span class="price"><?= $annonce->price ?></span></p>
      <p class="font-13 text-light"><?= substr(strip_tags($annonce->description), 0 , 250) ?> [...]</p>
      <div class="font-12">
                  <span class="mr-4">Déposé le <span><?= $annonce->date_publication_format ?></span>
                  </span>
        <span class="font-bold"><i class="fa fa-map-marker mr-2"></i><?= $annonce->region->name ?></span>
      </div>
    </div>
  </div>
</li>

<?php if (empty($annonce)) : ?>
<li class="media p-0">
  <div class="media-body d-flex">
    <div class="flex-1">
      <p class="font-13 text-light">Aucune annonce n'est disponible pour le moment</p>
    </div>
  </div>
</li>
<?php endif; ?>