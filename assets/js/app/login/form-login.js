angular.module('formLogin', ['ngMessages', 'ngAria', 'ngSanitize'])
  .config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');
  })
  .factory('loginFactory', ['$http', '$q', function ($http, $q) {
    return {
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.ajax_url,
          method: "POST",
          headers: {'Content-Type': undefined},
          data: formData
        });
      }
    };
  }])
  .directive('resetPwd', [function () {
    return {
      restrict: "E",
      templateUrl: itOptions.urlHelper.partials + "/reset-pwd.html",
      scope: true,
      controller: ["$scope", 'loginFactory', function ($scope, loginFactory) {
        $scope.page = 1;
        $scope.disabledSend = false;
        $scope.sendRecoveryLink = () => {
          $scope.disabledSend = true
          const Form = new FormData();
          Form.append('action', 'forgot_password');
          Form.append('email', $scope.infos.email);
          loginFactory
            .sendPostForm(Form)
            .then(resp => {
              let response = resp.data;
              let data = response.data;
              $scope.disabledSend = false;
              if (response.success) {
                $scope.page = 2;
              } else {
                $scope.status = data.msg;
              }
            });
        }

      }]
    }
  }])
  .controller('loginCtrl', ['$scope', '$window', 'loginFactory',
    function ($scope, $window, loginFactory) {
      // itOptions.customer_area_url
      $scope.buttonDisable = false;
      $scope.recoveryPassword = false;
      $scope.infos = {};

      $scope.error = false;
      $scope.errorTitle = '';
      $scope.errorMessage = '';

      $scope.login = null;
      $scope.pwd = null;
      $scope.rememberme = false;

      $scope.formSubmit = function (isValid) {
        if (!isValid) return;
        $scope.error = false;
        var form = new FormData();
        form.append('action', 'ajx_signon');
        form.append('log', $scope.login);
        form.append('pwd', $scope.pwd);
        form.append('rememberme', $scope.rememberme);
        $scope.buttonDisable = true;
        loginFactory
          .sendPostForm(form)
          .then(resp => {
            var response = resp.data;
            var query = response.data;
            if (response.success) {
              var redir = itOptions.urlHelper.redir;
              var pUrl = (_.isNull(redir) || _.isEmpty(redir)) ? itOptions.urlHelper.customer_area_url : redir;
              $window.location.href = pUrl;
            } else {
              if (query.code) {
                switch (query.code) {
                  case 1:
                    $scope.recoveryPassword = true;
                    $scope.infos = _.clone(query.infos);
                    $scope.resetTitle = "Réinitialiser votre mot de passe";
                    break;
                  case 2:
                  case 3:
                    $scope.error = true;
                    $scope.errorTitle = "ERREUR";
                    break;
                
                  default:
                    break;
                }
              }
              $scope.errorMessage = query.msg;
              $scope.buttonDisable = false;
            }
          }, error => {
            $scope.buttonDisable = false;
            alert("Une erreur s'est produite. Connexion introuvable. Veuillez réessayer ultérieurement.");
          })
      };

    }])