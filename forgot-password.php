<?php

/**
 * Template Name: Forgot Password
 */

$action = "";
if ( isset($_GET['action']) || !empty($_GET['action']) ) {
  $action = $_GET['action'];
  // Initialisation de la variable erreur
  $errors = new WP_Error();
  switch ( $action ):
    case 'resetpass':
      list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
      // Crée et ajouter le nom du cookie
      $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
      if ( isset($_GET['key']) ) {
        // Valeur du cookie
        $value = sprintf( '%s:%s', wp_unslash( $_GET['account'] ), wp_unslash( $_GET['key'] ) );
        // Ajouter une cookie qui contien la clé et le login
        setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
        wp_safe_redirect( remove_query_arg( array( 'key', 'account' ) ) );
        exit;
      } 
      // Si la cookie est definie, on ajoute l'utilisateur dans le variable $user
      // Sinon, $user est égala à false
      if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
        list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
        $user = check_password_reset_key( $rp_key, $rp_login );
        if ( isset( $_POST['pwd'] ) && ! hash_equals( $rp_key, $_POST['rp_key'] ) ) {
          $user = false;
        }
      } else {
        $user = false;
      }

      // Si la variable utilisateur ou $user est false ou un erreur on efface la cookie
      // et on redirige la page vers la page de reinitialisation de mot de passe
      if ( ! $user || is_wp_error( $user ) ) {
        setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
        // Revenir a la premiere étape de la reinitialisation de mot de passe
        if ( $user && $user->get_error_code() === 'expired_key' ) {
          wp_safe_redirect( remove_query_arg( array( 'action', 'key', 'account' ) ) );
        } else {
          wp_safe_redirect( remove_query_arg( array( 'action', 'key', 'account' ) ) );
        }
        exit;
      }

      // Si le mot de passe est envoyer par un formulaire et qu'ils ne sont pas identique on ajoute une erreur
      if ( isset($_POST['pwd']) && $_POST['pwd'] != $_POST['cpwd'] ) {
        $errors->add( 'password_reset_mismatch', "Les mots de passe saisis ne sont pas identiques." );
      }

      /**
       * Fires before the password reset procedure is validated.
       *
       * @since 3.5.0
       *
       * @param object $errors WP Error object.
       * @param WP_User|WP_Error $user WP_User object if the login and reset key match. WP_Error object otherwise.
       */
      do_action( 'validate_password_reset', $errors, $user ); // Verifier si l'utilisateur a reinitialiser le mot de passe

      // On change le mot de passe si ces condition sont requis
      if ( ( ! $errors->get_error_code() ) && isset($_POST['pwd']) && !empty($_POST['pwd'])) {
        // Modifie le mot de passe
        reset_password( $user, $_POST['pwd'] );
        setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
        // Password is reset succefuly
        $role = in_array('company', $user->roles) ? 'company' : 'candidate';

        // Verifier si l'utilisateur est importer depuis le fichier CSV
        $recoverUserPassword = get_user_meta($user->ID, "__recovery_password", true);
        if ($recoverUserPassword) {
          update_user_meta($user->ID, '__recovery_password', 0);
        }

        wp_safe_redirect( add_query_arg( [ 'action' => 'confirmaction', 'role' =>  $role] ) );
        exit;
      }

      break;

    case "confirmaction":
      $espace_client_page = \includes\object\jobServices::page_exists("Espace client");
      $redir = get_the_permalink($espace_client_page);
      $role = isset($_GET['role']) ? $_GET['role'] : 'candidate';
      $login_url = home_url("/connexion/{$role}/?redir={$redir}");
      break;
  endswitch;
}
wp_enqueue_script( 'jquery-validate', VENDOR_URL . '/jquery-validation/dist/jquery.validate.min.js', [ 'jquery' ], '1.17.0', true );
get_header();
?>
  <style type="text/css">
    .recovery-content {
      max-width: 400px;
      margin: 100px auto 50px;
    }

    .recovery-content input {
      font-family: 'Montserrat', sans-serif;
      font-size: 12px;
    }

    .auth-head-icon {
      position: relative;
      height: 60px;
      width: 60px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      background-color: #fff;
      color: #5c6bc0;
      box-shadow: 0 5px 20px #d6dee4;
      border-radius: 50%;
      transform: translateY(-50%);
      z-index: 2;
    }

    #change-password-form label.error {
      font-size: 12px;
      color: red;
      padding-left: 5px;
    }
    #change-password-form input.error {
      border: 1px solid red;
    }
    #change-password-form input.valid {
      border: 1px solid #7bca44;
    }
  </style>
  <script type="text/javascript">
    (function ($) {
      $(document).ready(function () {
        var admin_ajax = "<?= admin_url( 'admin-ajax.php' ) ?>";
        var forgotForm = $("#forgot-form");
        var successMessage = $('.alert.success-message');
        var errorMessage = $('.alert.error-message');
        var submitButton = $('.recovery-content').find('button');

        if ($().validate) {
          $.validator.addMethod("pwdpattern", function (value) {
            return /^(?=(.*\d){2})(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z\d]).{8,}$/.test(value)
          });
          $("#change-password-form").validate({
            rules: {
              pwd: {
                required: true,
                pwdpattern: true,
                minlength: 8,
              },
              cpwd: {
                equalTo: "#pwd"
              }
            },
            messages: {
              pwd: {
                required: "Ce champ est obligatoire",
                pwdpattern: "Votre mot de passe doit comporter 8 caractères minimum, comprenant des chiffres et des lettres minuscules et"+
                " majuscules,ainsi qu'un caractère spécial (-*/@+\_%$=).",
              },
              cpwd: {
                required: "Ce champ est obligatoire",
                equalTo: "Les mots de passes ne sont pas identiques."
              }
            }
          });

          forgotForm
            .validate({
              rules: {
                mail: {
                  required: !0,
                  email: !0
                }
              },
              messages: {
                mail: {
                  email: "Vérifiez l'adresse email, son format n'est pas valide.",
                  required: "Veuillez saisir une adresse email."
                }
              },
              errorClass: "help-block error",
              highlight: function (e) {
                $(e).closest(".form-group.row").addClass("has-error")
              },
              unhighlight: function (e) {
                $(e).closest(".form-group.row").removeClass("has-error")
              },
              submitHandler: function (form) {
                errorMessage.hide();
                successMessage.hide();
                var forgotEmail = $('input#forgot_email').val();
                submitButton.text('Chargement en cours ...');
                $.ajax({
                  method: "POST",
                  url: admin_ajax,
                  dataType: "json",
                  data: {email: forgotEmail, action: "forgot_password"}
                })
                  .done(function (resp) {
                    submitButton.text('Envoyer');
                    var element = resp.success ? successMessage : errorMessage;
                    element.html(resp.data.msg).show();
                  });

              }
            });
        }

      })
    })(jQuery);
  </script>
<?php
$forgot_password = Http\Request::getValue( 'forgot_password', 0 );
?>
  <div class="uk-section uk-section-transparent uk-padding-remove-top">
    <div class="uk-container uk-container-medium">
      <div class="<?= $action !== 'confirmaction' ? 'ibox' : '' ?> recovery-content">
        <?php
        if ( !isset($_GET['action']) ) :?>
          <div class="text-center">
            <span class="auth-head-icon"><i class="la la-key"></i></span>
          </div>
          <form class="ibox-body pt-0" id="forgot-form" action="" method="POST">
            <h4 class="font-strong text-center mb-4">Mot de passe oublié</h4>
            <p class="mb-4">
              Veuillez saisir votre adresse de messagerie.
              Un lien permettant de créer un nouveau mot de passe vous sera envoyé par e-mail.
            </p>
<!--            Error message -->
            <div class="alert alert-pink alert-dismissable fade show alert-outline error-message" style="display:none">
              ...
            </div>
<!--            Success message -->
            <div class="alert alert-info alert-dismissable fade show alert-outline success-message"
                 style="display:none">
              ...
            </div>

            <div class="form-group mb-4">
              <input class="form-control form-control-solid" type="text" id="forgot_email" name="mail"
                     placeholder="Adresse électronique ou e-mail">
            </div>
            <div class="text-center d-flex justify-content-center">
              <div class="col-auto">
                <button class="btn btn-primary btn-block" type="submit">Envoyer</button>
              </div>
            </div>
          </form>
        <?php
        endif;

        if ( isset($_GET['action']) && $_GET['action'] === 'resetpass' ) : ?>
          <div class="text-center">
            <span class="auth-head-icon"><i class="la la-key"></i></span>
          </div>
          <form class="ibox-body pt-0" id="change-password-form" action="" method="POST">
            <h4 class="font-strong text-center mb-4">Modifier mon mot de passe</h4>
            <?php if ($errors->get_error_code()) : ?>
              <div class="alert alert-pink alert-dismissable fade show alert-outline has-icon"><i class="la la-info-circle alert-icon"></i>
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <strong>Erreur!</strong><br>
                    <?= $errors->get_error_message() ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <div class="form-group mb-4">
              <input class="form-control form-control-solid" type="password" id="pwd" name="pwd"
                     placeholder="Nouveau mot de passe" required>
              <input class="form-control form-control-solid mt-2" type="password" id="cpwd" name="cpwd"
                     placeholder="Confirmation" required>
            </div>
            <div class="text-center d-flex justify-content-center">
              <div class="col-auto">
                <input type="hidden" name="rp_key" value="<?= esc_attr( $rp_key ); ?>" />
                <button class="btn btn-primary btn-block " type="submit">Modifier</button>
              </div>
            </div>
          </form>
        <?php
        endif; ?>

        <?php
        if ( isset($_GET['action']) && $_GET['action'] === 'confirmaction' ) : ?>
          <div class="ibox-body">
            <div class="alert alert-info alert-dismissable fade show">
              <h4>Félicitation!</h4>
              <p>Votre mot de passe a été réinitialisé.</p>
              <p>
                <a href="<?= $login_url ?>" class="btn btn-secondary btn-sm mr-2">Se connecter</a>
              </p>
            </div>
          </div>
        <?php
        endif;
        ?>
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();