<?php
/**
 * Template Name: Forgot Password
 */
wp_enqueue_script('jquery-validate', VENDOR_URL . '/jquery-validation/dist/jquery.validate.min.js', ['jquery'], '1.17.0', true);
get_header();
?>
<style type="text/css">
  .login-content {
    max-width: 400px;
    margin: 100px auto 50px;
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
  (function($) {
    $(document).ready(function () {
      var admin_ajax = "<?= admin_url( 'admin-ajax.php' ) ?>";
      var forgotForm = $("#forgot-form");
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
        highlight: function(e) {
          $(e).closest(".form-group.row").addClass("has-error")
        },
        unhighlight: function(e) {
          $(e).closest(".form-group.row").removeClass("has-error")
        },
      });

      forgotForm
        .submit(function( event ) {
          event.preventDefault();
          var forgotEmail = $('input#forgot_email').val();
          $.ajax({
            method: "POST",
            url: admin_ajax,
            dataType: "json",
            data: { email: forgotEmail, action: "forgot_password" }
          })
            .done(function( resp ) {
              console.log(resp);
            });

        });
    })
  })(jQuery);
</script>
  <div class="uk-section uk-section-transparent uk-padding-remove-top">
    <div class="uk-container uk-container-medium">
      <div class="ibox login-content">
        <div class="text-center">
          <span class="auth-head-icon"><i class="la la-key"></i></span>
        </div>
        <form class="ibox-body pt-0" id="forgot-form" action="" method="POST">
          <h4 class="font-strong text-center mb-4">Identifiant et/ou mot de passe oublié(s)</h4>
          <p class="mb-4">
            Veuillez saisir l'adresse électronique de votre espace personnel
          </p>
          <div class="form-group mb-4">
            <input class="form-control form-control-line" type="text" id="forgot_email" name="mail" placeholder="Adresse électronique ou email">
          </div>
          <div class="text-center d-flex justify-content-center">
            <div class="col-md-5">
              <button class="btn btn-success btn-rounded btn-block btn-air" type="submit" >Valider</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();