<?php

namespace includes\mailing;

use includes\post\Company;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Mailing {
  public function __construct() {
    add_action( 'init', [ &$this, 'onInit' ] );

  }

  public function onInit() {
    // Uses: do_action() Calls 'user_register' hook when creating a new user giving the user's ID
    //add_action( 'user_register', [ &$this, 'register_user' ], 10, 1 );
    // Featured: Cree une action et appeler cette action apres l'enregistrement
    add_action('register_user_company', [&$this, 'register_user'], 10, 1);
  }

  public function register_user( $user_id ) {
    global $Engine;
    $User = new \WP_User( $user_id );
    if ( in_array( 'company', $User->roles ) ) {
      // Création d'un compte entreprise reussi
      $Company = Company::get_company_by( $User->ID );
      $to      = $User->user_email;
      if ( empty( $to ) ) {
        return "Adresse e-mail de l'administrateur abscent";
      }
      $subject   = "Confirmation de l’enregistrement de « {$Company->title} »";
      $headers   = [];
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = 'From: ItJobMada <no-reply@itjobmada.com';
      $content   = '';
      try {
        $con_query = add_query_arg( [
          'action' => "rp",
          "token"  => $User->user_pass,
          "login"  => $User->user_email
        ], home_url( "/connexion/company/" ) );
        $content   .= $Engine->render( '@MAIL/confirm-register-company.html.twig', [
          'company'       => $Company,
          'connexion_url' => $con_query,
          'home_url'      => home_url("/")
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        $content = $e->getRawMessage();
      }
      $sender = wp_mail( $to, $subject, $content, $headers );
      if ( $sender ) {
        // Mail envoyer avec success
        return $user_id;
      } else {
        // Erreur d'envoie
        return false;
      }
    } // .company

    return $user_id;
  }

}

new Mailing();