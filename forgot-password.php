<?php

/**
 * Template Name: Forgot Password
 */
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
  </style>
  <script type="text/javascript">
    (function ($) {
      $(document).ready(function () {
        var admin_ajax = "<?= admin_url( 'admin-ajax.php' ) ?>";
        var forgotForm = $("#forgot-form");
        var successMessage = $('.alert.success-message');
        var errorMessage = $('.alert.error-message');
        var submitButton = $('.login-content').find('button');
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
          });

        forgotForm
          .submit(function (event) {
            event.preventDefault();
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
                var element = resp.success ? successMessage : errorMessage;
                element.html(resp.data).show();
                submitButton.text('Envoyer');
              });

          });
      })
    })(jQuery);
  </script>
<?php
$forgot_password = Http\Request::getValue( 'forgot_password', 0 );
?>
  <div class="uk-section uk-section-transparent uk-padding-remove-top">
    <div class="uk-container uk-container-medium">
      <div class="ibox recovery-content">
        <div class="text-center">
          <span class="auth-head-icon"><i class="la la-key"></i></span>
        </div>
        <?php
        if ( (int) $forgot_password === 0 ) { ?>
          <form class="ibox-body pt-0" id="forgot-form" action="" method="POST">
            <h4 class="font-strong text-center mb-4">Identifiant et/ou mot de passe oublié(s)</h4>
            <p class="mb-4">
              Veuillez saisir l'adresse électronique de votre espace personnel
            </p>

            <div class="alert alert-pink alert-dismissable fade show alert-outline error-message" style="display:none">
              ...
            </div>

            <div class="alert alert-info alert-dismissable fade show alert-outline success-message"
                 style="display:none">
              ...
            </div>

            <div class="form-group mb-4">
              <input class="form-control form-control-solid" type="text" id="forgot_email" name="mail"
                     placeholder="Adresse électronique ou email">
            </div>
            <div class="text-center d-flex justify-content-center">
              <div class="col-auto">
                <button class="btn btn-primary btn-block " type="submit">Envoyer</button>
              </div>
            </div>
          </form>
        <?php
        }

        if ( (int) $forgot_password === 1) { ?>
          <form class="ibox-body pt-0" id="change-password-form" action="" method="POST">
            <h4 class="font-strong text-center mb-4">Modifier mon mot de passe</h4>
            <div class="form-group mb-4">
              <input class="form-control form-control-solid" type="password" id="pwd" name="pwd"
                     placeholder="Nouveau mot de passe">
              <input class="form-control form-control-solid mt-2" type="password" id="cpwd" name="cpwd"
                     placeholder="Confirmation">
            </div>
            <div class="text-center d-flex justify-content-center">
              <div class="col-auto">
                <button class="btn btn-primary btn-block " type="submit">Modifier</button>
              </div>
            </div>
          </form>
        <?php
        } ?>
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();