<?php

/**
 * Cette fonction permet d'envoyer au candidat qui n'on pas encore postuler à une offre
 * @param $candidates
 * @return bool
 * @throws \PHPMailer\PHPMailer\Exception
 */
function send_not_applied_candidate( $candidates ) {
  $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
  $mail->addAddress('no-reply@itjobmada.com', 'ITJOB Team');
  $mail->addReplyTo('no-reply@itjobmada.com', 'ITJOB Team');
  foreach ($candidates as $candidate) {
    $author = $candidate->privateInformations->author;
    $mail->addBCC($author->user_email);
  }
  $subject = "Cela fait un moment que vous êtes inscrit sur Itjobmada";
  $archive_offers_link = get_post_type_archive_link("offers");
  $content = '';
  $content .= "Bonjour, <br><br>
        Cela fait un moment que vous êtes inscrit sur Itjobmada et nous constatons que " .
    "vous n’avez postulé à aucune offre d’emploi. Si vous recherchez toujours activement un emploi nous vous invitons " .
    "à postuler aux offres qui vous correspondent. Cela est gratuit <br> Nous vous souhaitons une bonne journée <br><br>";
  $content .= "<a href='{$archive_offers_link}' style='color: white;background-color: #1733d4; padding: 12px;font-weight: bold;' " .
    "target='_blank'>Voir les offres sur ITJob</a> <br><br>";

  $content .= "Tompoko, <br><br>
    Efa andro maromaro izay ianao no nisoratra anarana tao amin’ny tranokala itjobmada, nefa hitanay fa tsy mandray " .
    "anjara amin’ny tolotr’asa mihintsy. <br>Raha mbola eo ampitadiavana asa ianao, dia manasa anao izahay handray anjara " .
    "amin’ireo tolotr ‘asa izay mety mifandraika aminao. Maimaim-poana izany. <br> Miraray tontolo andro mahafinaritra ho anao <br><br>";
  $content .= "<a href='{$archive_offers_link}' style='color: white;background-color: #1733d4; padding: 12px;font-weight: bold;' " .
    "target='_blank'>Hijery ny tolotr'asa ao aminy ITJob</a> <br><br><br>";

  $mail->CharSet = 'UTF-8';
  $mail->isHTML(true);
  $mail->setFrom("no-reply-notification@itjobmada.com", "Équipe ITJob");
  $mail->Body = $content;
  $mail->Subject = $subject;

  try {
    // Envoyer le mail
    $mail->send();
  } catch (\PHPMailer\PHPMailer\Exception $e) {
    return false;
  }
}

add_action('action_scheduler_run_week', function() {
  $model = new cronModel();
  send_not_applied_candidate($model->getCandidatsNotApplied());

});

