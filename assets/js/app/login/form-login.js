angular.module('formLogin', ['ngMessages', 'ngAria'])
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
  .controller('loginCtrl', ['$scope', '$location', 'loginFactory',
    function ($scope, $location, loginFactory) {
      // itOptions.customer_area_url
      $scope.buttonDisable = false;
      $scope.error = false;
      $scope.login = null;
      $scope.pwd = null;
      $scope.rememberme = false;
      $scope.formSubmit = function (isValid) {
        if (!isValid) return;
        var form = new FormData();
        form.append('action', 'ajx_signon');
        form.append('log', $scope.login);
        form.append('pwd', $scope.pwd);
        form.append('rememberme', $scope.rememberme);
        $scope.buttonDisable = true;
        loginFactory
          .sendPostForm(form)
          .then(function (resp) {
            var data = resp.data;
            if (data.logged) {
              $location.path(itOptions.customer_area_url);
            } else {
              $scope.error = true;
              $scope.buttonDisable = false;
            }
          })
      };

    }])