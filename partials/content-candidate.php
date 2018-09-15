<?php
global $candidate;
// $candidate instanceof \includes\post\Candidate;
// print_r($candidate);
?>
<div class="col-md-12">
  <div class="card ibox mb-4">
    <div class="card-body">
      <h4 class="card-title mb-4">
        <a href="<?= get_the_permalink( $candidate->getId() ) ?>" class="text-primary">
          <?= $candidate->title ?>
        </a>
      </h4>
      <div class="card-description">

        <div class="row">
          <div class="col-8 uk-padding-remove-right">
            <table class="table">
              <tbody>
              <tr>
                <td>L'emploi recherché:</td>
                <td>
                  <?php
                  $job_shought = [];
                  foreach ( $candidate->jobSought as $job ) : array_push( $job_shought, $job->name ); endforeach;
                  echo ! empty( $job_shought ) ? implode( ', ', $job_shought ) : 'Aucun';
                  ?>
                </td>
              </tr>
              <tr>
                <td>Secteur d'activité:</td>
                <td><?= $candidate->branch_activity[0]; ?></td>
              </tr>
              <tr>
                <td>Permis:</td>
                <td>
                  <?php
                  $driveLicences = [];
                  foreach ( $candidate->driveLicences as $driveLicence ) : array_push( $driveLicences, $driveLicence['label'] ); endforeach;
                  echo ! empty( $driveLicences ) ? implode( ', ', $driveLicences ) : 'Aucun';
                  ?>
                </td>
              </tr>
              <tr>
                <td>Langues:</td>
                <td>
                  <?php
                  $languages = [];
                  foreach ( $candidate->languages as $language ) : array_push( $languages, $language->name ); endforeach;
                  echo ! empty( $languages ) ? implode( ', ', $languages ) : 'Aucun';
                  ?>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
          <div class="col-4 uk-flex">
            <div class="uk-flex uk-margin-auto-left">
              <a href="<?= get_the_permalink( $candidate->getId() ) ?>" class=" uk-margin-auto-vertical">
                <button class="btn btn-info btn-fix">
                  <span class="btn-icon"> Voir <i class="la la-plus"></i></span>
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
                <td class="no-bold uk-text-bold"></td>
                <td class="text-center uk-text-bold"></td>
                <td class="text-right">CV ajouté le <?= $candidate->dateAdd ?></td>
              </tr>
            </table>
          </div>
        </div>

      </div>
    </div>

    <div class="card-footer">
      <div class="d-flex align-items-center justify-content-between">
        <div class="text-primary">
          <b>TAG : </b> <span class="card-tag ml-2"><?= implode( ', ', $candidate->tags ) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>
