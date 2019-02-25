<?php
global $candidate;
if ( ! $candidate->is_activated()) { return; }
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

                  if (is_array($candidate->jobSought)) {
                    foreach ( $candidate->jobSought as $job ) :
                      if ( $job->activated ) {
                        array_push( $job_shought, $job->name );
                      }
                    endforeach;
                  } else {
                    array_push($job_shought, $candidate->jobSought->name);
                  }
                  echo ! empty( $job_shought ) ? implode( ', ', $job_shought ) : 'Aucun';
                  ?>
                </td>
              </tr>
              <?php if (isset($candidate->branch_activity->name)): ?>
              <tr>
                <td>Secteur d'activité:</td>
                <td><?= $candidate->branch_activity->name ?></td>
              </tr>
              <?php endif; ?>
              <tr>
                <td>Permis:</td>
                <td>
                  <?php
                  $driveLicences = [];
                  if ( ! empty($candidate->driveLicences) ) {
                    foreach ( $candidate->driveLicences as $driveLicence ) :
                      if (!is_array($driveLicence)) continue;
                      array_push( $driveLicences, $driveLicence['label'] );
                    endforeach;
                  }
                  echo ! empty( $driveLicences ) ? implode( ', ', $driveLicences ) : 'Aucun';
                  ?>
                </td>
              </tr>
              <tr>
                <td>Langues:</td>
                <td>
                  <?php
                  $languages = [];
                  if ( ! empty($candidate->languages))
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
            <div class="text-right">CV ajouté le <?= $candidate->dateAdd ?></div>
          </div>
        </div>

      </div>
    </div>

    <?php if (!empty($candidate->tags)): ?>
    <div class="card-footer">
      <div class="d-flex align-items-center justify-content-between">
        <div class="text-primary">
          <b>TAG : </b> <span class="card-tag ml-2"><?= implode( ', ', $candidate->tags ) ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
