angular.module('clientApp', ['ngMessages', 'froala', 'ngSanitize'])
  .value('froalaConfig', {
    toolbarInline: false,
    quickInsertTags: null,
    toolbarButtons: ['bold', 'strikeThrough', 'subscript', 'superscript', 'align', 'formatOL', 'formatUL', 'indent', 'outdent', 'undo', 'redo'],
  })
  .config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');
  })
  .factory('clientFactory', ['$http', '$q', function ($http, $q) {
    return {
      getCity: function () {
        return $http.get(itOptions.Helper.ajax_url + '?action=get_city', {cache: true})
          .then(function (resp) {
            return resp.data;
          });
      },
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.Helper.ajax_url,
          method: "POST",
          headers: {
            'Content-Type': undefined
          },
          data: formData
        });
      }
    };
  }])
  .service('clientService', ['$http', function ($http) {
    this.offers = _.clone(itOptions.offers);
    this.Company = () => {
      return $http.get(itOptions.Helper.ajax_url + '?action=client_company', {
        cache: true
      });
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
      return _.findWhere(Greeting, {
        greeting: value
      }).label;
    }
  }])
  .directive('generalInformation', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/general-information.html',
      scope: {
        Entreprise: '=company'
      },
      link: function (scope, element, attrs) {

      }
    }
  }])
  .directive('offerLists', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/offer-lists.html',
      scope: {
        Entreprise: '=company',
        Offers: '=offers',
        regions: '&',
        allCity: '&',
        abranchs: '&'
      },
      link: function (scope, element, attrs) {
        scope.Helper = itOptions.Helper;

        jQuery('.input-group.date').datepicker({
          format: "mm/dd/yyyy",
          language: "fr",
          startView: 2,
          todayBtn: false,
          keyboardNavigation: true,
          forceParse: false,
          autoclose: true
        });

      },
      controller: ['$scope', '$q', 'clientFactory', function ($scope, $q, clientFactory) {
        $scope.offerEditor = {};
        $scope.openEditor = (offerId) => {
          let offer = _.findWhere($scope.Offers, {
            ID: parseInt(offerId)
          });

          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {

            $scope.Regions        = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys          = _.clone(data[2]);

            $scope.offerEditor = _.mapObject(offer, (val, key) => {
              if (typeof val.term_id !== 'undefined') return val.term_id;
              if (typeof val.label !== 'undefined') return val.value;
              if (typeof val.post_title !== 'undefined') return val.ID;
              if (key === 'proposedSalary') return parseInt(val);
              return val;
            });
            if (!_.isEmpty(offer) || !_.isNull($scope.offerEditor)) {
              UIkit.modal('#modal-edit-overflow').show();
            }
          });

        };
        $scope.$watch('current', value => {

        });

        $scope.editOffer = (offerId) => {
          let offerForm = new FormData();
          let formObject = Object.keys($scope.offerEditor);
          offerForm.append('action', 'update_offer');
          offerForm.append('post_id', parseInt(offerId));
          formObject.forEach(function (property) {
            let propertyValue = Reflect.get($scope.offerEditor, property);
            offerForm.set(property, propertyValue);
          });
          clientFactory
            .sendPostForm(offerForm)
            .then(resp => {

            });
        };
      }]
    }
  }])
  .controller('clientCompanyCtrl', ['$scope', '$http', '$q', 'clientFactory', 'clientService',
    function ($scope, $http, $q, clientFactory, clientService) {
      $scope.loading = false;
      $scope.Company = {};
      $scope.offerLists = [];

      // Récuperer les données du client
      clientService
        .Company()
        .then(resp => {
          var data = resp.data;
          $scope.Company = data.Company;
          $scope.offerLists = data.Offers;
          $scope.loading = true;
        });

      this.$onInit = function () {

      };

      $scope.asyncTerms = (Taxonomy) => {
        if (Taxonomy !== 'city') {
          return $http.get(itOptions.Helper.ajax_url + '?action=ajx_get_taxonomy&tax=' + Taxonomy, {
            cache: true
          }).then(resp => resp.data);
        } else {
          return clientFactory.getCity();
        }
        
      };

      // Trash offert
      $scope.trashOffer = function (offerId) {
        var offer = _.findWhere(clientService.offers, {
          ID: parseInt(offerId)
        });
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
    }
  ])