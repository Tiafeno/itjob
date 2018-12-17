<?php
global $offers;
if (!$offers->is_activated()) {
  return;
}

// Vérifier la date limite de l'offre
$today = strtotime("today");
$limited = strtotime($offers->dateLimit) < $today;
?>
<div class="col-md-12">
  <div class="card ibox mb-4">
    <div class="rel">
      <div class="fab fab-bottom card-overlay-fab">
        <button class="btn btn-primary btn-icon-only btn-circle btn-air" data-toggle="button">
          <i class="la la-share-alt fab-icon"></i>
          <i class="la la-close fab-icon-active"></i>
        </button>
        <ul class="fab-menu">
          <li>
            <a class="btn btn-soc-facebook btn-icon-only btn-circle btn-air" href="javascript:;">
              <i class="fa fa-facebook"></i>
            </a>
          </li>
          <li>
            <a class="btn btn-soc-twitter btn-icon-only btn-circle btn-air" href="javascript:;">
              <i class="fa fa-twitter"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
    <div class="card-body">
      <h4 class="card-title mb-4">
        <a href="<?= get_the_permalink($offers->ID) ?>" class="text-primary">
          <?= $offers->postPromote ?>
        </a>
      </h4>
      <div class="card-description">

        <div class="row">
          <div class="col-8 uk-padding-remove-right">
            <table class="table">
              <tbody>
              <tr>
                <td>Région:</td>
                <td><?= isset($offers->region->name) ? $offers->region->name : 'Non definie' ?></td>
              </tr>
              <tr>
                <td>Mission:</td>
                <td>
                  <?php
                  $offers->mission = strip_tags($offers->mission);
                  $mission_words = explode(' ', $offers->mission);
                  foreach ($mission_words as $index => $word) {
                    if (($index < 12))
                      echo " $word";
                  }
                  echo count($mission_words) >= 12 ? ' ... ' : ''
                  ?>
                </td>
              </tr>
              <tr>
                <td>Profil:</td>
                <td>
                  <?php
                  $offers->profil = strip_tags($offers->profil);
                  $profil_words = explode(' ', $offers->profil);
                  foreach ($profil_words as $index => $word) {
                    if (($index < 12))
                      echo " $word";
                  }
                  echo count($profil_words) >= 12 ? ' ... ' : ''
                  ?>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
          <div class="col-4 uk-flex">
            <div class="uk-flex uk-margin-auto-left">
              <a href="<?= get_the_permalink($offers->ID) ?>" class=" uk-margin-auto-vertical">
                <button class="btn btn-warning btn-fix">
                  <span class="btn-icon"> Voir l'offre <i class="la la-plus"></i></span>
                </button>
              </a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <table class="table">
              <tbody style="display: table-row-group;">
              <tr>
                <td class="no-bold uk-text-bold">Ref: <?= $offers->reference ?></td>
                <td class="text-center uk-text-bold">Date limite: <?= $offers->dateLimitFormat ?></td>
                <td class="text-right">Publier le <?= $offers->datePublication ?></td>
              </tr>
            </table>
          </div>
        </div>

      </div>
    </div>

    <div class="card-footer">
      <div class="d-flex align-items-center justify-content-between">
        <div class="text-primary">
          <b>TAG: </b> <span class="card-tag"><?= implode(', ', $offers->tags) ?></span>
        </div>

        <div>
          <?php if ($limited) : ?>
          <span class="badge badge-danger">Date limite atteinte</span>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>
