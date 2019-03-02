<?php
global $annonce, $post, $wp, $itJob;

$action = Http\Request::getValue('action', false);
$User = $itJob->services->getUser();
$rp_path = wp_unslash( $_SERVER['REDIRECT_URL'] );
//$credit = 0;
//if ( ! is_wp_error($User)) {
//  $wallet = \includes\post\Wallet::getInstance($User->ID, 'user_id', true);
//  if (is_wp_error($wallet)) {
//    add_action('add_notice', $wallet->get_error_message());
//  } else {
//    $credit = $wallet->credit;
//  }
//}

if ($action) {
  switch ($action):
    case 'contact':
      if (!is_user_logged_in()) {
        $login_url = home_url('/connexion/candidate');
        $url = add_query_arg(['redir' => get_the_permalink($post->ID) . '?action=contact'], $login_url);
        wp_safe_redirect($url);
        exit;
      }

      $errno = Http\Request::getValue('errno', false);
      if ($errno) {
        $errno = $errno == 1 ? "Veuillez remplire correctement le formulaire" : $errno;
        do_action('add_notice', $errno, "pink", false);
      }

//      if ($credit === 0)
//        do_action('add_notice', "Vous n'avez plus de credit. Veuillez acheter de credit avant de continuer", 'pink', false);

      $key = md5(date_i18n('Y-m-d H:i:s'));
      setcookie( 'contact-annonce', $key, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );

      break;

    case 'sender':
      $key = $_COOKIE['contact-annonce'];
      $name = Http\Request::getValue('name', '');
      $email = Http\Request::getValue('email', '');
      $message = Http\Request::getValue('message', '');
      $annonce_id = Http\Request::getValue('annonce_id', false);
      $form_validate = !$annonce_id || !$name || !$email || !$message;
      if ($form_validate || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        wp_safe_redirect( add_query_arg(['action' => 'contact', 'errno' => 1, 'key' => $key]) );
        exit;
      }

//      if ($credit === 0) {
//        wp_safe_redirect( add_query_arg(['action' => 'contact']) );
//        exit;
//      }

      $post_url = get_the_permalink($post->ID);
      $message .= "<p class='margin-top: 10px'>Voir l'annonce: <a href='{$post_url}' target='_blank'>{$post->post_title}</a> </p>";

      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      $Annonce = new \includes\post\Annonce( (int)$annonce_id, true );
      $annonce_contact_mail = $Annonce->get_mail();
      $annonce_author = $Annonce->get_author();
      if ($annonce_author->ID == $User->ID) {
        do_action('add_notice', "Cette annonce est la vôtre. Vous ne pouvez pas le contacter");
        break;
      }

      $mail->addBCC($annonce_author->user_email);
      $mail->addAddress($annonce_contact_mail);
      $mail->addReplyTo($User->user_email, $name);
      $mail->CharSet = 'UTF-8';
      $mail->isHTML(true);
      $mail->setFrom($User->user_email, $name);
      $mail->Body = $message;
      $mail->Subject = $post->post_title;

      try {
        // Envoyer le mail
        $mail->send();
        wp_safe_redirect( add_query_arg(['action' => 'confirmation', 'key' => $key]) );
      } catch (\PHPMailer\PHPMailer\Exception $e) {
        wp_safe_redirect( add_query_arg(['action' => 'contact', 'errno' => urlencode($e->getMessage())]) );
      }
      exit;
      break;

    case 'confirmation':
      $key = Http\Request::getValue('key');
      if ($key !== $_COOKIE['contact-work']) {
        wp_safe_redirect( add_query_arg(['action' => 'contact', 'error' => 1]) );
        exit;
      }
      // Reduire le credit du client
//      $credit = $credit - 1;
//      $credit = $credit <= 0 ? 0 : $credit;
//      if (!$credit) {
//        do_action('add_action', "Il ne vous reste plus de credit, Veuillez acheter de credit.", 'info', false);
//        setcookie( 'contact-annonce', ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
//        continue;
//      }

      $Annonce = new \includes\post\Annonce( (int)$post->ID, true );
      if ( ! in_array($User->ID, $Annonce->contact_sender)) {
//        $wallet->update_wallet($credit);

//        $msg = "Il vous reste {$credit} credit(s)";
//        do_action('add_action', $msg, 'info', false);
      }

      setcookie( 'contact-annonce', ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
      break;

  endswitch;
}

get_header();
wp_enqueue_style('themify-icons');
wp_enqueue_style('camroll-slider');
wp_enqueue_style('sweetalert');
wp_enqueue_script('camroll-slider');
wp_enqueue_script('sweetalert');

/**
 * Change the Yoast meta description for Android devices on the front/home page.
 */

?>
  <style type="text/css">
    #slider {
      width: 100%;
      height: 404px;
      color: white;
    }
    #slider .crs-screen-item {
      background-size: contain;
      background-repeat: no-repeat;
      background-color: #FFFFFF;
      background-position: bottom;
    }
    #slider .crs-bar-roll-current {
      border: 4px solid #fff;
      top: 7px;
    }
    .container {
      max-width: 760px;
      width: 100%;
      padding: 0 20px;
    }
    .price {
      color: #f56b2a;
      font-weight: 600;
    }
    .crs-screen:before {
      content: "";
      display: block;
      bottom: 0;
      background: -webkit-gradient(linear,left top,left bottom,from(transparent),to(rgba(0,0,0,.3)));
      background: -webkit-linear-gradient(top,transparent,rgba(0,0,0,.3));
      background: linear-gradient(180deg,transparent 0,rgba(0,0,0,.3));
      height: 64px;
      width: 100%;
      position: absolute;
      z-index: 9;
    }
    .crs-bar {
      z-index: 10;
    }
    @media (max-width: 640px) {
      #slider .crs-bar-roll-current {
        width: 38px;
        height: 38px;
      }
      #slider .crs-bar-roll-item {
        width: 30px;
        height: 30px;
      }
    }

  </style>
<script>
  (function ($) {
    $(document).ready(function () {
      $("#slider").each(function (i, el) {
        $(el).camRollSlider();
      });

      $('.view-phone-number').on('click', function (ev) {
        $.ajax({
          url: `<?= admin_url('admin-ajax.php') ?>`,
          cache: true,
          method: "GET",
          data: { action : 'request_phone_number', ad_id : <?= $post->ID ?> },
          dataType: "json"
        })
          .done(function (resp) {
            var numberPhone = resp.data;
            swal(numberPhone, 'Vous pouvez me contacter par téléphone avec le numéro ci-dessus');
          })
          .fail(function() {
            swal("Désolé", "Vous n'êtes pas connecter", "warning");
          })
          .always(function () {});
      });

      $('.price').each(function (index, el) {
        var priceValue = $(el).text().trim();
        $(el).text(new Intl.NumberFormat('de-DE', {
          style: "currency",
          minimumFractionDigits: 0,
          currency: 'MGA'
        }).format(priceValue));
      });

    });
  })(jQuery)
</script>
  <div class="uk-section uk-section-transparent">
    <div class="uk-container uk-container-medium">
      <div uk-grid>
        <div class="uk-width-2-3@s">
          <!--          Content here ... -->
          <?php
          while (have_posts()) : the_post();
            if ($annonce::is_wp_error()) {
              echo $annonce::is_wp_error();
              continue;
            }
            if (!$annonce instanceof \includes\post\Annonce) continue;
            $author = $annonce->get_author();
            $name = 'Inconnue';
            if (in_array('candidate', $User->roles)) {
              $Candidate = \includes\post\Candidate::get_candidate_by($User->ID);
              $name = $Candidate->getLastName();
            }

            if (in_array('company', $User->roles)) {
              $Company = \includes\post\Company::get_company_by($User->ID);
              $name = $Company->title;
            }
            ?>
          <div class="ibox">
            <?php if (!$action): ?>
            <div class="ibox-body">
              <?php do_action('get_notice'); ?>
              <div class="container">
                <?php
                $gallery = [];
                $thumbnail_id = get_post_thumbnail_id($annonce->ID);
                $gallery['url'] = get_the_post_thumbnail_url($annonce->ID, 'full');
                $gallery['sizes'] = [];
                $gallery['sizes']['thumbnail'] = get_the_post_thumbnail_url($annonce->ID, 'thumbnail');
                array_push($annonce->gallery, $gallery);
                ?>
                <?php if (!empty($annonce->gallery)): ?>
                <div id="slider" class="crs-wrap">
                  <div class="crs-screen">
                    <div class="crs-screen-roll">

                      <?php foreach ($annonce->gallery as $gallery): ?>
                      <div class="crs-screen-item" style="background-image: url('<?= $gallery['url'] ?>')">
                        <div class="crs-screen-item-content"></div>
                      </div>
                      <?php endforeach; ?>

                    </div>
                  </div>
                  <div class="crs-bar">
                    <div class="crs-bar-roll-current"></div>
                    <div class="crs-bar-roll-wrap">
                      <div class="crs-bar-roll">
                        <?php foreach ($annonce->gallery as $gallery): ?>
                        <div class="crs-bar-roll-item" style="background-image: url('<?= $gallery['sizes']['thumbnail'] ?>')"></div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <div class="mt-3"></div>
                <h2 class="page-title font-strong font-19"><?= $annonce->title ?></h2>

                <?php if ($annonce->price && !empty($annonce->price)) : ?>
                  <div class="price font-15"><span class="price"><?= $annonce->price ?></span></div>
                <?php endif; ?>

                <div>Déposer le <?= $annonce->date_publication_format ?> par <b><?= $name ?></b></div>
                <hr class="mt-5">
                <div>
                  <div class="row mt-4">
                    <div class="col-sm-4">
                      <h6 class="uppercase font-strong">Région</h6>
                      <p><?= $annonce->region->name ?></p>
                    </div>
                    <div class="col-sm-4">
                      <h6 class="uppercase font-strong">Adresse</h6>
                      <p><?= $annonce->address ?></p>
                    </div>
                    <?php if ($annonce->town): ?>
                    <div class="col-sm-4">
                      <h6 class="uppercase font-strong">Ville</h6>
                      <p><?= $annonce->town->name ?></p>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <hr>
                <div class="mt-5">
                  <h5 class="font-strong font-15">Description</h5>
                  <div>
                    <?= $annonce->description ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endif ?>

            <?php if ($action === 'contact'): ?>
            <div class="ibox-body">
              <form name="workcontact" method="post" action="?action=sender&key=<?= $key ?>">
                <div class="ibox-body pt-0">
                  <?= do_action('get_notice', ''); ?>
                  <h6 class="mb-4">Envoyer un message à « <?= $author->user_login ?> »</h6>
                  <div class="alert alert-warning">
                    <b>Attention</b> : Méfiez-vous des propositions trop alléchantes et des prix trop bas.
                    Assurez-vous de ne pas être victime d’une tentative d’escroquerie.
                  </div>
                  <div class="form-group mb-4">
                    <label>Votre nom</label>
                    <input class="form-control" name="name" type="text" placeholder="" required>
                  </div>
                  <div class="form-group mb-4">
                    <label>Votre email</label>
                    <input class="form-control" name="email" type="email" placeholder="" required>
                  </div>
                  <div class="form-group mb-4">
                    <label>Votre message</label>
                    <textarea class="form-control" style="border: 1px solid black;" name="message" rows="3" required></textarea>
                  </div>
                </div>
                <div class="ibox-footer">
                  <input type="hidden" name="annonce_id" value="<?= $annonce->ID ?>">
                  <button class="btn btn-blue mr-2"  type="submit">Envoyer votre message</button>
                </div>
              </form>
            </div>
            <?php endif; ?>

            <?php if ($action === 'confirmation') : $annonce->add_contact_sender($User->ID); ?>
            <div class="ibox-body">
              <div class="page-heading mb-4">
                <h4 class="page-title">MESSAGE ENVOYER AVEC SUCCES</h4>
                <h6>Vous recevrez un message dans votre boîte au lettre à cette adresse: <b><?= $User->user_email ?></b>.</h6>
              </div>
            </div>
            <?php endif; ?>

            <div class="ibox-footer">
              <div class="text-right">
                <a href="<?= get_post_type_archive_link('annonce') ?>" class="btn btn-outline-blue">Retour</a>
              </div>
            </div>
          </div>
          <?php
          endwhile;
        ?>
        </div>
        <div class="uk-width-1-3@s">
          <div class="ibox">

            <?php if (!$action): ?>
              <div class="ibox-body">
                <button type="button" class="view-phone-number btn btn-danger btn-fix btn-block">
                  <span class="btn-icon"><i class="la la-phone"></i>
                    Voir ses coordonées
                  </span>
                </button>
                <a href="?action=contact" data-annonce-id="<?= $annonce->ID ?>" class="btn btn-blue btn-fix d-block mt-2" id="view-number-phone">
                  <span class="btn-icon"><i class="la la-envelope-o"></i>Evoyer un message</span>
                </a>
              </div>
            <?php endif; ?>

            <?php if ($action === 'contact'): ?>
              <div class="ibox-body">
                <h6 class="font-bold mb-4 font-15">Résumé de l’annonce</h6>
                <h1 class="display-5 font-18 font-light">
                  <?= $annonce->title ?>
                </h1>
                <div class="pt-2 work-content">
                  <h5 class="font-bold font-15">Description</h5>
                  <div style="font-size: 12px !important;"><?= $annonce->description ?></div>
                </div>
                <table class="table">
                  <tbody>
                  <?php if ($annonce->price && $annonce->price !== 0): ?>
                    <tr>
                      <td>Budget indicatif</td>
                      <td class="font-bold"><span class="price"><?=  $annonce->price ?></span></td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <td>Publié le</td>
                    <td class="font-bold"><?= $annonce->date_publication_format ?></td>
                  </tr>
                  <?php if ($annonce->region): ?>
                    <tr>
                      <td>Région</td>
                      <td class="font-bold"><?=  $annonce->region->name ?> </td>
                    </tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

          </div>
          <!--     Sidebar here ...     -->
          <?php
          if (is_active_sidebar('single-annonce-sidebar')) { dynamic_sidebar('single-annonce-sidebar'); }
          ?>
        </div>

      </div>
    </div>
  </div>
<?php
get_footer();