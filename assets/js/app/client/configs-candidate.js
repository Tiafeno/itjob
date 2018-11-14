APPOC.config(['$interpolateProvider', '$routeProvider', function ($interpolateProvider, $routeProvider) {
  $interpolateProvider.startSymbol('[[').endSymbol(']]');
  $routeProvider
    .when('/oc-candidate', {
      templateUrl: itOptions.Helper.tpls_partials + '/oc-candidate.html',
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
    .otherwise({redirectTo: '/oc-candidate'});
}])
.directive('candidacy', [function () {
  return {
    restrict: 'E',
    templateUrl: itOptions.Helper.tpls_partials + '/candidacy.html',
    scope: true,
    link: function (scope, element, attr) {},
    controller: ['$scope', '$http', function ($scope, $http) {
      const self = this;
      $scope.Candidatures = [];
      $scope.relaunchCandidature = (id_offer, $event) => {
        // envoyer une notification à l'entreprise pour vérifier la candidature
      };
      self.Initialize = () => {
        $http.get(`${itOptions.Helper.ajax_url}?action=get_candidacy`, {
          cache: false
        })
          .then(resp => {
            const query = resp.data;
            if (query.success) {
              $scope.Candidatures = _.clone(query.data);
            } else {
              $scope.error = query.data;
            }
          });
      };
      self.Initialize();
    }]
  }
}])