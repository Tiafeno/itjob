<?php
global $offers;
get_header();
wp_enqueue_style( 'themify-icons' );
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
          <?php do_action('send_apply_offer') ?>
          <?php do_action('get_notice'); ?>
          <!--          Content here ... -->
          <?php
          while ( have_posts() ) : the_post();
            if ( ! $offers instanceof  \includes\post\Offers) exit;
            ?>
            <div class="offer-section">
              <div class="offer-top d-inline-block pb-4">
                <div class="row">
                  <div class="col-md-5 d-flex">
                    <h5 class="text-uppercase uk-margin-auto-vertical">
                      Détail de l'offre
                    </h5>
                  </div>
                </div>
                <div class="mt-4">
                  <div>Offre ajoutéé le : <?= $offers->datePublication ?></div>
                  <div>Réf : <?= $offers->reference ?></div>
                  <div class="uk-text-bold">Date limite de candidature : <?= $offers->dateLimit ?></div>
                </div>
              </div>

              <div class="offer-content d-inline-block mt-4">
                <h1><?= $offers->postPromote ?></h1>
                <div class="offer-description mt-4">
                  <h5 class="mt-3">Description</h5>
                  <div class="row mt-4">
                    <div class="col-md-3 pt-4 pr-lg-5">
                      <p class="offer-field-title m-0">Région:</p>
                      <p class="offer-field-value m-0"><?= $offers->region->name ?></p>
                    </div>
                    <div class="col-md-3 pt-4">
                      <p class="offer-field-title m-0">Type de contrat: </p>
                      <p class="offer-field-value m-0"><?= $offers->contractType['label'] ?></p>
                    </div>
                  </div>

                  <div class="mt-4">
                    <p class="offer-field-title m-0">Profil: </p>
                    <div class="offer-field-value">
                      <?= $offers->profil ?>
                    </div>
                  </div>

                  <div class="mt-4">
                    <p class="offer-field-title m-0">Mission: </p>
                    <div class="offer-field-value">
                      <?= $offers->mission ?>
                    </div>
                  </div>

                  <?php
                  if ( ! empty( $offers->otherInformation ) ):
                    ?>
                    <div class="mt-4">
                      <p class="offer-field-title m-0">Autres informations: </p>
                      <div class="offer-field-value">
                        <?= $offers->otherInformation ?>
                      </div>
                    </div>
                  <?php
                  endif;
                  ?>

<!--                  <div class="m-5">-->
<!--                    <p class="uk-text-bold">-->
<!--                      Merci d'envoyer vos dossiers de candidatures ( CV + LM ) à l'adresse :-->
<!--                      recrutement@itjobmada.com-->
<!--                    </p>-->
<!--                  </div>-->

                </div>
              </div>

              <div class="offer-footer mt-3">
                <div class="row pt-5">
                  <div class="col-md-6">

                  </div>
                  <div class="col-md-6">
                    <div>
                      <?= do_action('je_postule'); ?>
                      <div class="float-right ml-3">
                        <a href="<?= get_post_type_archive_link( 'offers' ) ?>" class="float-right">
                          <button class="btn btn-outline-primary btn-fix">
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
          endwhile;
          ?>
        </div>
        <div class="uk-width-1-3@s">
          <!--     Sidebar here ...     -->
        </div>

      </div>
    </div>
  </div>
<?php
get_footer();