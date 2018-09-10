angular.module('addOfferApp', ['ui.router', 'froala', 'ngMessages', 'ngAria', 'ngSanitize'])
  .value('froalaConfig', {
    toolbarInline: false,
    quickInsertTags: null,
    toolbarButtons: ['bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', 'align', 'formatOL', 'formatUL', 'indent', 'outdent', 'undo', 'redo'],
  })
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
  .component('formComponent', {
    bindings: {abranchs: '<', regions: '<'},
    templateUrl: itOptions.partials_url + '/form.html',
    controller: ["$location", "$scope", "offerData", "offerService", "offerFactory",
      function ($location, $scope, offerData, offerService, offerFactory) {
        // Code controller here...
        $scope.froalaOptions = {
          theme: 'gray',
          placeholderText: 'Ajouter une description',
        };
        $scope.isSubmit = false;
        $scope.offers = {};
        $scope.formSubmit = function (isValid) {
          if ($scope.formOffer.$invalid) {
            angular.forEach($scope.formOffer.$error, function (field) {
              angular.forEach(field, function (errorField) {
                errorField.$setTouched();
              });
            });
          }
          if (!isValid) return;
          $scope.isSubmit = !$scope.isSubmit;
          offerData.formOfferValue = _.clone($scope.offers);
          var offerForm = new FormData();
          offerForm.append('action', 'ajx_insert_offers');
          offerForm.append('post', $scope.offers.postpromote);
          // offerForm.append('reference', $scope.offers.reference);
          offerForm.append('ctt', $scope.offers.contrattype);
          offerForm.append('salary_proposed', $scope.offers.proposedsallary);
          offerForm.append('region', parseInt($scope.offers.region));
          offerForm.append('ba', parseInt($scope.offers.branch_activity));
          offerForm.append('datelimit', $scope.offers.datelimit);
          offerForm.append('mission', $scope.offers.mission);
          offerForm.append('profil', $scope.offers.profil);
          offerForm.append('other', $scope.offers.otherinformation);

          offerFactory
            .sendPostForm(offerForm)
            .then(function (response) {
              var data = response.data;
              if (data.success) {
                offerData.setMessage('Info', 'Offre publier avec succes');
                $location.path('/confirmation');
              } else {
                $scope.isSubmit = !1;
              }
            })
        };
        // Listening offers variable scope
        $scope.$watch('offers', function (value) {
          // Watch variable here...

        }, true);

        var jqSelects = jQuery("select.form-control");
        jQuery.each(jqSelects, function (index, element) {
          var selectElement = jQuery(element);
          var placeholder = (selectElement.attr('title') === undefined) ? 'Please select' : selectElement.attr('title');
          jQuery(element).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%'
          })
        });
        jQuery('[data-toggle="tooltip"]').tooltip();
      }]
  })
  .component('confComponent', {
    templateUrl: itOptions.partials_url + '/confirmation.html',
    controller: ["$scope", 'offerData', function ($scope, offerData) {
      this.message = _.clone(offerData.message);
    }]
  })
  .run();
