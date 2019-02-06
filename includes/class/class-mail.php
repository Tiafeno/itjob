<?php

namespace includes\mailing;

use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Formation;
use includes\post\Offers;
use Underscore\Types\Arrays;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Mailing {
  public $espace_client;
  public $logo;
  private $no_reply_email = "no-reply@itjobmada.com>";
  private $no_reply_notification_email = "no-reply-notification@itjobmada.com";
  private $dashboard_url = "https://admin.itjobmada.com";

  public function __construct() {
    add_action( 'init', [ &$this, 'onInit' ] );
  }

  public function onInit() {
    $oc_id          = jobServices::page_exists( 'Espace client' );
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
    $this->espace_client = get_the_permalink( $oc_id );
    $this->logo = $logo;

    // On active ou désactive l'envoie des mails
    $option_mailing = get_field('save_post_mailing', 'option');
    if ($option_mailing) return true;

    // Uses: do_action() Calls 'user_register' hook when creating a new user giving the user's ID
    //add_action( 'user_register', [ &$this, 'register_user' ], 10, 1 );
    // Featured: Cree une action et appeler cette action apres l'enregistrement
    add_action( 'register_user_company', [ &$this, 'register_user_company' ], 10, 1 );
    add_action( 'register_user_particular', [ &$this, 'register_user_particular' ], 10, 1 );
    add_action( 'submit_particular_cv', [ &$this, 'submit_particular_cv' ], 10, 1 );
    add_action( 'forgot_my_password', [ &$this, 'forgot_my_password' ], 10, 2 );
    add_action( 'alert_admin_postuled_offer', [ &$this, 'alert_admin_postuled_offer' ], 10, 1 );
    add_action( 'alert_when_company_interest', [ &$this, 'alert_when_company_interest' ], 10, 2 );
    add_action( 'email_application_validation', [ &$this, 'email_application_validation' ], 10, 1 );
    add_action( 'update_cv', [ &$this, 'update_cv'], 10, 1);

    // Envoyer une email pour administrateur pour informer un nouvelle offre
    add_action( 'create_pending_offer_mail', [&$this, 'create_new_pending_offer_mail'], 10, 1);

    add_action( 'confirm_validate_offer', [&$this, 'confirm_validate_offer'], 10, 1);
    // Envoyer une email de confirmation de validation de CV
    add_action( 'confirm_validate_candidate', [&$this, 'confirm_validate_candidate'], 10, 1);
    // Envoyer une email de confirmation de validation de compte professionnel
    add_action( 'confirm_validate_company', [&$this, 'confirm_validate_company'], 10, 1);

    add_action( 'confirm_accept_registration_formation', [&$this, 'confirm_accept_registration_formation'], 10, 2);
    // Envoyer une email au commercial et a l'administrateur
    // pour notifier une inscription ou un nouveau utilisateur
    add_action( 'new_register_user', [ &$this, 'new_register_user' ], 10, 1 );
    add_action( 'email_new_formation', [ &$this, 'email_new_formation' ], 10, 1 );
    add_action( 'new_request_formation', [ &$this, 'new_request_formation' ], 10, 2 );
    add_action( 'send_registration_formation', [ &$this, 'send_registration_formation' ], 10, 2 );

    add_action( 'acf/save_post', function ( $post_id ) {
      $post_type   = get_post_type( $post_id );
      $post_status = get_post_status( $post_id );

    }, 20, 1 );
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
    if (!is_numeric($user_id)) return false;
    $User = new \WP_User( (int)$user_id );
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
        $Company   = Company::get_company_by( $User->ID );
        $greeting  = isset( $Company->greeting['value'] ) ? $Company->greeting['value'] : "Mr/Mme";
        $con_query = add_query_arg( [
          'action' => "rp",
          "token"  => $User->user_pass,
          "login"  => $User->user_email,
          "redir"  => $this->espace_client
        ], home_url( "/connexion/company/" ) );
        $content   .= $Engine->render( '@MAIL/confirm-register-company.html.twig', [
          'greeting'      => $greeting,
          'company'       => $Company,
          'connexion_url' => $con_query,
          'home_url'      => home_url( "/" ),
          'logo' => $this->logo[0]
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        $content .= $e->getRawMessage();
      }
      $sender = wp_mail( $to, $subject, $content, $headers );
      if ( $sender ) {
        // Mail envoyer avec success
        // Envoyer un mail à l'administrateur
        $year = Date('Y');
        $admin_emails = $this->getModeratorEmail();
        $to        = $admin_emails;
        $subject   = "Notification inscription - {$Company->title}";
        $headers   = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
        $content   = 'Bonjour, <br/>';
        $content   .= "'Une inscription de <b>{$Company->title}</b> portant la réference « <b>{$Company->reference}</b> » en tant que entreprise a été éffectuée ";
        $content   .= "<p>Espace admnistration: <a href='{$this->dashboard_url}/company-lists'>Back office</a> </p> <br/>";
        $content   .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
        // Envoyer un mail à l'entreprise
        wp_mail( $to, $subject, $content, $headers );

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
        $Candidate = Candidate::get_candidate_by( $User->ID );
        $greeting  = isset( $Candidate->greeting['value'] ) ? $Candidate->greeting['value'] : "Mr/Mme";
        $con_query = add_query_arg( [
          'action' => "validation",
          "key"    => $reset_key,
          "login"  => $User->user_email,
          "redir"  => $this->espace_client
        ], home_url( "/connexion/candidate/" ) );
        $content   .= $Engine->render( '@MAIL/confirm-register-particular.html.twig', [
          'greeting'      => $greeting,
          'user_data'     => get_userdata( $user_id ),
          'connexion_url' => $con_query,
          'home_url'      => home_url( "/" ),
          'logo' => $this->logo[0]
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
   * Envoyer un email quand un CV viens d'être ajouter à l'utilisateur et au administrateur
   * @call class-vc-register-candidate.php (line 333)
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
        'user_data'   => [
          'title'     => $Candidate->title,
          'full_name' => $privateInfo->firstname . ' ' . $privateInfo->lastname
        ],
        'greeting' => is_array($Candidate->greeting) ? $Candidate->greeting['label'] : '',
        'oc_url'   => $oc_url,
        'home_url' => home_url( "/" ),
        'logo'     => $this->logo[0],
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
      // FEATURED: Envoyer à l'administrateur

      // $admin_emails - Contient les adresses email de l'admin et les moderateurs
      $admin_emails = $this->getModeratorEmail();
      $admin_emails = empty( $admin_emails ) ? false : $admin_emails;
      if ( ! $admin_emails ) {
        return false;
      }
      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $args           = [
        'logo_url' => $logo[0]
      ];
      $to             = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
      $headers        = [];
      $headers[]      = 'Content-Type: text/html; charset=UTF-8';
      $headers[]      = "From: ItJobMada <{$this->no_reply_email}>";
      $subject        = "#{$Candidate->reference} Enregistrement d'un CV sur ItJobMada - ItJobMada";
      $args           = array_merge( $args, [
        'reference' => $Candidate->reference,
        'firstname' => $Candidate->privateInformations->firstname,
        'lastname'  => $Candidate->privateInformations->lastname,
      ] );
      $content        = '';
      try {
        $args    = array_merge( $args, [
          'dashboard_url' => $this->dashboard_url,
          'home_url'      => home_url( "/" )
        ] );
        $content .= $Engine->render( "@MAIL/admin/notification-new-cv.html.twig", $args );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        $content .= $e->getRawMessage();
      }
      $sender = wp_mail( $to, $subject, $content, $headers );
      if ( $sender ) {
        return true;
      } else {
        return false;
      }

    } else {
      // Erreur d'envoie
      return false;
    }
  }

  /**
   * Récuperer les adresses email de l'administrateur et les moderateurs
   * @return array|string - Array of email string or empty content
   */
  protected function getModeratorEmail() {
    if ( ! is_user_logged_in() ) {
      return [];
    }
    // Les address email des administrateurs qui recoivent les notifications
    // La valeur de cette option est un tableau
    $admin_email               = get_field( 'admin_mail', 'option' ); // return string (mail)
    $admin_email = !$admin_email || empty($admin_email) ? "david@itjobmada.com" : $admin_email;
    return $admin_email;
  }

  /**
   * Cette fonction envoie les formation publier à l'administrateur
   * @param $formation_id
   * @return bool
   */
  public function email_new_formation( $formation_id ) {
    $Formation = new Formation((int) $formation_id, true);
    $author = $Formation->__['author'];
    if (!in_array('company', $author->roles)) return false;
    $Company = Company::get_company_by($author->ID);
    $year = Date('Y');

    $admin_emails = $this->getModeratorEmail();
    $admin_emails = empty( $admin_emails ) ? false : $admin_emails;
    if ( ! $admin_emails ) {
      return false;
    }
    $to        = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $subject   = "{$Formation->reference} -  Notification de l’insertion d’une formation modulaire";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = 'Bonjour, <br/>';
    $content   .= "<p>Une  nouvelle formation modulaire « <b>{$Formation->title}</b> » portant la reférence 
    « <b>{$Formation->reference}</b> » a été insérée sur le site ITJOBMada par <b>{$Company->title}</b></p>";
    $content   .= "<p>Voir la formation: <a href='{$this->dashboard_url}/formation/{$Formation->ID}/edit'>Back office</a> </p> <br/>";
    $content   .= 'A bientôt. <br/><br/><br/>';
    $content   .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
    // Envoyer un mail à l'entreprise
    wp_mail( $to, $subject, $content, $headers );
  }

  /**
   * Envoyer à l'administrateur un mail pour une nouvelle demande de formation
   * @param $subject string
   * @param $Candidate Candidate
   */
  public function new_request_formation( $sujet, $Candidate ) {
    $year = Date('Y');

    $admin_emails = $this->getModeratorEmail();
    $admin_emails = empty( $admin_emails ) ? false : $admin_emails;
    if ( ! $admin_emails ) {
      return false;
    }
    $to        = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $subject   = "Une nouvelle demande de formation sur ITJobMada";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = 'Bonjour, <br/>';
    $content   .= "<p><b>{$Candidate->reference}</b> viens d'inserée une demande de formation « <b>{$sujet}</b> »</p>";
    $content   .= "<p>Voir la demande: <a href='{$this->dashboard_url}/request-formations'>Back office</a> </p> <br/>";
    $content   .= 'A bientôt. <br/><br/><br/>';
    $content   .= "<p style='text-align: center'>ITJobMada © {$year}</p>";
    // Envoyer un mail à l'entreprise
    wp_mail( $to, $subject, $content, $headers );
  }

  /**
   * Cree une notification pour les nouvelles utilisateur particulier ou professionel
   * @call class-itjob.php (line 91), user_register hook
   *
   * @param int $user_id - L'identification du client
   * @return bool|mixed
   */
  public function new_register_user( $user_id ) {
    global $Engine;
    $error    = true;
    $template = null;
    $logo     = null;
    if ( ! is_int( $user_id ) ) {
      return false;
    }
    // $admin_emails - Contient les adresses email de l'admin et les moderateurs
    $admin_emails   = $this->getModeratorEmail();
    $to             = $admin_emails;
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
    $User           = get_user_by( 'ID', $user_id );
    if ( in_array( 'company', $User->roles ) ) {
      // L'utilisateur est une entreprise
      $subject  = "Inscription d'une nouvelle entreprise - ItJobMada";
      $Company  = Company::get_company_by( $User->ID );
      $args     = [
        'logo_url'  => $logo[0],
        'reference' => $User->user_login,
        'name'      => $Company->name,
        'email'     => $Company->email
      ];
      $template = 'company';
      $error    = false;
    }
    if ( in_array( 'candidate', $User->roles ) ) {
      // L'utilisateur est un candidat
      $subject  = "Inscription d'un nouveau compte particulier - ItJobMada";
      $args     = [
        'logo_url'  => esc_url( $logo[0] ),
        'reference' => $User->user_login,
        'name'      => $User->first_name . ' ' . $User->last_name,
        'email'     => $User->user_email
      ];
      $template = 'particular';
      $error    = false;
    }
    if ( $error ) {
      return false;
    }
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
    $content   = '';

    // Featured: Envoyer le mail a l'administrateur ici...
    if ( ! is_null( $template ) ) {
      try {
        $args    = array_merge( $args, [
          'dashboard_url' => $this->dashboard_url,
          'home_url'      => home_url( "/" )
        ] );
        $content .= $Engine->render( "@MAIL/admin/notification-admin-{$template}.html.twig", $args );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        $content .= $e->getRawMessage();
      }

      $sender = wp_mail( $to, $subject, $content, $headers );
      if ( $sender ) {
        // Mail envoyer avec success
        return [
          "msg"     => "Merci de vérifier que vous avez reçu un e-mail avec un lien de récupération.",
          "success" => true
        ];
      } else {
        // Erreur d'envoie
        return [
          "msg"     => "Le message n’a pas pu être envoyé. " .
                       "Cause possible : Votre hébergeur a peut-être désactivé la fonction mail().",
          "success" => false
        ];
      }
    } else {
      return false;
    }
  }

  /**
   * Envoyer un email au administrateur pour les modifications effectuer au CV
   */
  public function update_cv( $candidate_id = null ) {
    $Candidate = new Candidate((int)$candidate_id);
    $firstname = $Candidate->getFirstName();
    $admin_emails = $this->getModeratorEmail();
    $to        = $admin_emails;
    $subject   = "Le CV « {$Candidate->title} » a reçus une modification";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = 'Bonjour, <br/>';
    $content   .= "<p>{$firstname} viens de modifier son CV portant la reférence « {$Candidate->title} »";
    $content   .= "<p>Voir la modification: <a href='{$this->dashboard_url}/candidate/{$candidate_id}/edit'>Back office</a> </p> <br/>";
    $content   .= 'A bientôt. <br/><br/><br/>';
    $content   .= "<p style='text-align: center'>ITJobMada © 2018</p>";
    // Envoyer un mail à l'entreprise
    wp_mail( $to, $subject, $content, $headers );
  }

  /**
   * Notifier l'administrateur si un candidat à postuler à un offre
   *
   * @param int $offer_id
   */
  public function alert_admin_postuled_offer( $offer_id ) {
    global $Engine;
    if ( ! is_user_logged_in() || ! is_int( $offer_id ) ) {
      return false;
    }

    $User = wp_get_current_user();
    if ( ! $User->ID ) {
      return;
    }
    $current_candidate = Candidate::get_candidate_by( $User->ID );
    $current_candidate->__get_access();
    $offer = new Offers( $offer_id );
    // @var array $admin_emails - Contient les adresses email de l'admin et les moderateurs
    $admin_emails = $this->getModeratorEmail();
    $admin_emails = empty( $admin_emails ) ? false : $admin_emails;
    if ( ! $admin_emails ) {
      return false;
    }
    $to        = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $subject   = 'Un candidat « ' . $current_candidate->title . ' » a postule pour un offre - ItJobMada';
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = '';
    try {
      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $content        .= $Engine->render( '@MAIL/admin/notification-admin-for-postuled-offer.html.twig', [
        'candidate_name' => $current_candidate->title,
        'offer_name'     => $offer->postPromote,
        'offer_reference' => $offer->reference,
        'logo'           => $logo[0],
        'dashboard_url'  => $this->dashboard_url
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content .= $e->getRawMessage();
    }
    $sender = wp_mail( $to, $subject, $content, $headers );
    if ( $sender ) {
      $this->email_company_for_postuled( $current_candidate, $offer );
      $this->email_candidate_confirm_postuled( $offer );

      // Mail envoyer avec success
      return true;
    } else {
      // Erreur d'envoie
      return false;
    }
  }

  // Envoyer un email à l'entreprise pour l'informer qu'un candidat à postuler
  private function email_company_for_postuled( $Candidate, $Offer ) {
    global $Engine;
    if ( $Offer instanceof Offers ) {
      $offerUser = $Offer->getAuthor();
      $to        = $offerUser->user_email;
      $subject   = 'Notification d\'un postulant pour l\'offre d’emploi «' . $Offer->postPromote . '» sur ItJobMada';
      $headers   = [];
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
      $content   = '';
      try {
        $postCompany = $Offer->getCompany();
        $Company = new Company($postCompany->ID);
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
        $content        .= $Engine->render( '@MAIL/notification-company-when-candidate-postuled.html.twig', [
          'company'  => $Company,
          'greeting' => empty($Company->greeting) ? 'Mme/Mr' : ($Company->greeting === 'mr' ? 'Monsieur' : 'Madame'),
          'logo'      => $logo[0],
          'oc_url'    => $this->espace_client,
          'candidate' => $Candidate,
          'offer'     => $Offer
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        $content .= $e->getRawMessage();
      }
      $sender = wp_mail( $to, $subject, $content, $headers );
      if ( $sender ) {

        // Mail envoyer avec success
        return true;
      } else {
        // Erreur d'envoie
        return false;
      }
    } else {
      return false;
    }
  }

  // Envoyer un email de confirmation d'envoie de la candidaturea l'entreprise
  private function email_candidate_confirm_postuled( $Offer, $Candidate = null ) {
    global $Engine;
    if ( $Offer instanceof Offers ) {
      if ( null === $Candidate ) {
        $User = wp_get_current_user();
        $to   = $User->user_email;
      } else {
        if ( ! $Candidate instanceof Candidate ) {
          return false;
        }
        $User = $Candidate->getAuthor();
        $to   = $User->user_email;
      }

      $subject   = 'Notification de votre intérêt pour un poste (' . $Offer->reference . ') sur ITJobMada';
      $headers   = [];
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
      $content   = '';
      try {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
        $content        .= $Engine->render( '@MAIL/notification-candidate-confirm-postuled.html.twig', [
          'logo'      => $logo[0],
          'admin_url' => admin_url( '/' ),
          'offer'     => $Offer
        ] );
      } catch ( \Twig_Error_Loader $e ) {
      } catch ( \Twig_Error_Runtime $e ) {
      } catch ( \Twig_Error_Syntax $e ) {
        $content .= $e->getRawMessage();
      }
      $sender = wp_mail( $to, $subject, $content, $headers );
      if ( $sender ) {
        // Mail envoyer avec success
        return true;
      } else {
        // Erreur d'envoie
        return false;
      }
    } else {
      return false;
    }
  }

  // Envoyer un email au candidat et a l'entreprise pour informer que la requete sur l'offre a bien ete valider
  public function email_application_validation( $request ) {
    global $Engine;
    if ( ! is_user_logged_in() ) {
      return false;
    }
    if ( is_object( $request ) && isset( $request->type ) ) {
      $Company   = new Company( (int) $request->id_company );
      $Offre     = new Offers( (int) $request->id_offer );
      $Candidate = new Candidate( $request->id_candidate );
      switch ( $request->type ) {
        case 'apply':
          // Ici le candidat à postuler
          // Envoyer un mail pour le candidat seulement
        case 'interested':
          // Ici l'entreprise à selectionner un candidat
          // Envoyer un mail pour le candidat et l'entreprise

          $candidate_author = $Candidate->getAuthor();
          if ( ! isset( $candidate_author->user_email ) || empty( $candidate_author->user_email ) ) {
            return false;
          }
          $to        = $candidate_author->user_email;
          $subject   = 'Votre CV a été consulté sur ITJobMada';
          $headers   = [];
          $headers[] = 'Content-Type: text/html; charset=UTF-8';
          $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
          $content   = 'Bonjour, <br/>';
          $content   .= "Votre CV a été consulté par <b>{$Company->title}</b>, sur l'offre « {$Offre->title} » <br/>";
          $content   .= "Voir l'offre: {$Offre->offer_url} <br/> <br/>";
          $content   .= 'A bientôt. <br/><br/><br/>';
          $content   .= "<p style='text-align: center'>ITJobMada © 2018</p>";
          // Envoyer un mail pour le candidat
          wp_mail( $to, $subject, $content, $headers );

          if ( $request->type === 'requested' ):
            $to        = $Company->author->user_email;
            $subject   = 'CV disponible sur ITJobMada';
            $headers   = [];
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
            $content   = 'Bonjour, <br/>';
            $content   .= "<p>Le CV portant la référence « {$Candidate->title} » que vous avez sélectionner pour l'offre " .
                          "« {$Offre->title} » est maintenant disponible " .
                          "et vous pouvez le consulter dans votre espace client sur ITJobMada.</p>";
            $content   .= "<p>Espace client: {$this->espace_client}</p> <br/>";
            $content   .= 'A bientôt. <br/><br/><br/>';
            $content   .= "<p style='text-align: center'>ITJobMada © 2018</p>";
            // Envoyer un mail à l'entreprise
            wp_mail( $to, $subject, $content, $headers );
          endif;

          BREAK;
      }
    }
  }

  // Envoyer un mail a l'administrateur pour l'informer qu'une entreprise s'interesse a un candidat.
  public function alert_when_company_interest( $candidat_id, $offer_id ) {
    global $Engine;
    if ( ! is_user_logged_in() ) {
      return false;
    }

    $User            = wp_get_current_user();
    $current_company = Company::get_company_by( $User->ID );
    $Offer  = new Offers((int) $offer_id);
    // @var array $admin_emails - Contient les adresses email de l'admin et les moderateurs
    $admin_emails = $this->getModeratorEmail();
    $admin_emails = empty( $admin_emails ) ? false : $admin_emails;
    if ( ! $admin_emails ) {
      return false;
    }
    $to        = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $subject   = 'L\’Entreprise « ' . $current_company->title . ' » est intéressée par un candidat - ItJobMada';
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = '';
    try {
      $Candidate = new Candidate( $candidat_id );
      $Candidate->__get_access();

      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      // New template...
      $content .= $Engine->render( '@MAIL/admin/notification-admin-for-company-interest.html.twig', [
        'company_name'       => $current_company->title,
        'candidat_firstname' => $Candidate->privateInformations->firstname,
        'candidat_reference' => $Candidate->title,
        'post_promote'       => $Offer->postPromote,
        'logo'               => esc_url( $logo[0] ),
        'home_url'           => home_url( '/' ),
        'admin_url'          => $this->dashboard_url
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content .= $e->getRawMessage();
    }
    $sender = wp_mail( $to, $subject, $content, $headers );
    if ( $sender ) {
      // Mail envoyer avec success
      return true;
    } else {
      // Erreur d'envoie
      return false;
    }
  }

  // Envoyer un mail au candidat pour une notification de validation par l'administrateur
  public function confirm_validate_candidate( $candidat_id ) {
    global $Engine;
    if ( ! is_user_logged_in() ) {
      return false;
    }

    $email  = get_field( 'itjob_cv_email', (int)$candidat_id );
    $author = get_user_by( 'email', $email );
    if ( ! $author ) {
      return false;
    }

    /**
     * Crée une notification au candidat
     */
    do_action('notice_publish_cv', (int)$candidat_id);

    $to        = $email;
    $subject   = 'Votre CV viens d\'être validé - ItJobMada';
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = '';
    try {
      $Candidate      = new Candidate( (int) $candidat_id );
      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $content        .= $Engine->render( '@MAIL/confirm-validate-candidate.html.twig', [
        'greeting'           => isset( $Candidate->greeting['value'] ) ? $Candidate->greeting['value'] : "Mr/Mme/Mlle",
        'candidat_firstname' => $Candidate->privateInformations->firstname,
        'user'               => $author,
        'home_url'           => home_url( '/' ),
        'logo'               => $logo[0],
        'archive_offer_link' => get_post_type_archive_link( 'offers' )
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content .= $e->getRawMessage();
    }
    $sender = wp_mail( $to, $subject, $content, $headers );
    if ( $sender ) {
      /**
       * Envoyer une alert au professionnel abonnée
       */
      $this->alert_for_new_candidate( (int)$candidat_id );
      return true;
    } else {
      // Erreur d'envoie
      return false;
    }
  }

  // Envoyer un mail a l'entreprise pour la confirmation de validation
  public function confirm_validate_company( $company_id ) {
    global $Engine;
    if ( ! is_user_logged_in() ) {
      return false;
    }

    $email     = get_field( 'itjob_company_email', $company_id );
    $to        = $email;
    $subject   = 'Confirmation de l’enregistrement - ItJobMada';
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = '';
    try {
      $Company = new Company( $company_id );
      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $content        .= $Engine->render( '@MAIL/confirm-validate-company.html.twig', [
        'company'  => $Company,
        'greeting' => empty($Company->greeting) ? 'Mme/Mr' : ($Company->greeting === 'mr' ? 'Monsieur' : 'Madame'),
        'oc_url'   => $this->espace_client,
        'home_url' => home_url( '/' ),
        'logo'     => esc_url( $logo[0] )
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content .= $e->getRawMessage();
    }
    $sender = wp_mail( $to, $subject, $content, $headers );
    if ( $sender ) {
      // Mail envoyer avec success
      return true;
    } else {
      // Erreur d'envoie
      return false;
    }
  }

  // Confirmer et activer l'offre dans le site ITJOBMada
  public function confirm_validate_offer( $offer_id ) {
    global $Engine;
    if ( ! is_user_logged_in() ) {
      return false;
    }

    $post_company = get_field( 'itjob_offer_company', $offer_id );
    $to           = get_field( 'itjob_company_email', $post_company->ID );
    $subject      = 'Validation de votre offre d’emploi sur ITJobMada';
    $headers      = [];
    $headers[]    = 'Content-Type: text/html; charset=UTF-8';
    $headers[]    = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content      = '';
    try {
      $Company = new Company( $post_company->ID );
      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $content        .= $Engine->render( '@MAIL/confirm-validate-offer.html.twig', [
        'company'  => $Company,
        'greeting' => empty($Company->greeting) ? 'Mme/Mr' : ($Company->greeting === 'mr' ? 'Monsieur' : 'Madame'),
        'offer'    => new Offers( $offer_id ),
        'oc_url'   => $this->espace_client,
        'home_url' => home_url( '/' ),
        'logo'     => esc_url( $logo[0] )
      ] );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content .= $e->getRawMessage();
    }
    $sender = wp_mail( $to, $subject, $content, $headers );
    if ( $sender ) {
      // Mail envoyer avec success
      // Envoyer une alerte au candidat concerner
      $this->alert_for_new_offer( $offer_id );
      return true;
    } else {
      // Erreur d'envoie
      return false;
    }
  }

  public function confirm_accept_registration_formation($user_id, $formation_id) {
    global $Engine;
    if (!is_numeric($user_id)) return false;
    $User = get_user_by('ID', $user_id);
    if ($User) {
      $Formation = new Formation(intval($formation_id));
      // TODO: Envoyer par mail la confirmation d'inscription pour une formation
    }
  }

  /**
   * Envoyer à l'administrateur un notification quand un candidat s'inscrit sur une
   * formation modulaire
   *
   * @param $user_id integer
   * @param $formation_id integer
   * @return bool
   */
  public function send_registration_formation( $user_id, $formation_id ) {
    if (!is_numeric($user_id) || !is_numeric($formation_id)) return false;
    $User = get_user_by('ID', $user_id);
    if (!in_array('candidate', $User->roles)) return false;
    $Candidate = Candidate::get_candidate_by($User->ID);
    $Formation = new Formation((int)$formation_id);

    $first_name = $Candidate->getFirstName();
    $last_name = $Candidate->getLastName();

    $admin_emails = $this->getModeratorEmail();
    $to        = $admin_emails;
    $subject   = "Notification d'inscription sur une formation modulaire";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_notification_email}>";
    $content   = 'Bonjour, <br/>';
    $content   .= "<p>{$first_name} {$last_name} viens de s'inscrire sur une formation modulaire « <b>{$Formation->title}</b> » portant la réference « <b>{$Formation->reference}</b> »";
    $content   .= "<p>Voir la formation: <a href='{$this->dashboard_url}/formation/{$formation_id}/edit'>Back office</a> </p> <br/>";
    $content   .= '<br/><br/><br/>';
    $content   .= "<p style='text-align: center'>ITJobMada © 2018</p>";

    wp_mail( $to, $subject, $content, $headers );
  }

  // Envoyer une notification a l'administrateur pour une nouvelle offre publier dans le site  
  public function create_new_pending_offer_mail( $offer_id ) {
    global $Engine;

    $Offer = new Offers( (int)$offer_id );
    if ( ! is_user_logged_in() ) {
      return false;
    }
    $User  = wp_get_current_user();
    $Company = Company::get_company_by( $User->ID );
    $admin_emails = $this->getModeratorEmail();
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
    $to             = &$admin_emails;
    $headers        = [];
    $headers[]      = 'Content-Type: text/html; charset=UTF-8';
    $headers[]      = "From: ItJobMada <{$this->no_reply_email}>";
    $subject        = "{$Offer->reference} - Notification de l’insertion d’une nouvelle offre sur ITJobMada";
    $content        = '';
    try {
      $args    = [
        'logo'      => esc_url( $logo[0] ),
        'company'   => $Company,
        'offer'     => $Offer,
        'admin_url' => $this->dashboard_url,
        'home_url'  => home_url( "/" )
      ];
      $content .= $Engine->render( "@MAIL/admin/notification-new-offer.html.twig", $args );
    } catch ( \Twig_Error_Loader $e ) {
    } catch ( \Twig_Error_Runtime $e ) {
    } catch ( \Twig_Error_Syntax $e ) {
      $content .= $e->getRawMessage();
    }
    $sender = wp_mail( $to, $subject, $content, $headers );
    if ( $sender ) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Notifier les candidates qui ont des alerts correspondant
   *
   * @param int $offer_id
   */
  public function alert_for_new_offer( $offer_id ) {
    global $Engine;
    if ( ! is_int( $offer_id ) ) {
      return false;
    }
    $Offer         = new Offers( $offer_id );
    $args          = [
      "post_type"      => "candidate",
      "post_status"    => "publish",
      "posts_per_page" => - 1,
      'meta_query'     => [
        [
          'key'     => 'itjob_cv_notifEmploi_notification',
          'value'   => 1,
          'compare' => '='
        ],
        [
          'key'     => 'itjob_cv_notifEmploi_branch_activity',
          'value'   => $Offer->branch_activity->term_id,
          'compare' => '='
        ]
      ]
    ];
    $postCandidate = get_posts( $args );
    $rechercher    = strip_tags( $Offer->postPromote );
    $rechercher    .= ' ' . strip_tags( $Offer->mission );
    $rechercher    .= ' ' . strip_tags( $Offer->profil );
    $rechercher    .= ' ' . strip_tags( $Offer->otherInformation );
    $rechercher    = strtolower( $rechercher );
    $see_alerts    = [];
    foreach ( $postCandidate as $pts ) {
      $Candidate = new Candidate( $pts->ID );
      $Candidate->__client_premium_access();
      $candidate_alerts = $Candidate->jobNotif['job_sought'];
      $alerts           = explode( ',', $candidate_alerts );
      $alerts           = Arrays::filter( $alerts, function ( $alert ) {
        return empty( $value );
      } );
      $alert_matches    = $this->matches_alerts_content( $alerts, $rechercher );
      if ( ! empty( $alert_matches ) ) {
        $see_alerts[] = (object) [
          'candidate' => [
            'ID'    => $Candidate->getId(),
            'email' => $Candidate->privateInformations->author->user_email
          ],
          'alerts'    => $alert_matches
        ];
      }
    }

    if ( ! empty( $see_alerts ) ) {
      foreach ( $see_alerts as $see ) {
        $to        = $see->candidate['email'];
        $keys      = Arrays::each( $see->alerts, function ( $alert ) {
          return $alert->alert;
        } );
        $keys      = implode( ', ', $keys );
        $subject   = "Votre alerte - ItJobMada";
        $headers   = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
        $content   = '';
        try {
          $custom_logo_id = get_theme_mod( 'custom_logo' );
          $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
          $content        .= $Engine->render( '@MAIL/notification-offer-for-candidate.html.twig', [
            'offer'    => $Offer,
            'alerts'   => $keys,
            'logo'     => esc_url( $logo[0] ),
            'home_url' => home_url( "/" )
          ] );
        } catch ( \Twig_Error_Loader $e ) {
        } catch ( \Twig_Error_Runtime $e ) {
        } catch ( \Twig_Error_Syntax $e ) {
          $content .= $e->getRawMessage();
        }
        $sender = wp_mail( $to, $subject, $content, $headers );
        if ( $sender ) {
          // Mail envoyer avec success
          return true;
        } else {
          // Erreur d'envoie
          return false;
        }
      }

    } else {
      return true;
    }

  }

  /**
   * Notifier les entreprises si le candidate correspont a ce qu'ils recherchent
   *
   * @param int $candidate_id
   * @return bool
   */
  public function alert_for_new_candidate( $candidate_id ) {
    global $Engine, $wpdb;
    if ( ! is_int( $candidate_id ) ) {
      return false;
    }
    $Candidate = new Candidate( $candidate_id );
    if ( ! is_null( $Candidate->branch_activity ) ) {

      $sql = "SELECT * FROM {$wpdb->posts} as pts  WHERE pts.post_type ='company' AND pts.post_status = 'publish'";
      $postCompany = $wpdb->get_results($sql, OBJECT);
      $jobs        = Arrays::each( $Candidate->jobSought, function ( $job ) {
        return $job->name;
      } );
      $emploi_rechercher_candidate = implode( ' ', $jobs );
      $emploi_rechercher_candidate = strtolower( $emploi_rechercher_candidate );
      $see_alerts                  = [];
      foreach ( $postCompany as $pts ) {
        $company       = new Company( $pts->ID );
        $company_alert = get_field( 'itjob_company_alerts', $company->getId() );
        $alerts        = explode( ',', $company_alert );
        $alerts        = Arrays::filter( $alerts, function ( $alert ) {
          return !empty( $alert );
        } );
        // Recherche les alerts
        $alert_matches = $this->matches_alerts_content( $alerts, $emploi_rechercher_candidate );
        if ( ! empty( $alert_matches ) ) {
          $see_alerts[] = (object) [
            'company' => [
              'ID'    => $company->getId(),
              'email' => $company->author->user_email
            ],
            'alerts'  => $alert_matches
          ];
        }

      } // .each company

      // featured: Envoyer les emails
      if ( ! empty( $see_alerts ) ) {
        foreach ( $see_alerts as $see ) {
          $to        = $see->company['email'];
          $keys      = Arrays::each( $see->alerts, function ( $alert ) {
            return $alert->alert;
          } );
          $keys      = implode( ', ', $keys );
          $subject   = "Votre alerte - ItJobMada";
          $headers   = [];
          $headers[] = 'Content-Type: text/html; charset=UTF-8';
          $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
          $content   = '';
          try {
            $custom_logo_id = get_theme_mod( 'custom_logo' );
            $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
            $content        .= $Engine->render( '@MAIL/notification-candidate-to-company.html.twig', [
              'candidate' => $Candidate,
              'alerts'    => $keys,
              'logo'      => $logo[0],
              'home_url'  => home_url( "/" )
            ] );
          } catch ( \Twig_Error_Loader $e ) {
          } catch ( \Twig_Error_Runtime $e ) {
          } catch ( \Twig_Error_Syntax $e ) {
            $content .= $e->getRawMessage();
          }
          $sender = wp_mail( $to, $subject, $content, $headers );
          if ( $sender ) {
            // Mail envoyer avec success
            return true;
          } else {
            // Erreur d'envoie
            return false;
          }
        }
      }

      // Envoyer au abonnée au notification

    } // .end branch activity condition
  }

  /**
   * Réchercher les alerts dans un contenue '$content'
   *
   * @param array $alerts - Les alertssont definie dans ce tableau
   * @param string $content
   *
   * @return array
   */
  protected function matches_alerts_content( $alerts, $content ) {
    $alert_matches = [];
    foreach ( $alerts as $alert ) {
      // Si on trouve une espace dans l'alert, on crée un tableau
      if ( strpos( trim( $alert ), ' ' ) ) {
        $alert = explode( ' ', $alert );
      }
      $pattern = '/';
      if ( is_array( $alert ) ) {
        Arrays::each( $alert, function ( $el, $index ) use ( &$pattern, $alert ) {
          $el      = strtolower( $el );
          $pattern .= "({$el})";
          if ( count( $alert ) - 1 !== $index ) {
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
      preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE );
      if ( $matches ) {
        $matches = Arrays::filter( $matches, function ( $matche ) {
          return ! empty( $matche[0] );
        } );
        if ( ! empty( $matches ) ) {
          $alert = is_array( $alert ) ? implode( ' ', $alert ) : $alert;
        }
        $alert_matches[] = (object) [
          'job_soughts' => $content,
          'pattern'     => $pattern,
          'alert'       => $alert,
          'matches'     => $matches
        ];
      }
    } // .each alerts

    return $alert_matches;
  }


  /**
   * Envoyer un mail de recuperation de mot de passe
   *
   * @param string $email
   * @param string $key - An generate reset key
   */
  public function forgot_my_password( $email, $key ) {
    global $Engine;
    $to        = $email;
    $User      = get_user_by( 'email', $to );
    $subject   = "Réinitialiser votre mot de passe - ItJobMada";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: ItJobMada <{$this->no_reply_email}>";
    $content   = '';
    try {
      $forgot_password_page_id = jobServices::page_exists( 'Forgot password' );
      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $content                 .= $Engine->render( '@MAIL/forgot-password.html.twig', [
        'forgot_link' => get_the_permalink( $forgot_password_page_id ) . "?key={$key}&account={$User->user_login}&action=resetpass",
        'home_url'    => home_url( "/" ),
        'logo'     => $logo[0]
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