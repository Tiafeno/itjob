angular.module('clientApp', ['ngMessages'])
  .value('Company', )
  .config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');
  })
  .factory('clientFactory', ['$http', '$q', function ($http, $q) {
    return {
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.helper.ajax_url,
          method: "POST",
          headers: {'Content-Type': undefined},
          data: formData
        });
      }
    };
  }])
  .service('clientService', ['$http', function ($http) {
    this.offers = _.clone(itOptions.offers);
    this.Company = () => {
      return $http.get(itOptions.Helper.ajax_url + '?action=client_company', {cache: true});
    }
  }])
  .filter('Greet', [function () {
    const Greeting = [
      {
        greeting: 'mrs',
        label: 'Madame'
      },
      {
        greeting: 'mr',
        label: 'Monsieur'
      }
    ];
    return value => {
      if (typeof value === 'undefined') return null;
      return _.findWhere(Greeting, {greeting: value}).label;
    }
  }])
  .component('generalInformation', {
    templateUrl: itOptions.Helper.tpls_partials + '/general-information.html',
    bindings: {company: '='},
    controller: ['$scope', function ($scope) {
      $scope.Entreprise = {};
      this.$onInit = () => {

      };
      $scope.$parent.$watch('Company', value => {
        $scope.Entreprise =  _.clone(value);
        console.log(value);
      }, true)
    }]
  })
  .controller('clientCompanyCtrl', ['$scope', '$timeout', 'clientFactory', 'clientService',
    function ($scope, $timeout, clientFactory, clientService) {
      $scope.loading = false;
      $scope.Company = {};
      clientService
        .Company()
        .then(resp => {
          var data = resp.data;
          $scope.Company = data;
          $scope.loading = true;
        });

      this.$onInit = function () {
        $timeout(() => {
          // Timeout here ...
        }, 2000);
      };
      // Trash offert
      $scope.trashOffer = function (offerId) {
        var offer = _.findWhere(clientService.offers, {ID: parseInt(offerId)});
        var form = new FormData();
        swal({
          title: "Supprimer",
          text: offer.postPromote,
          type: "error",
          confirmButtonText: 'Oui, je suis sûr',
          cancelButtonText: "Annuler",
          showCancelButton: true,
          closeOnConfirm: false,
          showLoaderOnConfirm: true
        }, function () {
          form.append('action', 'trash_offer');
          form.append('pId', parseInt(offerId));
          clientFactory
            .sendPostForm(form)
            .then(function (resp) {
              var data = resp.data;
              if (data.success) {
                // Successfully delete offer
                swal({
                  title: 'Confirmation',
                  text: data.msg,
                  type: 'info'
                }, function () {
                  location.reload();
                });
              } else {
                swal(data.msg);
              }
            });
        });


      }
    }])