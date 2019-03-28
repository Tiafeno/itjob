APPOC
  .config(['$interpolateProvider', '$stateProvider', '$urlServiceProvider',
    function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
      $interpolateProvider.startSymbol('[[').endSymbol(']]');
      const states = [
        {
          name: 'manager',
          url: '/manager',
          resolve: {
            Client: ['$http', '$q', function ($http, $q) {
              let access = $q.defer();
              $http.get(itOptions.Helper.ajax_url + '?action=client_area', {cache: false})
                .then(resp => {
                  let data = resp.data;
                  access.resolve(data);
                });
              return access.promise;
            }],
            Regions: ['$http', function ($http) {
              return $http.get(itOptions.Helper.ajax_url + '?action=ajx_get_taxonomy&tax=region', {cache: true})
                .then(function (resp) {
                  return resp.data;
                });
            }],
            Towns: ['$http', function ($http) {
              return $http.get(itOptions.Helper.ajax_url + '?action=get_city', {cache: true})
                .then(function (resp) {
                  return resp.data;
                });
            }],
            Areas: ['$http', function ($http) {
              return $http.get(itOptions.Helper.ajax_url + '?action=ajx_get_taxonomy&tax=branch_activity', {cache: true})
                .then(function (resp) {
                  return resp.data;
                });
            }],
            Options: ['clientFactory', function (clientFactory) {
              return clientFactory.getOptions();
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/oc-company.html?ver=${itOptions.version}`,
          controller: 'companyController',
        },
        {
          name: 'manager.profil',
          url: '/profil',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/profil.html?ver=${itOptions.version}`,
          controller: ["$rootScope", "$state", "clientFactory", function ($rootScope, $state, clientFactory) {
            $rootScope.profilEditor = {};
            $rootScope.profilEditor.loading = false;
            $rootScope.profilEditor.form = {};

            this.$onInit = () => {
              let greeting = $rootScope.greeting;
              $rootScope.Greet = !_.isEmpty(greeting) ? $filter('Greet')($rootScope.Company.greeting).toLowerCase() : '';
              if (_.isNull($rootScope.Company.branch_activity) || !$rootScope.Company.branch_activity ||
                !$rootScope.Company.country || !$rootScope.Company.region || _.isEmpty($rootScope.Company.greeting)) {

                $rootScope.profilEditor.abranchs = _.clone($rootScope.Areas);
                $rootScope.profilEditor.regions = _.clone($rootScope.Regions);
                $rootScope.profilEditor.city = [];
                $rootScope.profilEditor.city = _.map($rootScope.Towns, (term) => {
                  term.name = `(${term.postal_code}) ${term.name}`;
                  return term;
                });

                if (!_.isEmpty($rootScope.Company.greeting)) {
                  $rootScope.profilEditor.form.greeting = $rootScope.Company.greeting;
                }
                if (!_.isNull($rootScope.Company.branch_activity) || $rootScope.Company.branch_activity) {
                  $rootScope.profilEditor.form.abranch = $rootScope.Company.branch_activity.term_id;
                }
                if (!_.isNull($rootScope.Company.region) || $rootScope.Company.region) {
                  $rootScope.profilEditor.form.region = $rootScope.Company.region.term_id;
                }

                if (!_.isNull($rootScope.Company.region) || $rootScope.Company.region) {
                  $rootScope.profilEditor.form.region = $rootScope.Company.region.term_id;
                }

                if (!_.isNull($rootScope.Company.address) || $rootScope.Company.address) {
                  $rootScope.profilEditor.form.address = $rootScope.Company.address;
                }
                UIkit.modal('#modal-information-editor').show();
                $rootScope.preloaderToogle();
              } else {
                $rootScope.preloaderToogle();
              }
            };

            // Mettre à jours les informations utilisateurs
            $rootScope.onSubmitCompanyInformation = (isValid) => {
              if (!isValid) return false;
              $rootScope.profilEditor.loading = true;
              const Form = new FormData();
              Form.append('action', 'update_company_information');
              Form.append('abranch', $rootScope.profilEditor.form.abranch);
              Form.append('region', $rootScope.profilEditor.form.region);
              Form.append('country', $rootScope.profilEditor.form.country);
              Form.append('address', $rootScope.profilEditor.form.address);
              Form.append('greet', $rootScope.profilEditor.form.greeting);
              clientFactory
                .sendPostForm(Form)
                .then(resp => {
                  let response = resp.data;
                  if (response.success) {
                    $rootScope.profilEditor.loading = false;
                    $state.reload();
                    UIkit.modal("#modal-information-editor").hide();
                  }
                }, (error) => {
                  $rootScope.profilEditor.loading = false;
                });
            };

            $rootScope.searchCityFn = (city) => {
              if (!_.isUndefined($rootScope.profilEditor.form.region)) {
                let region = parseInt($rootScope.profilEditor.form.region);
                rg = _.findWhere($rootScope.Regions, {
                  term_id: region
                });
                if (rg) {
                  if (city.name.indexOf(rg.name) > -1) {
                    return true;
                  }
                }
              }
              return false;
            };
          }]
        },
        {
          name: 'manager.profil.index',
          url: '/index',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/index.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.settings',
          url: '/settings',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/settings.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.offers',
          url: '/offers',
          resolve: {
            access: ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 1) {
                $state.go('manager.profil.index');
              }
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/offers.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.formation',
          url: '/formation',
          resolve: {
            access: ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 2) {
                $state.go('manager.profil.index');
              }
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/formation.html?ver=${itOptions.version}`,
          controller: ["$rootScope", "$state", function ($rootScope, $state) {
            this.$onInit = () => {
              $state.go("manager.profil.formation.lists");
            }
          }]
        },
        {
          name: 'manager.profil.formation.lists',
          url: '/lists',
          resolve: {
            access: ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 2) {
                $state.go('manager.profil.index');
              }
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/formation-lists.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.formation.subscription',
          url: '/{id}/subscription',
          resolve: {
            access: ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 2) {
                $state.go('manager.profil.index');
              }
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/formation-subscription.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.formation.featured',
          url: '/{id}/featured',
          resolve: {
            access: ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 2) {
                $state.go('manager.profil.index');
              }
            }],
            positions: ["$http", function ($http) {
              return $http.get(`${itOptions.Helper.ajax_url}?action=collect_support_featured&type=formation`).then(results => {
                return results.data;
              });
            }],
            $id: ['$transition$', function ($transition$) {
              return $transition$.params().id;
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/formation-featured.html?ver=${itOptions.version}`,
          controller: ["$rootScope", "$scope", "$http", "$id", "positions", function ($rootScope, $scope, $http, $id, positions) {
            $scope.Formation = {};
            $scope.supportFeatured = {};
            $scope.formationTariff = [];
            $scope.Positions = null;
            this.$onInit = () => {
              moment.locale('fr');
              $rootScope.preloaderToogle();
              $scope.supportFeatured = _.clone(positions.data);
              let featured = _.clone($rootScope.options.featured);
              $scope.formationTariff = _.map(featured.formation_tariff, (tariff) => {
                let support = _.findWhere(positions.data, {slug: tariff.ugs});
                tariff.available = support.counts >= 4 ? 0 : 1;
                return tariff;
              });
              $rootScope.WPEndpoint.formation().id($id).then(resp => {
                $scope.$apply(() => {
                  $scope.Formation = _.clone(resp);
                  $rootScope.preloaderToogle();
                });
              });
            };

            $scope.checkout = (ugs, price) => {
              const key = $rootScope.options.wc._k;
              const secret = $rootScope.options.wc._s;
              let support = _.findWhere($scope.supportFeatured, {slug: ugs});
              let priceFeatured = price.toString();
              if (!support || support.counts === 4) return false;
              $rootScope.preloaderToogle();
              let offer = _.findWhere($rootScope.options.featured.formation_tariff, {ugs: ugs});
              $rootScope.WPEndpoint
                .product()
                .param('consumer_key', key)
                .param('consumer_secret', secret)
                .create({
                  status: 'publish',
                  type: 'simple',
                  name: `FORMATION SPONSORISE (${$scope.Formation.title.rendered}) - ${offer.position}`,
                  price: offer.price, // string accepted
                  regular_price: offer.price, // string accepted
                  sold_individually: true,
                  virtual: true,
                  sku: `FEATURED${$scope.Formation.id}`,
                  meta_data: [
                    {key: '__type', value: 'featured'},
                    {key: '__id', value: $scope.Formation.id}
                  ]
                })
                .then(product => {
                  $scope.$apply(() => {
                    $rootScope.preloaderToogle();
                    $scope.addProductCart(product.id, offer.ugs);
                  });
                })
                .catch(err => {
                  if (!_.isUndefined(err.code)) {
                    if (err.code === "product_invalid_sku") {
                      let resource_id = err.data.resource_id;
                      $scope.$apply(() => {
                        $scope.WPEndpoint
                          .product()
                          .param('consumer_key', key)
                          .param('consumer_secret', secret)
                          .id(resource_id)
                          .update({price: offer.price, regular_price: offer.price})
                          .then(product => {
                            $scope.addProductCart(resource_id, offer.ugs);
                          });
                      });
                    }
                  }
                })
            };

            $scope.addProductCart = (product_id, position) => {
              $http.get(`${itOptions.Helper.ajax_url}?action=add_cart&product_id=${product_id}`, {cache: false})
                .then((resp) => {
                  let response = resp.data;
                  $scope.WPEndpoint
                    .formation()
                    .id($scope.Formation.id)
                    .update({
                      featured_position: position,
                      featured_datelimit: moment().format("YYYY-MM-DD H:mm:ss")
                    })
                    .then((formation) => {
                      if (response.success) {
                        $scope.$apply(() => {
                          $rootScope.preloaderToogle();
                        });
                        swal("Redirection", "Vous serez rediriger vers la page de paiement");
                        setTimeout(() => {
                          window.location.href = response.data;
                        }, 2000);
                      }
                    })
                })
                .then(() => {
                });
            };


          }]
        },
        {
          name: 'manager.profil.formation.paiement',
          url: '/paiement/{id}',
          resolve: {
            access: ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 2) {
                $state.go('manager.profil.index');
              }
            }],
            $id: ['$transition$', function ($transition$) {
              return $transition$.params().id;
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/formation-paiement.html?ver=${itOptions.version}`,
          controller: ["$rootScope", "$scope", "$http", "$id", function ($rootScope, $scope, $http, $id) {
            $scope.Formation = {};
            $scope.TARIFFS = [];
            this.$onInit = () => {
              moment.locale('fr');
              $rootScope.preloaderToogle();
              $rootScope.WPEndpoint.formation().id($id).then(resp => {
                $scope.Formation = _.clone(resp);
                jQuery('.form-control.date').datepicker({
                  format: "dd-mm-yyyy",
                  language: "fr",
                  startView: 2,
                  todayBtn: false,
                  keyboardNavigation: true,
                  forceParse: false,
                  autoclose: true
                });
                $rootScope.$apply(() => {
                  $rootScope.preloaderToogle();
                });
              });
              $scope.TARIFFS = _.clone($rootScope.options.pub.formation);
            };

            $scope.choisePlan = (rate) => {
              const key = $rootScope.options.wc._k;
              const secret = $rootScope.options.wc._s;
              const pubTariff = $rootScope.options.pub;
              let tariffFormation = _.findWhere(pubTariff.formation, {_id: rate});
              if (!tariffFormation) return false;
              let priceFormation = tariffFormation._p;
              swal({
                title: 'Paiement',
                text: `Il vous en coutera ${priceFormation} MGA, souhaitez-vous procéder au paiement ?`,
                type: 'info',
                showCancelButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: "Non",
                confirmButtonText: "Oui"
              }, function () {
                $rootScope.preloaderToogle();
                $scope.WPEndpoint.product()
                  .param('consumer_key', key)
                  .param('consumer_secret', secret)
                  .create({
                    status: 'publish',
                    type: 'simple',
                    name: `FORMATION (${$scope.Formation.title.rendered})`,
                    price: priceFormation, // string accepted
                    regular_price: priceFormation, // string accepted
                    sold_individually: true,
                    virtual: true,
                    sku: `FORM${$scope.Formation.id}`,
                    meta_data: [
                      {key: '__type', value: 'formation'},
                      {key: '__id', value: $scope.Formation.id}
                    ]

                  }).then(resp => {
                  let product = _.clone(resp);
                  $scope.$apply(() => {
                    $scope.addProductCart(product.id, rate);
                  });
                }).catch(err => {
                  if (!_.isUndefined(err.code)) {
                    if (err.code === "product_invalid_sku") {
                      let resource_id = err.data.resource_id;
                      tariffFormation = _.findWhere(pubTariff.formation, {_id: rate});
                      $scope.$apply(() => {
                        $scope.WPEndpoint
                          .product()
                          .param('consumer_key', key)
                          .param('consumer_secret', secret)
                          .id(resource_id)
                          .update({price: tariffFormation._p, regular_price: tariffFormation._p})
                          .then(product => {
                            $scope.addProductCart(resource_id, rate);
                          });
                      });
                    }
                  }
                });
              });
            };

            $scope.addProductCart = (product_id, rate) => {
              $http.get(`${itOptions.Helper.ajax_url}?action=add_cart&product_id=${product_id}`, {cache: false})
                .then((resp) => {
                  let response = resp.data;
                  $scope.WPEndpoint
                    .formation()
                    .id($scope.Formation.id)
                    .update({tariff: rate})
                    .then((formation) => {
                      if (response.success) {
                        $scope.$apply(() => {
                          $rootScope.preloaderToogle();
                        });
                        swal("Redirection", "Vous serez rediriger vers la page de paiement");
                        setTimeout(() => {
                          window.location.href = response.data;
                        }, 2000);
                      }
                    })
                })
                .then(() => {
                });
            };

          }]
        },
        {
          name: 'manager.profil.formation.editor',
          url: '/edit/{id}',
          resolve: {
            access: ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 2) {
                $state.go('manager.profil.index');
              }
            }],
            $id: ['$transition$', function ($transition$) {
              return $transition$.params().id;
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/formation-edit.html?ver=${itOptions.version}`,
          controller: ["$rootScope", "$scope", "$id", function ($rootScope, $scope, $id) {
            $scope.Editor = {};
            this.$onInit = () => {
              moment.locale('fr');
              $rootScope.preloaderToogle();
              $rootScope.WPEndpoint.formation().id($id).then(resp => {
                let formation = _.clone(resp);
                $scope.$apply(() => {
                  $scope.Editor = _.clone(formation);
                  $scope.Editor.region = _.isArray(formation.region) ? formation.region[0] : null;
                  $scope.Editor.title = formation.title.rendered;
                  $scope.Editor.date_limit = moment(formation.date_limit, 'YYYY-MM-DD').format('DD-MM-YYYY');
                  $scope.Editor.price = parseInt(formation.price);
                  $scope.Editor.branch_activity = _.isArray(formation.branch_activity) ? formation.branch_activity[0] : null;
                  $rootScope.preloaderToogle();
                });
                jQuery('.form-control.date').datepicker({
                  format: "dd-mm-yyyy",
                  language: "fr",
                  startView: 2,
                  todayBtn: false,
                  keyboardNavigation: true,
                  forceParse: false,
                  autoclose: true
                });
              });
            };

            $scope.submitEditFormation = (Form) => {
              if (Form.$valid && Form.$dirty) {
                $rootScope.preloaderToogle();
                $rootScope.WPEndpoint
                  .formation()
                  .id($id)
                  .update({
                    address: Form.address.$modelValue,
                    branch_activity: Form.branch_activity.$modelValue,
                    date_limit: moment(Form.date_limit.$modelValue, 'DD-MM-YYYY').format('YYYY-MM-DD'),
                    diploma: Form.diploma.$modelValue,
                    price: parseInt(Form.price.$modelValue),
                    region: Form.region.$modelValue,
                    title: Form.title.$modelValue,

                    status: "pending",
                    activated: 0
                  }).then(resp => {
                  swal({
                    title: 'Succès',
                    text: "Formation modifier avec succès ",
                    type: 'info',
                    showCancelButton: false,
                    closeOnConfirm: true,
                    confirmButtonText: "Oui"
                  }, function () {
                    $scope.$apply(() => {
                      $rootScope.preloaderToogle();
                    });
                  });

                }).catch(err => {

                });
              }
            }
          }]
        },
        {
          name: 'manager.profil.works',
          url: '/works',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/works.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.annonces',
          url: '/annonces',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/annonces.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
      ];
      // Loop over the state definitions and register them
      states.forEach(function (state) {
        $stateProvider.state(state);
      });
      $urlServiceProvider.rules.otherwise({state: 'manager.profil.index'});

    }])
  .directive('generalInformationCompany', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/general-information-company.html?ver=' + itOptions.version,
      scope: {
        Entreprise: '=company',
        init: '&init'
      },
      link: function (scope, element, attrs) {
      },
      controller: ['$rootScope', '$scope', '$q', '$state', 'clientFactory', 'clientService',
        function ($rootScope, $scope, $q, $state, clientFactory, clientService) {
          $scope.status = false;
          $scope.userEditor = {};

          this.$onInit = () => {
            $scope.Areas = _.clone($rootScope.Areas);
            $scope.Regions = _.clone($rootScope.Regions);
            $scope.Towns = _.clone($rootScope.Towns);
          };

          /**
           * Ouvrir l'editeur d'information utilisateur
           */
          $scope.openEditor = () => {
            const incInput = ['address', 'name', 'stat', 'nif'];
            const incTerm = ['branch_activity', 'region', 'country'];
            incInput.forEach((InputValue) => {
              if ($scope.Entreprise.hasOwnProperty(InputValue)) {
                $scope.userEditor[InputValue] = _.clone($scope.Entreprise[InputValue]);
              }
            });

            incTerm.forEach(TermValue => {
              if ($scope.Entreprise.hasOwnProperty(TermValue)) {
                if (!_.isUndefined($scope.Entreprise[TermValue].term_id)) {
                  $scope.userEditor[TermValue] = parseInt($scope.Entreprise[TermValue].term_id);
                } else {
                  $scope.userEditor[TermValue] = '';
                }
              }
            });
            if (!_.isEmpty($scope.userEditor)) {
              UIkit.modal('#modal-edit-user-overflow').show();
              console.log($scope.userEditor);
            }
          };

          $scope.searchCityFn = (city) => {
            if (!_.isUndefined($scope.userEditor.region)) {
              let region = parseInt($scope.userEditor.region);
              rg = _.findWhere($rootScope.Regions, {
                term_id: region
              });
              if (rg) {
                if (city.name.indexOf(rg.name) > -1) {
                  return true;
                }
              }
            }
            return false;
          };

          /**
           * Mettre à jours les informations de l'utilisateur
           */
          $scope.updateUser = () => {
            $scope.status = "Enregistrement en cours ...";
            let userForm = new FormData();
            let formObject = Object.keys($scope.userEditor);
            userForm.append('action', 'update_profil');
            userForm.append('company_id', parseInt($scope.Entreprise.ID));
            formObject.forEach(function (property) {
              let propertyValue = Reflect.get($scope.userEditor, property);
              userForm.set(property, propertyValue);
            });
            clientService.setLoading(true);
            clientFactory
              .sendPostForm(userForm)
              .then(resp => {
                let dat = resp.data;
                clientService.setLoading(false);
                if (dat.success) {
                  $scope.status = 'Votre information a bien été enregistrer avec succès';
                  UIkit.modal('#modal-edit-user-overflow').hide();
                  $state.reload();
                } else {
                  $scope.status = 'Une erreur s\'est produit pendant l\'enregistrement, Veuillez réessayer ultérieurement';
                }

              });
          };

          // Event on modal dialog close or hide
          UIkit.util.on('#modal-edit-user-overflow', 'hide', function (e) {
            e.preventDefault();
            e.target.blur();
            $scope.status = false;
          });

        }]
    }
  }])
  .directive('planPremium', [function () {
    return {
      restrict: 'E',
      scope: {},
      templateUrl: itOptions.Helper.tpls_partials + '/premium-plan.html?ver=' + itOptions.version,
      link: function (scope, element, attr) {
      },
      controller: ['$rootScope', '$scope', '$http', function ($rootScope, $scope, $http) {
        $scope.accountUpgrade = !$rootScope.Company.account;
        $scope.sender = false;
        $scope.updateAccount = () => {
          alertify
            .okBtn("Confirmer")
            .cancelBtn("Annuler")
            .confirm("Un mail sera envoyer à l'administrateur pour valider votre demande.<br> Pour plus d'informations, contactez le service commercial au:\n" +
              "<b>032 45 378 60 - 033 82 591 13 - 034 93 962 18.</b>",
              function (ev) {
                // Oui
                ev.preventDefault();
                let btnUpgrade = jQuery('#account_upgrade_btn');
                const formData = new FormData();
                formData.append('action', 'send_request_premium_plan');
                btnUpgrade.text('Chargement en cours ...');
                $http({
                  url: itOptions.Helper.ajax_url,
                  method: "POST",
                  headers: {
                    'Content-Type': undefined
                  },
                  data: formData
                })
                  .then(resp => {
                    let data = resp.data;
                    btnUpgrade.text("Votre demande a bien été envoyée");
                    $scope.sender = true;
                  });
              },
              function (ev) {
                // Annuler
                ev.preventDefault();
              });
        };
      }]
    }
  }])
  .directive('historyCv', [function () {
    return {
      restrict: "E",
      scope: true,
      templateUrl: itOptions.Helper.tpls_partials + '/history-cv.html?ver=' + itOptions.version,
      controller: ["$scope", '$http', function ($scope, $http) {
        const loadingHistoricalElement = jQuery('#modal-history-cv-overflow').find('.loading-historical');
        loadingHistoricalElement.text('Aucun CV');
        $scope.Historicals = [];
        (function ($) {
          jQuery('#modal-history-cv-overflow').on('show.bs.modal', function (e) {
            loadingHistoricalElement.hide().text('Chargement en cours ...').fadeIn();
            $http.get(itOptions.Helper.ajax_url + '?action=get_history_cv_view', {
              cache: true
            })
              .then(success => {
                let resp = success.data;
                if (resp.data.length <= 0) {
                  loadingHistoricalElement.text('Aucun CV');
                } else {
                  $scope.Historicals = _.clone(resp.data);
                  loadingHistoricalElement.hide();
                }
              });
          })
        })(jQuery)

      }]
    }
  }])
  .directive('offerLists', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/offer-lists.html?ver=' + itOptions.version,
      scope: {
        candidateLists: '=listsCandidate',
        options: '&',
        Entreprise: '=company',
        Offers: '=offers',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      link: function (scope, element, attrs) {
        scope.fireData = false;
        scope.Helper = itOptions.Helper;
        scope.collectDatatable = () => {
          if (scope.fireData) return;
          const table = jQuery('#products-table').DataTable({
            pageLength: 10,
            fixedHeader: false,
            responsive: false,
            "sDom": 'rtip',
            language: {
              url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
            }
          });
          jQuery('#key-search').on('keyup', (event) => {
            let value = event.currentTarget.value;
            table.search(value).draw();
          });
          scope.fireData = true;
        };
        angular.element(document).ready(function () {
          // Load datatable on focus search input
          jQuery('#key-search').focus(function () {
            scope.collectDatatable();
          });
          window.setTimeout(() => {
            scope.collectDatatable();
          }, 200);
          jQuery('.input-group.date').datepicker({
            format: "dd/mm/yyyy",
            language: "fr",
            startView: 2,
            todayBtn: false,
            keyboardNavigation: true,
            forceParse: false,
            startDate: new Date(),
            autoclose: true
          });
        });
      },
      controller: ['$rootScope', '$scope', '$http', '$q', '$state', 'clientFactory',
        function ($rootScope, $scope, $http, $q, $state, clientFactory) {
          let self = this;
          $scope.offerEditor = {};
          $scope.offerView = {};
          $scope.loadingCandidats = false;
          $scope.postuledCandidats = [];
          $scope.mode = "";
          $scope.error = "";
          $scope.idCandidate = 0;
          // Ouvrire une boite de dialoge pour modifier une offre
          $scope.openEditor = (offerId, $event) => {
            let offer = _.findWhere($scope.Offers, {
              ID: parseInt(offerId)
            });
            $rootScope.preloaderToogle();
            $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
              $scope.Regions = _.clone(data[0]);
              $scope.branchActivity = _.clone(data[1]);
              $scope.Citys = _.clone(data[2]);
              $scope.offerEditor = _.mapObject(offer, (val, key) => {
                if (_.isObject(val) && !_.isUndefined(val.term_id)) return val.term_id;
                if (_.isObject(val) && !_.isUndefined(val.post_title)) return val.ID;
                if (key === 'proposedSalary') return parseInt(val);
                if (key === 'dateLimit') return moment(val, 'MM/DD/YYYY').format('DD/MM/YYYY');
                return val;
              });
              $scope.offerEditor.contractType = parseInt($scope.offerEditor.contractType.value) === 0 ? 'cdd' : 'cdi';
              if (!_.isEmpty(offer) || !_.isNull($scope.offerEditor)) {
                $rootScope.preloaderToogle();
                UIkit.modal('#modal-edit-offer-overflow').show();
              }
            });
          };

          // Modifier une offre
          $scope.editOffer = (offerId, ev) => {
            let element = ev.currentTarget;
            let offerForm = new FormData();
            let formObject = Object.keys($scope.offerEditor);
            offerForm.append('action', 'update_offer');
            offerForm.append('post_id', parseInt(offerId));
            formObject.forEach(function (property) {
              let propertyValue = Reflect.get($scope.offerEditor, property);
              if (property === 'dateLimit') propertyValue = moment(propertyValue, 'DD/MM/YYYY').format('MM/DD/YYYY');
              offerForm.set(property, propertyValue);
            });
            element.innerText = "Chargement ...";
            clientFactory
              .sendPostForm(offerForm)
              .then(resp => {
                let data = resp.data;
                if (data.success) {
                  // Mettre à jours la liste des offres
                  let offers = _.clone(data.offers);
                  offers.dateLimit = moment(offers.dateLimit).format('DD/MM/YYYY');
                  $scope.Offers = offers;
                  element.innerText = "Enregistrer";
                  UIkit.modal('#modal-edit-offer-overflow').hide();
                  alertify.success("L'offre a été mise à jour avec succès");
                  $state.reload();
                } else {
                  element.innerText = "Enregistrer";
                  alertify.error("Une erreur s'est produite pendant l'enregistrement");
                }
              });
          };

          $scope.featuredOffer = () => {
            jQuery('#featured-dialog').modal('show');
          };

          // Afficher les candidates qui ont postule
          $scope.viewApply = (offer_id) => {
            $scope.mode = 'manage';
            $scope.loadingCandidats = true;
            let offer = _.find($scope.Offers, (item) => item.ID === offer_id);
            if (_.isUndefined(offer) || offer.candidat_apply.length <= 0) return;
            $scope.offerView = _.clone(offer);
            UIkit.modal('#modal-view-candidat').show();
            self.refreshInterestCandidate(offer);
          };

          // Actualiser la liste des candidats dans la gestion des candidats
          self.refreshInterestCandidate = () => {
            let query = $http.get(`${itOptions.Helper.ajax_url}?action=get_postuled_candidate&oId=${$scope.offerView.ID}`, {cache: false});
            query.then(resp => {
              $scope.interestCandidats = _.map(resp.data, data => {
                if (_.find($scope.candidateLists, (id) => id === data.candidate.ID)) {
                  data.inList = true;
                } else {
                  data.inList = false;
                }
                return data;
              });
              $scope.loadingCandidats = false;
            });
            return query;
          };

          $scope.viewCandidateInformation = (idCandidate) => {
            $scope.idCandidate = parseInt(idCandidate);
            $scope.mode = 'view';
          };

          // Ajouter un candidat dans la liste de l'entreprise
          $scope.addList = (id_candidate, $event) => {
            $scope.error = '';
            if (!_.isNumber(id_candidate)) return;
            let el = $event.currentTarget;
            angular.element(el).text("Patienter ...");
            const request = _.find($scope.interestCandidats, (it) => it.candidate.ID === id_candidate);
            $http.get(`${itOptions.Helper.ajax_url}?action=add_cv_list&id_candidate=${request.candidate.ID}&id_request=${request.id_request}`, {
              cache: false
            })
              .then(resp => {
                var query = resp.data;
                if (query.success) {
                  $http.get(`${itOptions.Helper.ajax_url}?action=get_candidat_interest_lists`, {
                    cache: false
                  }).then(response => {
                    var query = response.data;
                    $scope.candidateLists = _.clone(query.data);
                    self.refreshInterestCandidate().then(resp => {
                      $scope.viewCandidateInformation(request.candidate.ID);
                    });
                  });
                } else {
                  $scope.error = query.data;
                  angular.element(el).text("Voir la candidature");
                }
              });
          };

          // Retirer un candidat sur l'offre
          $scope.rejectCandidate = (id_candidate, $event) => {
            $scope.error = '';
            if (!_.isNumber(id_candidate)) return;
            var el = $event.currentTarget;
            angular.element(el).text("Patienter ...");
            const request = _.find($scope.interestCandidats, (it) => it.candidate.ID === id_candidate);
            $http.get(`${itOptions.Helper.ajax_url}?action=reject_cv&id_candidate=${request.candidate.ID}&id_request=${request.id_request}`, {
              cache: false
            })
              .then(resp => {
                var query = resp.data;
                if (query.success) {
                  self.refreshInterestCandidate();
                } else {
                  $scope.error = query.data;
                }
              });
          };

          // Changer le mode de view dans la boite de dialogue
          $scope.toggleMode = () => {
            $scope.mode = $scope.mode === 'view' ? 'manage' : 'view';
          };

          $scope.onGetOptions = () => {
            return $scope.options();
          };

          $scope.collectFilterResults = (methode) => {
            return methode === 'filter_selected_candidate' ? _.filter($scope.interestCandidats, (item) => $scope.filterSelectedCandidates(item)) :
              _.filter($scope.interestCandidats, (item) => $scope.filterPostuledCandidates(item));
          };
          // Filtrer les candidats qui sont selectionner et qui sont valider pour postuler
          $scope.filterSelectedCandidates = (item) => {
            return item.type === "interested";
          };
          // Filtre les candidats qui ont postuler mais qui ne sont pas encore validé
          $scope.filterPostuledCandidates = (item) => {
            return item.type === 'apply' && item.view === 1;
          };

        }]
    };
  }])
  .directive('cvConsult', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/cv-consult.html?ver=' + itOptions.version,
      scope: {
        Company: '=company',
        Offer: '=offer',
        idCandidate: '=idCandidate',
        toggleMode: '&onToggleMode',
        Options: '&onOptions'
      },
      controller: ['$scope', '$http', function ($scope, $http) {
        $scope.loading = true;
        $scope.Candidate = {};
        $scope.Attachment = {};
        $scope.interestLink = '';
        this.$onInit = () => {
          $scope.Options = $scope.Options();
        };
        const self = this;

        self.collectInformation = () => {
          $scope.loading = true;
          $http({
            url: `${itOptions.Helper.ajax_url}?action=collect_favorite_candidates&id=${$scope.idCandidate}&id_offer=${$scope.Offer.ID}`,
            method: "GET",
          }).then(
            resp => {
              let query = resp.data;
              if (query.success) {
                const informations = query.data;
                const user_token = $scope.Company.author.data.user_pass;
                $scope.Candidate = _.clone(informations.candidate);
                $scope.Attachment = _.clone(informations.attachment);
                $scope.interestLink = `${$scope.Options.Helper.interest_page_uri}?token=${user_token}&cvId=${$scope.Candidate.ID}`;
                $scope.loading = false;
              } else {
                $scope.toggleMode();
              }

            },
            error => {
              $scope.toggleMode();
            })
        };

        $scope.onReturn = () => {
          $scope.toggleMode();
        };

        $scope.$watch('idCandidate', (id) => {
          if (id) {
            self.collectInformation();
          }
        });
      }]
    }
  }])
  .directive('appFormation', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/formation.html?ver=' + itOptions.version,
      scope: true,
      controller: ['$rootScope', '$scope', '$q', '$http', '$state', function ($rootScope, $scope, $q, $http, $state) {
        $scope.Formations = [];
        $scope.WPEndpoint = null;
        $scope.Loading = false;
        $scope.Table = null;
        $scope.getDataTableColumn = (ev) => {
          let el = jQuery(ev.currentTarget).parents('tr');
          let Column = $scope.Table.row(el).data();
          return Column;
        };
        this.$onInit = () => {
          moment.locale('fr');

          let origin = document.location.origin;
          $scope.WPEndpoint = new WPAPI({endpoint: `${origin}/wp-json`});
          let namespace = 'wp/v2'; // use the WP API namespace
          let wc_namespace = 'wc/v3'; // use the WOOCOMMERCE API namespace
          let route_formation = '/formation/(?P<id>\\d+)';
          let route_product = '/products/(?P<id>\\d+)';
          $scope.WPEndpoint.setHeaders({'X-WP-Nonce': `${WP.nonce}`});
          $scope.WPEndpoint.formation = $scope.WPEndpoint.registerRoute(namespace, route_formation);
          $scope.WPEndpoint.product = $scope.WPEndpoint.registerRoute(wc_namespace, route_product);

          $scope.Loading = true;
          $http.get(`${itOptions.Helper.ajax_url}?action=collect_formations`, {cache: false})
            .then(resp => {
              const query = resp.data;
              if (query.success) {
                $scope.Table = jQuery('#formation-table').DataTable({
                  pageLength: 10,
                  fixedHeader: false,
                  responsive: false,
                  dom: '<"top"i><"info"r>t<"bottom"flp><"clear">',
                  data: query.data,
                  columns: [
                    {data: 'reference'},
                    {data: 'title'},
                    {
                      data: 'status', render: (data, type, row) => {
                        var text = data === 'pending' && !row.activation ? 'En attente' :
                          (row.activation && data === 'publish' ? 'Validée' : 'Désactiver');
                        var style = data === 'publish' && row.activation ? 'blue' : 'pink';
                        return `<span class="badge badge-pill badge-${style}"> ${text} </span>`;
                      }
                    },
                    {
                      data: 'paid', render: (data, type, row) => {
                        let elClass = "";
                        let value, text, style;
                        if (data && row.activation && row.status === "publish") {
                          text = 'Terminée';
                          style = "success";
                        } else if (data && !row.activation && row.status === 'pending') {
                          text = 'Terminée';
                          style = "success";
                        } else if (row.status == 'publish' && !row.activation) {
                          text = 'Annulée';
                          style = "pink";
                        } else if (row.status === 'pending' && !data) {
                          text = "Attente publication";
                          style = "default";
                        } else if (row.activation && !data) {
                          text = "Attente paiement";
                          style = "info";
                          elClass += "paiement-process";
                        }

                        return `<span class="badge badge-pill badge-${style} ${elClass}"> ${text} </span>`;
                      }
                    },
                    {
                      data: 'featured', render: (data, type, row) => {
                        let text = data ? row.featured_position : 'AUCUN';
                        let style = data ? "success" : "default";
                        let elClass = style === 'default' ? 'featured-paiement' : '';
                        return `<span class="badge edit-position badge-pill ${elClass} badge-${style}"> ${text} </span>`;
                      }
                    },
                    {
                      data: 'date_limit', render: (data) => {
                        return moment(data).format('LLL');
                      }
                    },
                    {
                      data: null, render: () => {
                        return '<span class="edit-formation icon-pill"><i class="fa fa-pencil"></i></span>' +
                          '<span class="view-candidate ml-2 icon-pill"><i class="fa fa-address-card"></i></span>';
                      }
                    }
                  ],
                  initComplete: (setting, json) => {
                    // Modifier une formation
                    jQuery('#formation-table tbody').on('click', '.edit-formation', ev => {
                      ev.preventDefault();
                      let Formation = $scope.getDataTableColumn(ev);
                      $state.go('manager.profil.formation.editor', {id: Formation.ID});
                    });

                    // Voir les inscriptions dans la formation
                    jQuery('#formation-table tbody').on('click', '.view-candidate', ev => {
                      ev.preventDefault();
                      let Formation = $scope.getDataTableColumn(ev);
                      $state.go('manager.profil.formation.subscription', {id: Formation.ID});
                    });

                    // Paiement de la formation
                    jQuery('#formation-table tbody').on('click', '.paiement-process', ev => {
                      ev.preventDefault();
                      let Formation = $scope.getDataTableColumn(ev);
                      $state.go('manager.profil.formation.paiement', {id: Formation.ID});
                    });

                    // Paiement formation à la une
                    jQuery('#formation-table tbody').on('click', '.featured-paiement', ev => {
                      ev.preventDefault();
                      let Formation = $scope.getDataTableColumn(ev);
                      if (!Formation.paid) {
                        swal('Validation', "Pour effectuer cette action vous devez payer le frais d'insertion pour votre annonce. Merci", 'info');
                        return false;
                      }
                      $state.go('manager.profil.formation.featured', {id: Formation.ID});
                    });
                  },
                  "sDom": 'rtip',
                  language: {
                    url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
                  }
                });
                $scope.Loading = false;
              } else {
                $scope.loading = false;
              }
            });

        };

      }]
    }
  }])
  .directive('settingsCompany', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/settings-company.html?ver=' + itOptions.version,
      scope: true,
      controller: ["$scope", "$rootScope", "clientFactory",
        function ($scope, $rootScope, clientFactory) {
          $scope.options = {};
          $scope.loading = false;
          this.$onInit = () => {
            $scope.options.newsletter = _.clone($rootScope.Company.newsletter);
            $scope.options.notification = _.clone($rootScope.Company.notification);
          };

          $scope.saveSettings = (Form) => {
            if (Form.$valid && Form.$dirty) {
              let Fm = new FormData();
              Fm.append('action', 'update_settings');
              Fm.append('newsletter', Form.newsletter.$modelValue ? 1 : 0);
              Fm.append('notification', Form.notification.$modelValue ? 1 : 0);
              $scope.loading = true;
              clientFactory.sendPostForm(Fm).then(resp => {
                let response = resp.data;
                if (response.success) {
                  $scope.loading = false;
                } else {
                  $scope.loading = false;
                }
              });
            }
          }
        }]
    }
  }])
  .controller('companyController', ['$rootScope', '$http', '$q', '$filter', 'clientFactory',
    'clientService', 'Client', 'Regions', 'Towns', 'Areas', 'Options',
    function ($rootScope, $http, $q, $filter, clientFactory, clientService, Client, Regions, Towns, Areas, Options) {
      const self = this;
      $rootScope.WPEndpoint = null;
      $rootScope.options = {};
      $rootScope.sector = 0;
      $rootScope.formationCount = 0;
      $rootScope.alertLoading = false; // Directive alert
      $rootScope.alerts = [];
      $rootScope.Helper = {};
      $rootScope.Greet = '';
      $rootScope.preloader = false;
      $rootScope.select2Options = {
        allowClear: true,
        placeholder: "Selectionner",
        width: 'resolve',
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
            //modifiedData.text += ' (Trouver)';
            return modifiedData;
          } else {
            // Return `null` if the term should not be displayed
            return null;
          }
        }
      };
      // Company
      $rootScope.Company = {}; // Contient l'information de l'utilisateur
      $rootScope.offerLists = []; // Contient les offres de l'entreprise
      $rootScope.candidateLists = []; // Contient la list des candidates interesser par l'entreprise

      $rootScope.Regions = [];
      $rootScope.Areas = [];
      $rootScope.Towns = [];

      this.$onInit = () => {
        var $ = jQuery.noConflict();
        $rootScope.preloaderToogle();
        $rootScope.Company = _.clone(Client.iClient);
        $rootScope.Helper = _.clone(Client.Helper);
        $rootScope.offerLists = _.clone(Client.Offers);
        $rootScope.candidateLists = _.clone(Client.ListsCandidate);
        $rootScope.options = _.clone(Options);

        $rootScope.Regions = _.clone(Regions);
        $rootScope.Areas = _.clone(Areas);
        $rootScope.Towns = _.clone(Towns);

        $rootScope.formationCount = !_.isUndefined(Client.formation_count) ? Client.formation_count : 0;
        $rootScope.sector = _.clone(Client.iClient.sector);
        $rootScope.alerts = _.reject(Client.Alerts, alert => _.isEmpty(alert));


        let origin = document.location.origin;
        $rootScope.WPEndpoint = new WPAPI({endpoint: `${origin}/wp-json`});
        let namespace = 'wp/v2'; // use the WP API namespace
        let wc_namespace = 'wc/v3'; // use the WOOCOMMERCE API namespace
        let route_formation = '/formation/(?P<id>\\d+)';
        let route_product = '/products/(?P<id>\\d+)';
        $rootScope.WPEndpoint.setHeaders({'X-WP-Nonce': `${WP.nonce}`});
        $rootScope.WPEndpoint.formation = $rootScope.WPEndpoint.registerRoute(namespace, route_formation);
        $rootScope.WPEndpoint.product = $rootScope.WPEndpoint.registerRoute(wc_namespace, route_product);
      };


      /**
       * Récuperer les terms d'une taxonomie
       * @param {string} Taxonomy
       */
      $rootScope.asyncTerms = (Taxonomy) => {
        if (Taxonomy !== 'city') {
          return $http.get(`${itOptions.Helper.ajax_url}?action=ajx_get_taxonomy&tax=${Taxonomy}`, {
            cache: true
          }).then(resp => resp.data);
        } else {
          return clientFactory.getCity();
        }
      };


      $rootScope.preloaderToogle = () => {
        $rootScope.preloader = !$rootScope.preloader;
      };

      /**
       * Mettre a jour les alerts (Ajouter, Supprimer)
       * Une alerte permet de notifier l'utilisateur par email
       * Si une publication (offre, annonce, travaille temporaire) comportent ces mots
       */
      $rootScope.onSaveAlert = () => {
        if (_.isEmpty($rootScope.alerts)) return;
        $rootScope.alertLoading = true;
        var form = new FormData();
        form.append('action', 'update_alert_filter');
        form.append('alerts', JSON.stringify($rootScope.alerts));
        $http({
          method: 'POST',
          url: itOptions.Helper.ajax_url,
          headers: {
            'Content-Type': undefined
          },
          data: form
        })
          .then(response => {
            // Handle success
            let data = response.data;
            $rootScope.alertLoading = false;
            if (!data.success) {
              alertify.error("Une erreur inconue s'est produit")
            } else {
              alertify.success('Enregistrer avec succès')
            }
          }, error => {
            alertify.error("Une erreur s'est produite,  veuillez réessayer ultérieurement.");
            $rootScope.alertLoading = false;
          });
      };

      $rootScope.getOptions = () => {
        return {
          Helper: $rootScope.Helper
        };
      };

      /**
       * Envoyer une offre dans la corbeille
       * @param {int} offerId
       */
      $rootScope.trashOffer = function (offerId) {
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
      };
    }])