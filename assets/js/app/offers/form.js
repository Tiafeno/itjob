angular.module('addOfferApp', ['ui.router', 'ui.tinymce', 'ngMessages', 'ngAria'])
  .value('alertifyConfig', {
    notifier: {
      delay: 5,
      position: 'top-right',
      closeButton: true
    }
  })
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');
    const states = [
      {
        name: 'form',
        templateUrl: itOptions.helper.partials_url + '/form.html?ver=' + itOptions.version,
        url: '/form',
        resolve: {
          abranchs: ['offerService', function (offerService) {
            return offerService.getBranchActivity();
          }],
          regions: ['offerService', function (offerService) {
            return offerService.getRegions();
          }],
          allCity: ['$http', function ($http) {
            return $http.get(itOptions.ajax_url + '?action=get_city', {
              cache: true
            })
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
        templateUrl: itOptions.helper.partials_url + '/subscription.html?ver=' + itOptions.version,
        resolve: {
          offer: ['$q', '$rootScope', function ($q, $rootScope) {
            // for test
            // return $q.resolve(true);
            if (typeof $rootScope.offers === 'undefined' || _.isEmpty($rootScope.offers)) {
              return $q.reject({
                redirect: 'form.add-offer'
              });
            }
            return $q.resolve($rootScope.offers);
          }],
          options: ['$http', function ($http) {
            return $http.get(itOptions.helper.rest_url_options, {cache: true}).then(resp => {
              return resp.data;
            }, error => false);
          }]
        },
        controller: ['$rootScope', '$scope', 'offerFactory', 'options', function ($rootScope, $scope, offerFactory, options) {
          // Mode de diffusion par default
          $scope.loading = false;
          $scope.rateplan = 'standard';
          $scope.Options = {};
          $scope.Price = {};
          this.$onInit = () => {
            $scope.Options = _.clone(options);
            $scope.Price.serein = _.findWhere(options.pub.offer, {_id: "serein"});
            $scope.Price.premium = _.findWhere(options.pub.offer, {_id: "premium"});
          };
          $scope.sendSubscription = () => {
            $scope.loading = true;
            const sendData = new FormData();
            sendData.append('action', 'ajx_update_offer_rateplan');
            sendData.append('rateplan', $scope.rateplan);
            sendData.append('offerId', $rootScope.offers.ID);
            offerFactory
              .sendPostForm(sendData)
              .then(resp => {
                const data = resp.data;
                if (data.success) {
                  swal({
                    title: 'Reussi',
                    text: "Votre offre a été enregistré avec succès et en cours de validation. " +
                    "Nous vous enverrons une notification quand elle sera prête. merci",
                    type: "info",
                  }, () => {
                    window.location.href = itOptions.helper.redir_url;
                  });
                }
                $scope.loading = false;
              });
          };

          // Activate Popovers
          jQuery('[data-toggle="popover"]').popover();
        }]
      },
      {
        name: 'form.add-offer',
        url: '/add-offer',
        templateUrl: itOptions.helper.partials_url + '/add-offer.html?ver=' + itOptions.version,
        controller: ['$rootScope', '$scope', '$state', 'abranchs', 'regions', 'offerFactory',
          function ($rootScope, $scope, $state, abranchs, regions, offerFactory) {
            this.$onInit = function () {
              moment.locale("fr");
              $scope.abranchs = _.clone(abranchs);
              $scope.regions = _.clone(regions);
              $scope.tinymceOptions = {
                language: 'fr_FR',
                menubar: false,
                plugins: ['lists', 'paste'],
                theme_advanced_buttons3_add: "pastetext,pasteword,selectall",
                paste_auto_cleanup_on_paste: true,
                paste_remove_styles_if_webkit: true,
                paste_remove_styles: true,
                paste_postprocess: function (pl, o) {
                  // Content DOM node containing the DOM structure of the clipboard

                },
                content_css: [
                  '//fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i'
                ],
                selector: 'textarea',
                toolbar: 'undo redo | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat '
              };
              $rootScope.searchCityFn = (city) => {
                let rg;
                if (!_.isUndefined($rootScope.offers.region)) {
                  let region = parseInt($rootScope.offers.region);
                  rg = _.findWhere($scope.regions, {
                    term_id: region
                  });
                  if (rg) {
                    if (city.name.toLowerCase().indexOf(rg.name.toLowerCase()) > -1) {
                      return true;
                    }
                  }
                }
                return false;
              };
              /* jQuery element */
              var jqSelects = jQuery("select.form-control:not(.no-search)");
              jQuery.each(jqSelects, function (index, element) {
                var selectElement = jQuery(element);
                var placeholder = (selectElement.attr('title') === undefined) ? 'Please select' : selectElement.attr('title');
                jQuery(element).select2({
                  placeholder: placeholder,
                  allowClear: true,
                  width: '100%'
                })
              });
              jQuery("select.form-control.no-search").select2({
                minimumResultsForSearch: -1
              });
              jQuery(".form-control.country").select2({
                placeholder: "Tapez le nom d'une ville ou code postal",
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

            $scope.formSubmit = function (isValid) {
              if ($scope.formOffer.$invalid) {
                angular.forEach($scope.formOffer.$error, function (field) {
                  angular.forEach(field, function (errorField) {
                    errorField.$setTouched();
                  });
                });
              }
              if (!isValid) {
                alertify
                  .error("Une erreur s’est produite. Veuillez remplir correctement les champs requis");
                return false;
              }
              $rootScope.isSubmit = !$rootScope.isSubmit;
              const offerForm = new FormData();
              let otherInfo = $rootScope.offers.otherinformation;
              otherInfo = _.isUndefined(otherInfo) ? '' : otherInfo;
              offerForm.append('action', 'ajx_insert_offers');
              offerForm.append('post', $rootScope.offers.postpromote);
              offerForm.append('ctt', $rootScope.offers.contrattype);
              offerForm.append('salary_proposed', _.isUndefined($rootScope.offers.proposedsallary) ? 0 : $rootScope.offers.proposedsallary);
              offerForm.append('region', parseInt($rootScope.offers.region));
              offerForm.append('country', parseInt($rootScope.offers.country));
              offerForm.append('ba', parseInt($rootScope.offers.branch_activity));
              offerForm.append('datelimit', $rootScope.offers.datelimit);
              offerForm.append('mission', $rootScope.offers.mission);
              offerForm.append('profil', $rootScope.offers.profil);
              offerForm.append('other', otherInfo);
              offerFactory
                .sendPostForm(offerForm)
                .then(function (response) {
                  let data = response.data;
                  if (data.success) {
                    $rootScope.offers.ID = data.offer.ID;
                    $rootScope.isSubmit = !1;
                    $state.go('form.subscription');
                  } else {
                    $rootScope.isSubmit = !1;
                  }
                })
            };
          }
        ]
      }
    ];
    // Loop over the state definitions and register them
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
    $urlServiceProvider.rules.otherwise({
      state: 'form.add-offer'
    });

  })
  .service('offerService', ['$http', function ($http) {
    return {
      getBranchActivity: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_branch_activity', {
          cache: true
        })
          .then(function (resp) {
            return resp.data;
          });
      },
      getRegions: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=region', {
          cache: true
        })
          .then(function (r) {
            return r.data;
          });
      }
    }
  }])
  .factory('offerFactory', ['$http', function ($http) {
    return {
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.ajax_url,
          method: "POST",
          headers: {
            'Content-Type': undefined
          },
          data: formData
        });
      }
    };
  }])
  .filter('currencyFormat', function () {
    return function (number) {
      var value = parseFloat(number);
      if (_.isNaN(value)) return number;
      return new Intl.NumberFormat('de-DE', {}).format(value);
    }
  })
  .controller('formController', ["$state", "$scope", "$rootScope", "allCity",
    function ($state, $scope, $rootScope, allCity) {
      // Code controller here...
      $rootScope.allCity = _.clone(allCity);
      $rootScope.isSubmit = false;
      $rootScope.offers = {};
    }
  ])
  .run(['$state', 'alertifyConfig', function ($state, alertifyConfig) {
    alertify.defaults = alertifyConfig;
    $state.defaultErrorHandler(function (error) {
      // This is a naive example of how to silence the default error handler.
      if (error.detail !== undefined) {
        $state.go(error.detail.redirect);
      }

    });
  }]);