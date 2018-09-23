angular.module('addOfferApp', ['ui.router', 'froala', 'ngMessages', 'ngAria', 'ngSanitize'])
  .value('froalaConfig', {
    toolbarInline: false,
    quickInsertTags: null,
    toolbarButtons: ['bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', 'align', 'formatOL', 'formatUL', 'indent', 'outdent', 'undo', 'redo'],
  })
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');

    const states = [
        {
          name: 'form',
          templateUrl: itOptions.partials_url + '/form.html',
          url: '/form',
          resolve: {
            abranchs: function (offerService) {
              return offerService.getBranchActivity();
            },
            regions: function (offerService) {
              return offerService.getRegions();
            },
            allCity: ['$http', function ($http) {
              return $http.get(itOptions.ajax_url + '?action=get_city', {cache: true})
                .then(function (resp) {
                  return resp.data;
                });
            }]
          },
          controller: 'formController'
        },
        {
          name: 'form.subscription',
          url: '/subscription',
          templateUrl: itOptions.partials_url + '/subscription.html',
          resolve: {
            offer : ['$q', '$rootScope', function ($q, $rootScope) {
              if (typeof $rootScope.offers === 'undefined' || _.isEmpty($rootScope.offers)) {
                return $q.reject({redirect: 'form.add-offer'});
              }
              return $q.resolve($rootScope.offers);
            }]
          },
          controller: ['$rootScope', '$scope', 'offerFactory', function ($rootScope, $scope, offerFactory) {
            $scope.ratePlan = false;
            $scope.sendSubscription = () => {
              if ($scope.ratePlan) {
                const sendData = new FormData();
                sendData.append('action', 'ajx_update_offer_rateplan');
                sendData.append('ratePlan', $scope.ratePlan);
                sendData.append('offerId', $rootScope.offers.ID);
                offerFactory
                  .sendPostForm(sendData)
                  .then(resp => {
                    const data = resp.data;
                    if (data.success) {
                      swal({
                        title: 'Reussi',
                        text: "Votre offre a été envoyé avec succès",
                        type: "info",
                      },  () => {
                        window.location.href = itOptions.urlHelper.redir;
                      });
                    }
                  });
              } else {
                window.location.href = itOptions.urlHelper.redir;
              }

            };
            $scope.$watch('ratePlan',  value => {
              console.log(value);
            }, true);
          }]
        },
        {
          name: 'form.add-offer',
          url: '/add-offer',
          templateUrl: itOptions.partials_url + '/add-offer.html',
          controller: ['$rootScope', '$scope', '$state', 'abranchs', 'regions', 'offerService', 'offerFactory',
            function ($rootScope, $scope, $state, abranchs, regions, offerService, offerFactory) {
              this.$onInit = function () {
                $scope.abranchs = _.clone(abranchs);
                $scope.regions = _.clone(regions);

                /* jQuery element */
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

                jQuery(".form-control.country").select2({
                  placeholder: "Tapez le nom de la ville",
                  allowClear: true,
                  matcher: function (params, data) {
                    var inTerm = [];
                    // If there are no search terms, return all of the data
                    if (jQuery.trim(params.term) === '') {
                      return data;
                    }

                    // Do not display the item if there is no 'text' property
                    if (typeof data.text === 'undefined') {
                      return null;
                    }

                    // `params.term` should be the term that is used for searching
                    // `data.text` is the text that is displayed for the data object

                    var dataContains = data.text.toLowerCase();
                    var paramTerms = jQuery.trim(params.term).split(' ');
                    jQuery.each(paramTerms, (index, value) => {
                      if (dataContains.indexOf(jQuery.trim(value).toLowerCase()) > -1) {
                        inTerm.push(true);
                      } else {
                        inTerm.push(false);
                      }
                    });
                    var isEveryTrue = _.every(inTerm, (boolean) => {
                      return boolean === true;
                    });
                    if (isEveryTrue) {
                      var modifiedData = jQuery.extend({}, data, true);
                      // modifiedData.text += ' (matched)';
                      return modifiedData;
                    } else {
                      return null;
                    }

                    // Return `null` if the term should not be displayed
                    return null;
                  }
                });


                jQuery('[data-toggle="tooltip"]').tooltip();
              };

              /** Valider et envoyer le formulaire */
              $scope.formSubmit = function (isValid) {
                if ($scope.formOffer.$invalid) {
                  angular.forEach($scope.formOffer.$error, function (field) {
                    angular.forEach(field, function (errorField) {
                      errorField.$setTouched();
                    });
                  });
                }
                if (!isValid) return;
                $rootScope.isSubmit = !$rootScope.isSubmit;
                var offerForm = new FormData();
                offerForm.append('action', 'ajx_insert_offers');
                offerForm.append('post', $rootScope.offers.postpromote);
                offerForm.append('ctt', $rootScope.offers.contrattype);
                offerForm.append('salary_proposed', typeof $rootScope.offers.proposedsallary === 'undefined' ? 0 : $rootScope.offers.proposedsallary);
                offerForm.append('region', parseInt($rootScope.offers.region));
                offerForm.append('country', parseInt($rootScope.offers.country));
                offerForm.append('ba', parseInt($rootScope.offers.branch_activity));
                offerForm.append('datelimit', $rootScope.offers.datelimit);
                offerForm.append('mission', $rootScope.offers.mission);
                offerForm.append('profil', $rootScope.offers.profil);
                offerForm.append('other', $rootScope.offers.otherinformation);

                offerFactory
                  .sendPostForm(offerForm)
                  .then(function (response) {
                    var data = response.data;
                    if (data.success) {
                      $rootScope.offers.ID = data.offer.ID;
                      $rootScope.isSubmit = !1;
                      $state.go('form.subscription');
                    } else {
                      $rootScope.isSubmit = !1;
                    }
                  })
              };
          }]
        }
    ];
    // Loop over the state definitions and register them
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
    $urlServiceProvider.rules.otherwise({state: 'form.add-offer'});

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
  .controller('formController', ["$state", "$scope", "$rootScope", "allCity",
    function ($state, $scope, $rootScope, allCity) {
      // Code controller here...
      $scope.froalaOptions = {
        theme: 'gray',
        placeholderText: 'Ajouter une description',
      };
      $rootScope.allCity = _.clone(allCity);
      $rootScope.isSubmit = false;
      $rootScope.offers = {};

      $rootScope.$watch('offers', function (value) {
        // Watch variable here...
      }, true);

    }])
  .run(['$state', function ($state) {
    $state.defaultErrorHandler(function (error) {
      // This is a naive example of how to silence the default error handler.
      if (error.detail !== undefined) {
        $state.go(error.detail.redirect);
      }

    });
  }]);
