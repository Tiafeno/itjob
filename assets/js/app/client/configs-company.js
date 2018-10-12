APPOC.config(['$interpolateProvider', '$routeProvider', function ($interpolateProvider, $routeProvider) {
  $interpolateProvider.startSymbol('[[').endSymbol(']]');
  $routeProvider
    .when('/oc-company', {
      templateUrl: itOptions.Helper.tpls_partials + '/oc-company.html',
      controller: 'clientCtrl',
      resolve: {
        Client: ['$http', '$q', function ($http, $q) {
          let access = $q.defer();
          $http.get(itOptions.Helper.ajax_url + '?action=client_area', {cache: false})
            .then(resp => {
              let data = resp.data;
              access.resolve(data);
            });
          return access.promise;
        }]
      }
    })
    .otherwise({redirectTo: '/oc-company'});
}])
.directive('planPremium', [function() {
  return {
    restrict: 'E',
    scope: true,
    templateUrl: itOptions.Helper.tpls_partials + '/premium-plan.html',
    link: function (scope, element, attr) {},
    controller: ['$scope', '$http', function ($scope, $http) {
      $scope.accountUpgrade = !$scope.Company.account;
      $scope.updateAccount = () => {
        alertify
          .okBtn("Confirmer")
          .cancelBtn("Annuler")
          .confirm("Un mail sera envoyer à l'administrateur pour valider votre demande.<br> Pour plus d'informations, contactez le service commercial au:\n" +
            "<b>032 45 378 60 - 033 82 591 13 - 034 93 962 18.</b>",
            function (ev) {
              // Oui
              ev.preventDefault();
              const formData = new FormData();
              formData.append('action', 'send_request_premium_plan');
              formData.append('token', itOptions.token);
              $http({
                url: itOptions.Helper.ajax_url,
                method: "POST",
                headers: { 'Content-Type': undefined },
                data: formData
              })
                .then(resp => {
                  let data = resp.data;

                });
            }, function (ev) {
              // Annuler
              ev.preventDefault();
            });
      };
    }]
  }
}])