<?php
global $formation, $wp, $post;
get_header();
wp_enqueue_style('themify-icons');
wp_enqueue_style('offers');

?>
  <style type="text/css">
    .offer-top {
      border-bottom: .5px solid #888484;
      width: 100%;
    }

    .offer-footer {
      border-top: .5px solid #888484;
      width: 100%;
    }

    .offer-section .offer-content h1 {
      font-size: 20px;
      font-weight: bold;
    }

    .offer-field-title {
      color: #0C62A2;
      font-weight: bold;
    }
  </style>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div uk-grid>
        <div class="uk-width-2-3@s">
          <!--     Vérifier s'il y a une postulation en cours     -->
          <?php do_action('get_notice'); ?>
          <!--          Content here ... -->
          <?php
          while (have_posts()) : the_post();
            $error = FALSE;
            if (isset($_GET['action']) && !empty($_GET['action'])) {
              $action = wp_unslash($_GET['action']);
              $User = wp_get_current_user();
              switch ($action) {
                case 'confirmaction':
                  $user_id = wp_unslash($_GET['user']);
                  // Refuser pour les utilisateurs qui ne sont pas des candidats
                  if (empty($user_id) || $User->ID !== (int)$user_id || !in_array('candidate', $User->roles)) {
                    $error = TRUE;
                    break;
                  }
                  $args = ['formation_id' => $formation->ID, 'user_id' => $User->ID];
                  $result = \includes\model\Model_Subscription_Formation::add_resources($args);
                  if (!$result) {
                    new WP_Error('broke', "Impossible d'ajouter votre inscription dans la base de donnée");
                  } else {
                    do_action("send_registration_formation", $user_id, $formation->ID);
                  }
                  break;
                default:
                  # code...
                  break;
              }
            }

            if (!$formation instanceof \includes\post\Formation) exit;
            if (!isset($_GET['action'])):
              $subscription_url = add_query_arg(['action' => 'subscription'], $wp->request);
              ?>
              <div class="offer-section">
                <div class="offer-top d-inline-block pb-4">
                  <div class="row">
                    <div class="col-md-5 d-flex">
                      <h5 class="text-uppercase uk-margin-auto-vertical">
                        Détail de la formation
                      </h5>
                    </div>
                  </div>
                  <div class="mt-4">
                    <div>Formation ajoutée le : <?= date_i18n('l, j F Y', strtotime($formation->date_create)) ?></div>
                    <div>Réf : <?= $formation->reference ?></div>
                    <div class="uk-text-bold">Date limite d'inscription
                      : <?= date_i18n('l, j F Y', strtotime($formation->date_limit)) ?></div>
                  </div>
                </div>
                <div class="offer-content d-inline-block mt-4">
                  <h1><?= ucfirst($formation->title) ?></h1>
                  <div class="offer-description mt-4">
                    <h5 class="mt-3">Description</h5>
                    <div class="row">
                      <div class="col-md-4 pt-4 pr-lg-5">
                        <p class="offer-field-title m-0">Durée:</p>
                        <p
                          class="offer-field-value m-0"><?= isset($formation->duration) ? $formation->duration : 'Non definie' ?></p>
                      </div>
                      <div class="col-md-4 pt-4 pr-lg-5">
                        <p class="offer-field-title m-0">Region:</p>
                        <p
                          class="offer-field-value m-0"><?= !empty($formation->region) ? $formation->region[0]->name : 'Non definie' ?></p>
                      </div>
                    </div>

                    <div class="mt-4">
                      <div class="offer-field-value">
                        <?= $formation->description ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="offer-footer mt-3">
                  <div class="row pt-5">
                    <div class="col-md-6">

                    </div>
                    <div class="col-md-6">
                      <div>
                        <div class="float-right ml-3">
                          <a href="<?= site_url($subscription_url) ?>" class="float-right pl-2">
                            <button class="btn btn-outline-success btn-fix">
                              <span class="btn-icon">Je m'inscrit</span>
                            </button>
                          </a>
                          <a href="<?= get_post_type_archive_link('formation') ?>" class="float-right pl-2">
                            <button class="btn btn-outline-warning btn-fix">
                              <span class="btn-icon"><i class="ti-angle-left"></i>Retour</span>
                            </button>
                          </a>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>
            <?php
            endif; // .end default view
            ?>
            <?php if (isset($_GET['action']) && $_GET['action'] === "subscription"):
              $confirmaction = add_query_arg(['action' => 'confirmaction', 'user' => $User->ID], $wp->request);
              ?>
              <div class="offer-section">
                <div class="offer-content d-inline-block mt-4">
                  <h1>Voulez-vous vous inscrire sur cette formation? </h1>
                  <h4><?= ucfirst($formation->title) ?></h4>
                  <div class="offer-description mt-4">
                    <h6 class="mt-3">Description</h6>
                    <div class="mt-4">
                      <div class="offer-field-value">
                        <?= $formation->description ?>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="offer-footer mt-3">
                  <div class="row pt-5">
                    <div class="col-md-6" style="color: red">
                      * L'inscription est payante
                    </div>
                    <div class="col-md-6">
                      <div>
                        <div class="float-right ml-3">
                          <a href="<?= site_url($confirmaction) ?>" class="float-right pl-2">
                            <button class="btn btn-success btn-fix">
                              <span class="btn-icon">OUI</span>
                            </button>
                          </a>
                          <a href="<?= site_url($wp->request) ?>" class="float-right pl-2">
                            <button class="btn btn-outline-warning btn-fix">
                              <span class="btn-icon">ANNULER</span>
                            </button>
                          </a>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>
            <?php endif; // .end subscription ?>

            <?php if (isset($_GET['action']) && $_GET['action'] === "confirmaction"):
              if ($error) {
                echo "Une erreur s'est produite, il se peut que votre compte ne vous permet pas de s'inscrire pour une formation. <br>
                Veuillez vous connecter en tant que <b>particulier</b> et réessayer. Merci";
              }
              ?>
              <div class="offer-section">
                <?php if (!$error): ?>
                  <div class="offer-content d-inline-block mt-4">
                    <h1>Succès! </h1>
                    <div class="offer-description mt-4">
                      <div class="mt-4">
                        <div class="offer-field-value">
                          Inscription envoyer avec succès. ITJob vous contacteras pour le paiement <br>et les
                          informations de la formation
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="offer-footer mt-3">
                  <div class="row pt-5">
                    <div class="col-md-6">

                    </div>
                    <div class="col-md-6">
                      <div>
                        <div class="float-right ml-3">
                          <?php if (!$error): ?>
                            <a href="<?= get_post_type_archive_link('formation') ?>" class="float-right pl-2">
                              <button class="btn btn-outline-success btn-fix">
                                <span class="btn-icon">Voir les formations</span>
                              </button>
                            </a>
                          <?php endif; ?>

                          <?php if ($error): ?>
                            <a href="<?= site_url($wp->request) ?>" class="float-right pl-2">
                              <button class="btn btn-outline-success btn-fix">
                                <span class="btn-icon">Retour</span>
                              </button>
                            </a>
                          <?php endif; ?>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>
            <?php endif; // .end subscription ?>

          <?php
          endwhile; // .end loop post
          ?>


        </div>
        <div class="uk-width-1-3@s">
          <!--     Sidebar here ...     -->
          <?php
          if (is_active_sidebar('single-formation-sidebar')) {
            dynamic_sidebar('single-formation-sidebar');
          }
          ?>
        </div>

      </div>
    </div>
  </div>
<?php
get_footer();