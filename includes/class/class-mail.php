<?php

namespace includes\mailing;

use includes\object\jobServices;
use includes\post\Candidate;
use includes\post\Company;
use includes\post\Offers;
use Underscore\Types\Arrays;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Mailing {
  public $espace_client;
  private $no_reply_email = "no-reply@itjobmada.com>";
  private $no_reply_notification_email = "no-reply-notification@itjobmada.com";
  private $dashboard_url = "http://oc-itjob.falicrea.com";

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
    add_action( 'alert_for_postuled_offer', [ &$this, 'alert_for_postuled_offer' ], 10, 1 );
    add_action( 'alert_when_company_interest', [ &$this, 'alert_when_company_interest' ], 10, 1 );
    add_action( 'new_validate_term', [ &$this, 'notif_admin_new_validate_term' ], 10, 1 );

    // Activer le CV
    // Envoyer une alert pour les entreprises
    add_action( 'acf/save_post', function ( $post_id ) {
      $post_type   = get_post_type( $post_id );
      $post_status = get_post_status( $post_id );

      if ( $post_type === 'candidate' || $post_status === 'publish' ) {
        $this->alert_for_new_candidate( $post_id );
      }

      if ( $post_type === 'offers' || $post_status === 'publish' ) {
        $this->alert_for_new_offer( $post_id );
      }
    }, 10, 1 );
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
        'logo_url' => esc_url( $logo[0] )
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
          'dashboard_url' => "#dashboard",
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
   * Récuperer les adresses email de l'administrateur
   * @return array|string - Array of email string or empty content
   */
  protected function getModeratorEmail() {
    if ( ! is_user_logged_in() ) {
      return [];
    }
    // Les address email des administrateurs qui recoivent les notifications
    // La valeur de cette option est un tableau
    $admin_notification_emails = get_field( 'admin_editor_user', 'option' ); // Return array of user (WP_User)
    $admin_email               = get_field( 'admin_mail', 'option' ); // return string (mail)
    if ( empty( $admin_notification_emails ) ) {
      return $admin_email;
    }

    return is_array( $admin_notification_emails ) ? $admin_notification_emails : $admin_email;
  }


  public function notif_admin_new_validate_term( $terms = [] ) {
    global $Engine;
    if (empty($terms)) return false;
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
    $error    = true;
    $template = null;
    $logo     = null;
    if ( ! is_int( $user_id ) ) {
      return false;
    }
    // $admin_emails - Contient les adresses email de l'admin et les moderateurs
    $admin_emails = $this->getModeratorEmail();
    $admin_emails = empty( $admin_emails ) ? false : $admin_emails;
    if ( ! $admin_emails ) {
      return false;
    }
    $to             = is_array( $admin_emails ) ? implode( ',', $admin_emails ) : $admin_emails;
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
    $User           = get_user_by( 'ID', $user_id );
    $args           = [
      'logo_url' => esc_url( $logo[0] )
    ];
    if ( in_array( 'company', $User->roles ) ) {
      // L'utilisateur est une entreprise
      $subject  = "Inscription d'une nouvelle entreprise - ItJobMada";
      $Company  = Company::get_company_by( $User->ID );
      $args     = array_merge( $args, [
        'reference' => $User->user_login,
        'name'      => $Company->name,
        'email'     => $Company->email
      ] );
      $template = 'company';
      $error    = false;
    }
    if ( in_array( 'candidate', $User->roles ) ) {
      // L'utilisateur est un candidat
      $subject  = "Inscription d'un nouveau compte particulier - ItJobMada";
      $args     = array_merge( $args, [
        'reference' => $User->user_login,
        'name'      => $User->first_name . ' ' . $User->last_name,
        'email'     => $User->user_email
      ] );
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
          'dashboard_url' => "#dashboard",
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
   * Notifier l'administrateur si un candidat à postuler à un offre
   *
   * @param int $offer_id
   */
  public function alert_for_postuled_offer( $offer_id ) {
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
        'logo'           => esc_url( $logo[0] ),
        'dashboard_url'  => $this->dashboard_url
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

  /**
   * Envoyer un mail a l'administrateur pour l'informer qu'une entreprise s'interesse
   * a un candidat.
   */
  public function alert_when_company_interest( $candidat_id ) {
    global $Engine;
    if ( ! is_user_logged_in() ) {
      return false;
    }

    $User            = wp_get_current_user();
    $current_company = Company::get_company_by( $User->ID );
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
      $Candidat = new Candidate( $candidat_id );
      $Candidat->__get_access();

      $custom_logo_id = get_theme_mod( 'custom_logo' );
      $logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
      $content        .= $Engine->render( '@MAIL/admin/notification-admin-for-company-interest.html.twig', [
        'company_name'       => $current_company->title,
        'candidat_firstname' => $Candidat->privateInformations->firstname,
        'candidat_reference' => $Candidat->title,
        'logo'               => esc_url( $logo[0] ),
        'dashboard_url'      => $this->dashboard_url
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
   *
   * @return bool
   */
  public function alert_for_new_candidate( $candidate_id ) {
    global $Engine;
    if ( ! is_int( $candidate_id ) ) {
      return false;
    }
    $Candidate = new Candidate( $candidate_id );
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
      $jobs                        = Arrays::each( $Candidate->jobSought, function ( $job ) {
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
          return empty( $value );
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
              'logo'      => esc_url( $logo[0] ),
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

      } else {
        return true;
      }
    } // .end branch activity condition
  }

  /**
   * Réchercher les alerts dans un contenue '$content'
   *
   * @param array $alerts
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
      // TODO: Il est préférable que le champ d'alert ne contient pas d'espace
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