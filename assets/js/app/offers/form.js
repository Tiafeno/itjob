angular.module('addeOfferApp', ['ui.router', 'ngMessages', 'ngAria', 'ngSanitize'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');

    var states = [
      {
        name: 'form',
        url: '/form',
        component: 'formComponent',
        resolve: {
          abranchs: function (offerService) {
            return offerService.getBranchActivity();
          },
          regions: function (offerService) {
            return offerService.getRegions();
          }
        }
      },
      {
        name: 'confirmation',
        url: '/confirmation',
        component: 'confComponent',
        resolve: {
          message: function (offerData) {
            return offerData.message;
          }
        }
      }
    ];
    // Loop over the state definitions and register them
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
    $urlServiceProvider.rules.otherwise({state: 'form'});

  })
  .service('offerService', ['$http', '$q', function ($http, $q) {
    return {
      getBranchActivity: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_branch_activity', {cache: true})
          .then(function (resp) {
            return resp.data;
          });
      },
      getRegions: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=region', {cache: true})
          .then(function (r) {
            return r.data;
          });
      }
    }
  }])
  .service('offerData', [function () {
    var self = this;
    self.formOfferValue = {};
    self.message = {title: null, msg: null};
    self.setMessage = function (_title, _msg) {
      self.message = {title: _title, msg: _msg};
    };
  }])
  .factory('offerFactory', ['$http', '$q', function ($http, $q) {
    return {
      checkLogin: function (log) {
        return $http.get(itOptions.ajax_url + '?action=ajx_user_exist&log=' + log, {cache: true})
          .then(function (resp) {
            return resp.data;
          });
      },
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
  .directive('compareTo', function () {
    // Directive: Comparer les mots de passes
    return {
      require: "ngModel",
      scope: {
        repeaterPwd: "=compareTo"
      },
      link: function (scope, element, attrs, value) {
        value.$validators.compareTo = function (val) {
          return val == scope.repeaterPwd;
        };
        scope.$watch('repeaterPwd', function () {
          value.$validate();
        })
      }
    }
  })
  .component('formComponent', {
    bindings: {abranchs: '<', regions: '<'},
    templateUrl: itOptions.partials_url + '/offers/form.html',
    controller: ["$location", "$scope", "offerData", "offerService", "offerFactory",
      function ($location, $scope, offerData, offerService, offerFactory) {
        $scope.offers = {};
      }]
  })
  .component('confComponent', {
    controller: ["$scope", function ($scope) {

    }]
  })
  .controller('addCtrl', ['$scope', function ($scope) {
    // Code controller here...
    $scope.loadingPath = itOptions.template_url + '/img/loading.gif';
  }])