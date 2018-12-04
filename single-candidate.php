<?php
global $candidate;
get_header();
wp_enqueue_style( 'timeline', get_template_directory_uri() . '/assets/css/timeline.css' );
?>
  <style>
    ol.candidate-skill-list {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
    }

    ol.candidate-language-list {
      list-style-type: disc;
    }

    ol.candidate-language-list li {
      margin-right: 32px;
      float: left;
    }

    .candidate-skill-list li p {
      max-width: calc(100% - 40px);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      line-height: 21px;
      font-weight: 600;
    }

    .candidate-skill-list li {
      flex-basis: 360px;
      position: relative;
      max-width: 360px;
      margin: 0;
      padding: 0;
      border: 0;
      outline: 0;
      font-size: 100%;
      vertical-align: baseline;
      list-style: none;
    }

    i.candidate-icon-verify {
      color: #7ac943;
      font-size: 20px;
      margin-bottom: auto;
      margin-top: auto;
    }

    .candidate-content .informations i {
      font-size: 16px;
    }

    .candidate-content .informations p {
      display: flex;
    }

    .cd-timeline-block .cd-timeline-content {
      max-width: 510px;
    }
  </style>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div uk-grid>
        <div class="uk-width-3-4@s bg-transparent">
          <!--          Content here ... -->
          <?php
          while ( have_posts() ) : the_post();
            if ( ! $candidate->has_cv ) {
              echo sprintf( "CV en attente de confirmation. Veillez réessayer plus tard." );
              break;
            }
            ?>
            <div class="candidate-section ibox-body">
              <div class="candidate-top d-block pb-4">
                <div class="row">
                  <div class="col-md-6 d-flex">
                    <h5 class="text-uppercase uk-margin-auto-vertical">
                      Détail du CV
                    </h5>
                  </div>
                </div>
              </div>

              <div class="candidate-content d-block mt-4 mb-2">
                <div class="row mb-5">
                  <div class="col-md-8">
                    <h1 class="d-flex">
                      <i class="fa fa-check-circle mr-2 candidate-icon-verify"></i>
                      <?= $candidate->title ?>
                    </h1>
                    <h5
                      class="mb-2 text-muted"><?= isset( $candidate->branch_activity->name ) ? $candidate->branch_activity->name : "" ?></h5>
                    <!--                    Devider-->
                    <hr class="uk-width-4-4">

                    <div class="row">
                      <div class="col-md-6 mt-3">
                        <p class="mb-1 uk-text-bold">Emploi recherché:</p>
                        <?php
                        $jobs = [];
                        if ( isset( $candidate->jobSought ) && ! empty( $candidate->jobSought ) ) {
                          if ( is_array( $candidate->jobSought ) ) {
                            foreach ( $candidate->jobSought as $job ) :
                              if ( $job->activated ) {
                                echo sprintf( '<span class="badge badge-blue mr-2 mt-1" style="white-space: pre-line;">%s</span>',
                                  ucfirst( $job->name ) );
                              }
                            endforeach;
                          } else {
                            $job = $candidate->jobSought->name;
                            $values = strpos(',', $job) ? explode(',', $job) : [$job];
                            foreach ($values as $value)
                              echo sprintf( '<span class="badge badge-blue mr-2 mt-1" style="white-space: pre-line;">%s</span>',
                                ucfirst( $value ) );
                          }

                        } else {
                          echo "Non renseigné";
                        }

                        ?>
                      </div>

                      <div class="col-md-6 mt-3">
                        <p class="mb-1 uk-text-bold">Permis de conduire:</p>
                        <?php
                        if ( ! empty( $candidate->driveLicences ) ) {
                          $dls = array_filter( $candidate->driveLicences, function ( $dl ) {
                            return $dl !== '' || ! empty( $dl );
                          } );
                          if ( empty( $dls ) ) {
                            echo 'Aucun';
                          }
                          foreach ( $dls as $driveLicence ) :
                            echo sprintf( '<span class="badge badge-default mr-2">%s</span>', $driveLicence['label'] );
                          endforeach;
                        } else {
                          echo 'Aucun';
                        }
                        ?>
                      </div>

                      <div class="col-md-6 mt-3">
                        <p class="mb-1 uk-text-bold">Statut du candidat:</p>
                        <span class="badge badge-success mr-2"><?= $candidate->status['label'] ?></span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4 d-flex align-items-top">
                    <div class="mt-2 informations">
                      <p class="mb-2"><i class="ti-id-badge mr-2"></i>
                        <?php
                        if ( is_array( $candidate->greeting ) ) {
                          echo $candidate->greeting['value'] === 'mr' ? 'Homme' : 'Femme';
                        } else {
                          echo "Inconnu";
                        }
                        ?>
                      </p>
                      <p class="mb-2"><i
                          class="ti-map-alt mr-2"></i> <?= isset( $candidate->region->name ) ? $candidate->region->name : 'inconnu' ?>
                      </p>
                      <p class="mb-2"><i class="ti-agenda mr-2"></i> Déposée le <?= $candidate->dateAdd ?></p>
                    </div>
                  </div>
                </div>

                <hr>

                <div class="candidate-experience mt-5 mb-5">
                  <h4><i class="fa fa-graduation-cap"></i> Formations</h4>
                  <div class="cd-timeline timeline-1">
                    <?php
                    foreach ( $candidate->trainings as $training ) :
                      if ( ! $training->validated ) {
                        continue;
                      }
                      $trBegin = $training->training_dateBegin;
                      ?>
                      <div class="cd-timeline-block">
                        <div class="cd-timeline-icon bg-success text-white"><i class="fa fa-graduation-cap"></i></div>
                        <div class="cd-timeline-content">
                          <h5><?= $training->training_diploma ?></h5>
                          <h6 class="text-muted"><?= $training->training_establishment ?></h6>
                          <p><?= $training->training_city . ', ' . $training->training_country ?></p>
                          <span class="cd-date badge badge-success"><?= ucfirst( $training->training_dateBegin ) ?>
                            <?= ucfirst( $training->training_dateEnd !== $trBegin ? ' <b>-</b> ' . $training->training_dateEnd : '' ) ?></span>
                        </div>
                      </div>
                    <?php
                    endforeach;
                    ?>
                  </div>
                </div>

                <hr class="uk-devider">
                <div class="candidate-experience mt-5">
                  <h4><i class="fa fa-user"></i> Experiences professionnelles</h4>
                  <div class="cd-timeline timeline-1"> <!-- center-orientation -->
                    <?php
                    foreach ( $candidate->experiences as $experience ) :
                      if ( ! $experience->validated ) {
                        continue;
                      }
                      ?>
                      <div class="cd-timeline-block">
                        <div class="cd-timeline-icon bg-primary text-white"><i class="fa fa-user"></i></div>
                        <div class="cd-timeline-content">
                          <h5><span class="font-bold"><?= ucfirst( $experience->exp_positionHeld ) ?></span></h5>
                          <!--                          <h6 class="text-muted">--><?//= $experience->exp_company
                          ?><!--</h6>-->
                          <p><?= $experience->exp_city . ', ' . $experience->exp_country ?></p>

                          <h6 class="text-muted">Secteur d'activité: </h6>
                          <?php
                          if ( $experience->exp_branch_activity && isset($experience->exp_branch_activity->name)) :
                            ?>
                            <div class="mb-3"><?= $experience->exp_branch_activity->name ?></div>
                          <?php
                          else:
                            $abranch = $experience->old_value['exp_branch_activity'];
                            echo sprintf( "<div class='mb-3'>%s</div>", $abranch ? $abranch : "Non renseigné" );
                          endif;
                          ?>

                          <?php if ( $experience->exp_mission ) : ?>
                            <h6 class="text-muted">Missions et tâches realisées: </h6>
                            <div><?= $experience->exp_mission ?></div>
                          <?php endif; ?>

                          <?php
                          if ( ! empty( $experience->exp_dateBegin ) ):
                            ?>
                            <span
                              class="cd-date badge badge-primary"><?= date_i18n( "F, Y", strtotime( $experience->exp_dateBegin ) ) ?>
                              <b>-</b>
                              <?= $experience->exp_dateEnd ? date_i18n( "F, Y", strtotime( $experience->exp_dateEnd ) ) : 'Aujourd’hui' ?>
                            </span>
                          <?php
                          else:
                            $message = sprintf( "<span class=\"cd-date badge badge-primary\">%s", $experience->old_value['exp_dateBegin'] );
                            if ( ! empty( $experience->old_value['exp_dateBegin'] ) ) {
                              $message .= sprintf( " <b>-</b> %s", $experience->old_value['exp_dateEnd'] );
                            }
                            $message .= "</span>";
                            echo $message;
                          endif;
                          ?>
                        </div>
                      </div>
                    <?php
                    endforeach;
                    ?>
                  </div>
                </div>

                <!--                Outils et technologie-->
                <?php if ( ! empty( $candidate->softwares ) ) { ?>
                  <div class="mt-5">
                    <h4>Logiciels</h4>
                    <hr class="uk-devider">
                    <ol class="candidate-skill-list ml-0 pl-0">
                      <?php
                      foreach ( $candidate->softwares as $software ) :
                        if ( $software->activated ) {
                          echo sprintf( "<li><p>%s</p></li>", $software->name );
                        }
                      endforeach;
                      ?>
                    </ol>
                  </div>
                  <?php
                } ?>

                <!--                Langues-->
                <div class="mt-5">
                  <h4>Langues</h4>
                  <hr class="uk-devider">
                  <div class="row">
                    <div class="col-1">
                      <h2 class="text-right text-blue m-0"><?= count( $candidate->languages ) ?></h2>
                    </div>
                    <div class="col-11 d-flex align-items-center">
                      <ol class="candidate-language-list m-0 pl-0">
                        <?php
                        if ( ! empty( $candidate->languages ) ) {
                          $lgs = array_filter( $candidate->languages, function ( $lg ) {
                            return $lg !== '' || ! empty( $lg );
                          } );
                          if ( empty( $lgs ) ) {
                            echo 'Aucun';
                          }
                          foreach ( $lgs as $language ) :
                            echo sprintf( "<li><p class='mb-0'>%s</p></li>", $language->name );
                          endforeach;
                        } else {
                          echo 'Aucune';
                        }

                        ?>
                      </ol>
                    </div>
                  </div>
                </div>

                <!--                Centre d'intérêt-->
                <div class="mt-5">
                  <div class="row">
                    <div class="col-6">
                      <h4>Centre d'intérêt</h4>
                      <hr class="uk-devider">
                      <ol class="candidate-language-list mt-0">
                        <?php
                        if ( isset( $candidate->centerInterest->various ) ) {
                          $various = $candidate->centerInterest->various;
                          foreach ( explode( ',', $various ) as $item ) :
                            echo sprintf( "<li><p class='mb-0'>%s</p></li>", $item );
                          endforeach;
                        } else {
                          echo "Neant";
                        }

                        ?>
                      </ol>
                    </div>
                    <?php if ( isset( $candidate->centerInterest->projet ) && ! empty( trim( $candidate->centerInterest->projet ) ) ) { ?>
                      <div class="col-6 ">
                        <h4>Projet</h4>
                        <hr class="uk-devider">
                        <p class="c mt-0">
                          <?= $candidate->centerInterest->projet ?>
                        </p>
                      </div>
                      <?php
                    } ?>
                  </div>
                </div>
              </div>

              <div class="candidate-footer ibox-footer mt-lg-3">
                <div class="row pt-3">
                  <div class="col-md-4"></div>
                  <div class="col-md-8 text-right">

                    <?php do_action( 'i_am_interested_this_candidate' ); ?>

                    <a href="<?= get_post_type_archive_link( 'candidate' ) ?>" class="ml-2">
                      <button class="btn btn-outline-secondary btn-fix">
                        <span class="btn-icon"><i class="ti-angle-left"></i>Retour</span>
                      </button>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php
          endwhile;
          ?>
        </div>
        <div class="uk-width-1-4@s">
          <!--     Sidebar here ...     -->
        </div>

      </div>
    </div>
  </div>
<?php
get_footer();
