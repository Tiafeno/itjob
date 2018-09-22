<?php
global $offers;
if ( ! $offers->is_activated()) { return; }
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
        <a href="<?= get_the_permalink( $offers->ID ) ?>" class="text-primary">
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
                <td><?= $offers->region->name ?></td>
              </tr>
              <tr>
                <td>Mission:</td>
                <td>
                  <?php echo substr( strip_tags( $offers->mission ), 0, 75 ); ?>
                  <?= strlen( $offers->mission ) >= 75 ? ' ... ' : '' ?>
                </td>
              </tr>
              <tr>
                <td>Profil:</td>
                <td>
                  <?php echo substr( strip_tags( $offers->profil ), 0, 75 ); ?>
                  <?= strlen( $offers->profil ) >= 75 ? ' ... ' : '' ?>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
          <div class="col-4 uk-flex">
            <div class="uk-flex uk-margin-auto-left">
              <a href="<?= get_the_permalink( $offers->ID ) ?>" class=" uk-margin-auto-vertical">
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
              <tbody>
              <tr>
                <td class="no-bold uk-text-bold">Ref: <?= $offers->reference ?></td>
                <td class="text-center uk-text-bold">Date limite: <?= $offers->dateLimit ?></td>
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
          <b>TAG: </b> <span class="card-tag"><?= implode( ', ', $offers->tags ) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>
