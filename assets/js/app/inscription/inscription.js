const Route = angular.module('ngRouteInscription', ['ngRoute', 'ngSanitize', 'ngMessages', 'ngAria', 'ngAnimate'])
  .factory('Request', ["$q", "$http", function($q, $http) {
    return {
      getRegions: () => {
        return $http.get(ijOptions.ajax_url, {
          params: {
            action: 'ajax_taxonomy_content',
            taxonomy: 'itjob_region'
          }
        })
      },
      getActivityArea: () => {

      }
    }
  }])
  .service('InscriptionServices', ["Request", function(Request) {

  }])
  .controller('InscriptionCVController', ["$scope", function($scope) {

  }])

  .config(['$routeProvider', function($routeProvider) {
    $routeProvider
      .when('/inscription/cv/p1', {
        templateUrl: ijOptions.dir_uri + '/js/app/inscription/cv/',
        controller: 'OurInformationController'
      })
      .when('/inscription/cv/p2', {
        templateUrl: ijOptions.dir_uri + '/',
        controller: 'OurExperienceController'
      })
      .when('/inscription/cv/p3', {
        templateUrl: ijOptions.dir_uri + '/',
        controller: 'InterestController'
      })
      .otherwise(({
        redirectTo: '/inscription/cv/p1'
      }))
  }]);
