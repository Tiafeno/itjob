<?php

// Envoyer 1 fois par mois
add_action('action_scheduler_run_once_month', 'run_once_month');
function run_once_month() {
  global $Engine;
  $Model = new cronModel();

  // notification-02
  $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

  /**
   * ***********************************************************************************
   *  Savez-vous que de nombreux candidats se sont inscrit sur le site Itjobmada.com ?
   * ***********************************************************************************
   */

  $companies = $Model->getCompanyNoSaveOfferLongTime();
  if ( ! empty($companies) ) :
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom("no-reply-notification@itjobmada.com", "Equipe ITJob");
    $mail->addReplyTo('commercial@itjobmada.com', 'Responsable commercial');

    foreach ($companies as $Company) {
      $sender = $Company->author->user_email;
      $mail->addBCC($sender);
    }

    // Envoyer une mail de notification au entreprise
    $msg = '';
    try {
      $espace_client = get_the_permalink( (int) ESPACE_CLIENT_PAGE );
      $add_offer = get_the_permalink( (int) ADD_OFFER_PAGE );
      $msg .= $Engine->render('@MAIL/newsletters/notification-02.html', [
        'Year' => Date('Y'),
        'unsubscribe' => "{$espace_client}#!/manager/profil/settings", // Espace client
        'url' => $add_offer
      ]);

      $mail->addAddress('no-reply@itjobmada.com', 'Equipe ITJob');
      $mail->Body = $msg;
      $mail->Subject = "Savez-vous que de nombreux candidats se sont inscrit sur le site Itjobmada.com ?";
      // Envoyer le mail
      $mail->send();

    } catch (Twig_Error_Loader $e) {
    } catch (Twig_Error_Runtime $e) {
    } catch (Twig_Error_Syntax $e) {
    } catch (\PHPMailer\PHPMailer\Exception $e) {

    }
  endif;
}

// Envoyer 2 fois par semaine
add_action('action_scheduler_run_twice_week', 'run_twice_week');
function run_twice_week() {
  global $Engine;
  $Model = new cronModel();

  /**
   * ***************************************************************************************
   *  Savez-vous que vous pouvez sélectionné des candidats pour sur le site Itjobmada.com ?
   * ***************************************************************************************
   */
  $companies = $Model->getCompanyNoOffers();
  if ( ! empty($companies) ):
    // notification-04
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom("no-reply-notification@itjobmada.com", "Equipe ITJob");
    $mail->addReplyTo('commercial@itjobmada.com', 'Responsable commercial');

    foreach ($companies as $Company) {
      $sender = $Company->author->user_email;
      $mail->addBCC($sender);
    }

    // Envoyer une mail de notification au entreprise
    $msg = '';
    try {
      $espace_client = get_the_permalink( (int) ESPACE_CLIENT_PAGE );
      $add_offer = get_the_permalink( (int) ADD_OFFER_PAGE );
      $msg .= $Engine->render('@MAIL/newsletters/notification-04.html', [
        'Year' => Date('Y'),
        'unsubscribe' => "{$espace_client}#!/manager/profil/settings", // Espace client
        'url' => $add_offer
      ]);

      $mail->addAddress('no-reply@itjobmada.com', 'Equipe ITJob');
      $mail->Body = $msg;
      $mail->Subject = "Savez-vous que vous pouvez sélectionné des candidats pour sur le site Itjobmada.com ?";
      // Envoyer le mail
      $mail->send();

    } catch (Twig_Error_Loader $e) {
    } catch (Twig_Error_Runtime $e) {
    } catch (Twig_Error_Syntax $e) {
    } catch (\PHPMailer\PHPMailer\Exception $e) {

    }

    unset($mail);
  endif;
}

// Envoyer une (1) fois par semaine
add_action('action_scheduler_run_week', 'run_week');
function run_week() { // run at 06h30
  global $Engine;
  $Model = new cronModel();

  /**
   * **********************************************
   *  Ajouter une offre sur le site Itjobmada.com
   * *********************************************
   */
  $companies = $Model->getCompanyNoOffers();
  if ( ! empty($companies) ):
    // notification-01
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom("no-reply-notification@itjobmada.com", "Equipe ITJob");
    $mail->addReplyTo('commercial@itjobmada.com', 'Responsable commercial');

    foreach ($companies as $Company) {
      if ( ! $Company instanceof \includes\post\Company) continue;
      $sender = $Company->author->user_email;
      $mail->addBCC($sender);
    }

    // Envoyer une mail de notification au entreprise
    try {
      $espace_client = get_the_permalink( (int) ESPACE_CLIENT_PAGE );
      $add_offer = get_the_permalink( (int) ADD_OFFER_PAGE );
      $msg = $Engine->render('@MAIL/newsletters/notification-01.html', [
        'Year' => Date('Y'),
        'unsubscribe' => "{$espace_client}#!/manager/profil/settings", // Espace client
        'url' => $add_offer
      ]);

      $mail->addAddress('no-reply@itjobmada.com', 'Equipe ITJob');
      $mail->Body = $msg;
      $mail->Subject = "Ajouter une offre sur le site Itjobmada.com";
      // Envoyer le mail
      $mail->send();

    } catch (Twig_Error_Loader $e) {
    } catch (Twig_Error_Runtime $e) {
    } catch (Twig_Error_Syntax $e) {
    } catch (\PHPMailer\PHPMailer\Exception $e) {

    }
  endif;
}
?>