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