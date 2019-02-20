<?php
global $post, $works, $wp, $itJob;

$action = Http\Request::getValue('action', false);
$User = $itJob->services->getUser();
$rp_path = wp_unslash( $_SERVER['REDIRECT_URL'] );
$credit = get_user_meta($User->ID, 'credit', true);
$credit = empty($credit) ? 5 : intval($credit);
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

      if ($credit === 0)
        do_action('add_notice', "Vous n'avez plus de credit. Veuillez acheter de credit avant de continuer", 'pink', false);


      $key = md5(date_i18n('Y-m-d H:i:s'));
      setcookie( 'contact-work', $key, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );

      break;

    case 'sender':
      $key = $_COOKIE['contact-work'];
      $name = Http\Request::getValue('name', '');
      $email = Http\Request::getValue('email', '');
      $message = Http\Request::getValue('message', '');
      $work_id = Http\Request::getValue('work_id', false);
      $form_validate = !$work_id || !$name || !$email || !$message;
      if ($form_validate || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        wp_safe_redirect( add_query_arg(['action' => 'contact', 'errno' => 1, 'key' => $key]) );
        exit;
      }

      if ($credit === 0) {
        wp_safe_redirect( add_query_arg(['action' => 'contact']) );
      }

      $post_url = get_the_permalink($post->ID);
      $message .= "<p class='margin-top: 10px'>Voir l'annonce: <a href='{$post_url}' target='_blank'>{$post->post_title}</a> </p>";

      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      $Works = new \includes\post\Works( (int)$work_id, true );
      $work_contact_mail = $Works->get_mail();
      $work_author = $Works->get_user();
      if ($work_author->ID == $User->ID) {
        do_action('add_notice', "Cette annonce est la vôtre. Vous ne pouvez pas le contacter");
        break;
      }


      $mail->addBCC($Works->author->user_email);
      $mail->addAddress($work_contact_mail);
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
        exit;
      } catch (\PHPMailer\PHPMailer\Exception $e) {
        wp_safe_redirect( add_query_arg(['action' => 'contact', 'errno' => urlencode($e->getMessage())]) );
      }
      break;

    case 'confirmation':
      $key = Http\Request::getValue('key');
      if ($key !== $_COOKIE['contact-work']) {
        wp_safe_redirect( add_query_arg(['action' => 'contact', 'error' => 1]) );
      }
      // Reduire le credit du client
      $credit = $credit ? abs((int) $credit - 1) : 4;
      update_user_meta($User->ID, 'credit', $credit);
      $msg = $credit ? "Il vous reste {$credit} credit(s)": "Il ne vous reste plus de credit.";
      do_action('add_action', $msg, 'info', false);

      setcookie( 'contact-work', ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
      break;


  endswitch;
}

get_header();
wp_enqueue_style('themify-icons');

?>
  <style type="text/css">
    .work-wrap {
      background: #0e76bc;
      position: relative;
      color: white;
      padding: 17px 0;
    }
    .display-5 {
      font-size: 2.2rem;
      font-weight: 300;
      line-height: 1.1;
    }
    .work-content p,
    .work-content a,
    .work-content span,
    .work-content {
      margin-top: 0;
      margin-bottom: 1rem;
      font-size: 1rem;
      font-weight: normal;
      line-height: 1.8;
    }
  </style>
  <script type="text/javascript">
    (function ($) {
      $(document).ready(function () {
        fixWorkWrap();
        function fixWorkWrap() {
          // (x1 - x2) / 2 - x1~pL
          var work_wrap = $('#work-wrap');
          var x1 = $('#uk-container'); // x1
          var x2 = $('#section'); // x2

          var x1_width = x1.innerWidth();
          var x2_width = x2.innerWidth();
          var x1pL = x1.css('padding-left');

          var value = Math.ceil(((parseInt(x1_width) - parseInt(x2_width))/ 2) - parseInt(x1pL));
          work_wrap.css({
            width: parseFloat(x2_width),
            left: value
          });
        }
        $(window).resize(function () {
          fixWorkWrap();
        })

      });
    })(jQuery);
  </script>
  <div id="section" class="uk-section uk-section-transparent pt-0">
    <div id="uk-container" class="uk-container uk-container-medium" style="min-height: 350px;">
      <?php
      while (have_posts()) : the_post();
        if ($works::is_wp_error()) {
          echo $works::is_wp_error();
          continue;
        }
        if (!$works instanceof \includes\post\Works) continue;
        // Count view
        $author = $works->get_user();
        ?>
      <?php if (!$action): ?>
        <div id="work-wrap" class="work-wrap">
          <div class="banner banner-gradient banner-pb-90 ml-4">
            <div class="container">
              <h1 class="display-5 mb-2 font-bold">
                <?= $works->title ?>
              </h1>
              <div class="banner-subtitle">
                <span class="mr-4">Ref: <b><?= $works->reference ?></b></span>
                <span>Ville: <b><?= $works->town->name ?></b></span>
              </div>
            </div>
          </div>
        </div>

        <div uk-grid>
          <div class="uk-width-2-3@m">
            <!--     Vérifier s'il y a une postulation en cours     -->
            <?php
              do_action('get_notice');
              $works->increment_view();
            ?>

            <!--          Content here ... -->
            <div class="pt-4 pl-4 work-content">
              <h5 class="font-bold">Description</h5>
              <?= $works->description ?>
            </div>
            <div class="ibox-body pl-4 mt-4">
              <table class="table">
                <tbody>
                  <?php if ($works->price && $works->price !== 0): ?>
                  <tr>
                    <td>Budget indicatif</td>
                    <td class="font-bold"><?=  $works->price ?> MGA</td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                    <td>Publié le</td>
                    <td class="font-bold"><?= $works->date_publication_format ?></td>
                  </tr>
                  <?php if ($works->region): ?>
                    <tr>
                      <td>Région</td>
                      <td class="font-bold"><?=  $works->region->name ?> </td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <td>Début du projet</td>
                    <td class="font-bold">Tout de suite</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="ibox-footer mt-5">
              <div class="text-right">
                <a href="<?= get_post_type_archive_link("works") ?>" class="btn btn-blue">Voir les autres annonces</a>
              </div>
            </div>

          </div>
          <div class="uk-width-1-3@m">
            <div class="ibox mt-4">
              <div class="ibox-body">
                <a href="?action=contact" class="btn btn-info btn-fix d-block">
                  <span class="btn-icon"><i class="la la-envelope-o"></i>Evoyer un message</span>
                </a>
              </div>

            </div>
            <!--     Sidebar here ...     -->
            <?php
            if (is_active_sidebar('single-work-sidebar')) {
              dynamic_sidebar('single-work-sidebar');
            }
            ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($action === 'contact') : ?>
        <div class="mt-4" uk-grid>
          <div class="uk-width-1-2@s">
            <div class="ibox">
              <div class="ibox-body">
                <div>
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
                      <input type="hidden" name="work_id" value="<?= $works->ID ?>">
                      <button class="btn btn-blue mr-2"  type="submit">Envoyer votre message</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <div class="uk-width-1-2@s">
            <div class="ibox">
              <div class="ibox-body">
                <h6 class="font-bold mb-4 font-15">Résumé de l’annonce</h6>
                <h1 class="display-5 font-light">
                  <?= $works->title ?>
                </h1>

                <div class="pt-2 work-content">
                  <h5 class="font-bold">Description</h5>
                  <div style="font-size: 12px !important;"><?= $works->description ?></div>
                </div>

                <table class="table">
                  <tbody>
                  <?php if ($works->price && $works->price !== 0): ?>
                    <tr>
                      <td>Budget indicatif</td>
                      <td class="font-bold"><?=  $works->price ?> MGA</td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <td>Publié le</td>
                    <td class="font-bold"><?= $works->date_publication_format ?></td>
                  </tr>
                  <?php if ($works->region): ?>
                    <tr>
                      <td>Région</td>
                      <td class="font-bold"><?=  $works->region->name ?> </td>
                    </tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($action === 'confirmation') : $works->add_contact_sender($User->ID); ?>
        <div class="ibox mt-lg-5">
          <div class="ibox-body">
            <div class="page-heading mb-4">
              <h4 class="page-title">MESSAGE ENVOYER AVEC SUCCES</h4>
              <h6>Vous recevrez un message dans votre boîte au lettre à cette adresse: <b><?= $User->user_email ?></b>.</h6>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($action === 'sender'):
              do_action('get_notice');
            endif;
      ?>

      <?php

      endwhile;
      ?>

    </div>
  </div>
<?php
get_footer();