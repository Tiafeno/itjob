<?php

namespace includes\mailing;

use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use Underscore\Types\Arrays;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Mailing {
  public $espace_client;
  private $no_reply_email = "no-reply@itjobmada.com>";

  public function __construct() {
    add_action( 'init', [ &$this, 'onInit' ] );

  }

  public function onInit() {
    $oc_id               = jobServices::page_exists( 'Espace client' );
    $this->espace_client = get_the_permalink( $oc_id );

    // Uses: do_action() Calls 'user_register' hook when creating a new user giving the user's ID
    //add_action( 'user_register', [ &$this, 'register_user' ], 10, 1 );
    // Featured: Cree une action et appeler cette action apres l'enregistrement
    add_action( 'register_user_company', [ &$this, 'register_user_company' ], 10, 1 );
    add_action( 'register_user_particular', [ &$this, 'register_user_particular' ], 10, 1 );
    add_action( 'submit_particular_cv', [ &$this, 'submit_particular_cv' ], 10, 1 );
    add_action( 'forgot_my_password', [ &$this, 'forgot_my_password' ], 10, 2 );
  }

  /**
   * Envoyer un mail au client pour la confirmation d'inscription
   *
   * @param int $user_id
   *
   * @return bool
   */
  public function register_user_company( $user_id ) {
    global $Engine;
    $User = new \WP_User( $user_id );
    if ( in_array( 'company', $User->roles ) ) {
      // Création d'un compte entreprise reussi
      $Company   = Company::get_company_by( $User->ID );
      $to        = $User->user_email;
      $subject   = "Confirmation de l’enregistrement de « {$Company->title} »";
      $headers   = [];
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
      $content   = '';
      try {
        $con_query = add_query_arg( [
          'action' => "rp",
          "token"  => $User->user_pass,
          "login"  => $User->user_email,
          "redir"  => $this->espace_client
        ], home_url( "/connexion/company/" ) );
        $content   .= $Engine->render( '@MAIL/confirm-register-company.html.twig', [
          'company'       => $Company,
          'connexion_url' => $con_query,
          'home_url'      => home_url( "/" )
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        $content .= $e->getRawMessage();
      }
      $sender = wp_mail( $to, $subject, $content, $headers );
      if ( $sender ) {
        // Mail envoyer avec success
        return $user_id;
      } else {
        // Erreur d'envoie
        return $user_id;
      }
    } // .company

    return $user_id;
  }

  /**
   * Envoyer un mail au utilisateur particulier qui vient d'inscrire sur le site
   *
   * @param int $user_id
   *
   * @return bool
   */
  public function register_user_particular( $user_id ) {
    global $Engine;
    $User = new \WP_User( $user_id );
    if ( in_array( 'candidate', $User->roles ) ) {
      // Création d'un compte particular reussi
      $to        = $User->user_email;
      $subject   = "Confirmation de l'enregistrement de votre compte sur ItJobMada";
      $headers   = [];
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
      $content   = '';
      try {
        $reset_key = get_password_reset_key( $User );
        $con_query = add_query_arg( [
          'action' => "validation",
          "key"    => $reset_key,
          "login"  => $User->user_email,
          "redir"  => $this->espace_client
        ], home_url( "/connexion/candidate/" ) );
        $content   .= $Engine->render( '@MAIL/confirm-register-particular.html.twig', [
          'user_data'     => get_userdata( $user_id ),
          'connexion_url' => $con_query,
          'home_url'      => home_url( "/" )
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
    }
  }

  /**
   * Envoyer un email quand un CV viens d'être ajouter
   *
   * @param int $candidate_id - Post candidate id
   *
   * @return bool
   */
  public function submit_particular_cv( $candidate_id ) {
    global $Engine;
    if ( ! is_int( $candidate_id ) ) {
      return false;
    }
    $Candidate = new Candidate( $candidate_id );
    $Candidate->__client_premium_access();
    $privateInfo = $Candidate->privateInformations;
    $subject     = "Confirmation de l’enregistrement de votre CV sur ItJobMada";
    $headers     = [];
    $headers[]   = 'Content-Type: text/html; charset=UTF-8';
    $headers[]   = "From: ItJobMada <{$this->no_reply_email}>";
    $content     = '';
    try {
      $oc_id   = jobServices::page_exists( 'Espace client' );
      $oc_url  = get_the_permalink( $oc_id );
      $content .= $Engine->render( '@MAIL/confirm-added-cv.html.twig', [
        'user_data'          => [
          'title'     => $Candidate->title,
          'full_name' => $privateInfo->firstname . ' ' . $privateInfo->lastname
        ],
        'oc_url'             => $oc_url,
        'home_url'           => home_url( "/" ),
        'archive_offers_url' => get_post_type_archive_link( 'offers' )
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content = $e->getRawMessage();
    }

    $sender = wp_mail( $privateInfo->author->user_email, $subject, $content, $headers );
    if ( $sender ) {
      // Mail envoyer avec success
      return true;
    } else {
      // Erreur d'envoie
      return false;
    }
  }

  /**
   * Cree une notification
   *
   * @param int $user_id - L'identification du client
   *
   * @return bool|mixed
   */
  public function notif_admin_new_user( $user_id ) {
    global $Engine;
    $error = true;
    if ( ! is_int( $user_id ) ) {
      return false;
    }
    // $admin_emails - Contient les adresses email de l'admin et les moderateurs
    $admin_emails = [];
    $User         = get_user_by( 'ID', $user_id );
    if ( in_array( 'company', $User->roles ) ) {
      // L'utilisateur est une entreprise
      $subject = "Inscription d'une nouvelle entreprise - ItJobMada";
      $error   = false;
    }
    if ( in_array( 'candidate', $User->roles ) ) {
      // L'utilisateur est un candidat
      $subject = "Inscription d'un nouveau candidat - ItJobMada";
      $error   = false;
    }
    if ( $error ) {
      return false;
    }
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
    $content   = '';

    //TODO: Envoyer le mail a l'administrateur ici...


  }

  /**
   * Notifier les entreprises si le candidate correspont a ce qu'ils recherchent
   *
   * @param int $candidate_id
   *
   * @return bool
   */
  public function alert_for_new_candidate( $candidate_id ) {
    if ( ! is_int( $candidate_id ) ) {
      return false;
    }
    try {
      $Candidate = new Candidate( $candidate_id );
    } catch (\WP_Error $e) {
      echo $e->getMessage();
    }

    if ( ! is_null( $Candidate->branch_activity ) ) {
      $args                        = [
        "post_type"      => "company",
        "post_status"    => "publish",
        "posts_per_page" => - 1,
        "tax_query"      => [
          [
            'taxonomy' => 'branch_activity',
            'field'    => 'term_id',
            'terms'    => $Candidate->branch_activity->term_id
          ]
        ]
      ];
      $postCompany                 = get_posts( $args );
      $jobs = Arrays::each($Candidate->jobSought, function ($job) {
        return $job->name;
      });
      $emploi_rechercher_candidate = implode( ' ', $jobs );
      $emploi_rechercher_candidate = strtolower( $emploi_rechercher_candidate );
      $see_alerts                  = [];
      foreach ( $postCompany as $pts ) {
        $company       = new Company( $pts->ID );
        $company_alert = get_field( 'itjob_company_alerts', $company->getId() );
        $alerts        = explode( ',', $company_alert );
        $alert_matches = [];
        foreach ( $alerts as $alert ) {
          // Si on trouve une espace dans l'alert, on crée un tableau
          if ( strpos( trim( $alert ), ' ' ) ) {
            $alert = explode( ' ', $alert );
          }
          $pattern = '/';
          // TODO: Il est préférable que le champ d'alert ne contient pas d'espace
          if ( is_array( $alert ) ) {
            Arrays::each( $alert, function ( $el, $index ) use ( &$pattern, $alert ) {
              $el      = strtolower( $el );
              $pattern .= "({$el})";
              if (count($alert) - 1 !== $index) {
                $pattern .= "*";
              }
              return $el;
            } );
          } else {
            $alert   = strtolower( $alert );
            $pattern .= "({$alert})";
          }
          $pattern .= '/';
          $matches = [];
          preg_match( $pattern, $emploi_rechercher_candidate, $matches, PREG_OFFSET_CAPTURE );
          if ( $matches ) {
            $matches = Arrays::filter($matches, function ($matche) {
              return !empty($matche[0]);
            });
            if ( ! empty($matches))
              $alert = is_array($alert) ? implode(' ', $alert) : $alert;
              $alert_matches[] = (object) [
                'job_soughts' => $emploi_rechercher_candidate,
                'pattern' => $pattern,
                'alert' => $alert,
                'matches' => $matches
              ];
          }

        } // .each alerts

        $see_alerts[] = (object) [
          'company' => [
            'ID'    => $company->getId(),
            'email' => $company->author->user_email
          ],
          'alerts' => $alert_matches
        ];

      } // .each company

      // Envoyer les emails
      print_r($see_alerts);
    }
  }

  public function forgot_my_password( $email, $key ) {
    global $Engine;
    $to        = $email;
    $User      = get_user_by( 'email', $to );
    $subject   = "Mot de passe oublié? - ItJobMada";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
    $content   = '';
    try {
      $forgot_password_page_id = jobServices::page_exists( 'Forgot password' );
      $content                 .= $Engine->render( '@MAIL/forgot-password.html.twig', [
        'forgot_link' => get_the_permalink( $forgot_password_page_id ) . "?key={$key}&account={$User->user_login}&action=resetpass",
        'home_url'    => home_url( "/" )
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content .= $e->getRawMessage();
    }
    $sender = wp_mail( $to, $subject, $content, $headers );
    if ( $sender ) {
      // Mail envoyer avec success
      wp_send_json_success( [
        "msg" => "Merci de vérifier que vous avez reçu un e-mail avec un lien de récupération.",
        'key' => $key
      ] );
    } else {
      // Erreur d'envoie
      wp_send_json_error( [
        "msg"   => "Le message n’a pas pu être envoyé. " .
                   "Cause possible : votre hébergeur a peut-être désactivé la fonction mail().",
        "key"   => $key,
        "login" => $User->user_login
      ] );
    }
  }
}

return new Mailing();