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
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/oc-company.html?ver=${itOptions.version}`,
          controller: 'companyController',
        },
        {
          name: 'manager.profil',
          url: '/profil',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/profil.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

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
          resolve : {
            access : ['$rootScope', '$state', function ($rootScope, $state) {
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
          name: 'manager.profil.formations',
          url: '/formations',
          resolve : {
            access : ['$rootScope', '$state', function ($rootScope, $state) {
              if ($rootScope.sector !== 2) {
                $state.go('manager.profil.index');
              }
            }]
          },
          templateUrl: `${itOptions.Helper.tpls_partials}/route/company/formation.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

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
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      link: function (scope, element, attrs) {
      },
      controller: ['$scope', '$q', '$state', 'clientFactory', function ($scope, $q, $state, clientFactory) {
        $scope.status = false;
        $scope.userEditor = {};

        /**
         * Ouvrir l'editeur d'information utilisateur
         */
        $scope.openEditor = () => {
          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
            $scope.Regions = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys = _.clone(data[2]);
            const incInput = ['address', 'name', 'stat', 'nif'];
            const incTerm = ['branch_activity', 'region', 'country'];
            incInput.forEach((InputValue) => {
              if ($scope.Entreprise.hasOwnProperty(InputValue)) {
                $scope.userEditor[InputValue] = _.clone($scope.Entreprise[InputValue]);
              }
            });
            incTerm.forEach(TermValue => {
              if ($scope.Entreprise.hasOwnProperty(TermValue)) {
                if (typeof $scope.Entreprise[TermValue].term_id !== 'undefined') {
                  $scope.userEditor[TermValue] = $scope.Entreprise[TermValue].term_id;
                } else {
                  $scope.userEditor[TermValue] = '';
                }
              }
            });
            if (!_.isEmpty($scope.userEditor)) {
              $scope.userEditor.greeting = $scope.Entreprise.greeting.value;
              UIkit.modal('#modal-edit-user-overflow').show();
            }
          });
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
          clientFactory
            .sendPostForm(userForm)
            .then(resp => {
              let dat = resp.data;
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
      controller: ['$scope', '$q', '$http', function ($scope, $q, $http) {
        $scope.Formations = [];
        $scope.Loading = false;
        self.Initialize = () => {
          $scope.Loading = true;
          $http.get(`${itOptions.Helper.ajax_url}?action=collect_formations`, {
            cache: false
          })
            .then(resp => {
              const query = resp.data;
              if (query.success) {
                moment.locale('fr');
                const table = jQuery('#formation-table').DataTable({
                  pageLength: 10,
                  fixedHeader: false,
                  responsive: false,
                  dom: '<"top"i><"info"r>t<"bottom"flp><"clear">',
                  data: query.data,
                  columns: [
                    {data: 'reference'},
                    {data: 'status', render: (data, type, row, meta) => {
                        var text =  data === 'pending' ? 'En attente' : 'Publier';
                        return `<span class="badge badge-pill badge-default"> ${text} </span>`;
                      }},
                    {data: 'title'},
                    {data: 'date_limit', render: (data) => { return moment(data).format('LL'); }},
                    {data: 'establish_name'},
                    {data: null, render: () => {
                        return '<i class="fa fa-pencil"></i>';
                      }}
                  ],
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
        self.Initialize();
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
    'clientService', 'Client', 'Regions', 'Towns', 'Areas',
    function ($rootScope, $http, $q, $filter, clientFactory, clientService, Client, Regions, Towns, Areas) {
      const self = this;

      $rootScope.sector = 0;
      $rootScope.formationCount = 0;
      $rootScope.profilEditor = {};
      $rootScope.profilEditor.loading = false;
      $rootScope.profilEditor.form = {};
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

      self.$onInit = () => {
        var $ = jQuery.noConflict();
        $rootScope.preloaderToogle();
        $rootScope.Company = _.clone(Client.iClient);
        $rootScope.Helper  = _.clone(Client.Helper);
        $rootScope.offerLists = _.clone(Client.Offers);
        $rootScope.candidateLists = _.clone(Client.ListsCandidate);

        let greeting = $rootScope.greeting;
        $rootScope.Greet = !_.isEmpty(greeting) ? $filter('Greet')($rootScope.Company.greeting).toLowerCase() : '';
        if (_.isNull($rootScope.Company.branch_activity) || !$rootScope.Company.branch_activity ||
          !$rootScope.Company.country || !$rootScope.Company.region || _.isEmpty($rootScope.Company.greeting)) {

          $rootScope.profilEditor.abranchs = _.clone(Areas);
          $rootScope.profilEditor.regions  = _.clone(Regions);
          $rootScope.profilEditor.city = [];
          $rootScope.profilEditor.city = _.map(Towns, (term) => {
            term.name = `(${term.postal_code}) ${term.name}`;
            return term;
          });

          if (!_.isEmpty($rootScope.Company.greeting)) {
            $rootScope.profilEditor.form.greeting = $rootScope.Company.greeting.value;
          }
          if (!_.isNull($rootScope.Company.branch_activity) || $rootScope.Company.branch_activity) {
            $rootScope.profilEditor.form.abranch = $rootScope.Company.branch_activity.term_id;
          }
          if (!_.isNull($rootScope.Company.region) || $rootScope.Company.region) {
            $rootScope.profilEditor.form.region = $rootScope.Company.region.term_id;
          }
          UIkit.modal('#modal-information-editor').show();
          $rootScope.preloaderToogle();
        } else {
          $rootScope.preloaderToogle();
        }
        $rootScope.formationCount = !_.isUndefined(Client.formation_count) ? Client.formation_count : 0;
        $rootScope.sector = _.clone(Client.iClient.sector);
        $rootScope.alerts = _.reject(Client.Alerts, alert => _.isEmpty(alert));
      };

      // Mettre à jours les informations utilisateurs
      $rootScope.onSubmitCompanyInformation = (isValid) => {
        if (!isValid) return false;
        $rootScope.profilEditor.loading = true;
        const Form = new FormData();
        Form.append('action', 'update_company_information');
        Form.append('abranch', $rootScope.profilEditor.form.abranch);
        Form.append('region',  $rootScope.profilEditor.form.region);
        Form.append('country', $rootScope.profilEditor.form.country);
        Form.append('address', $rootScope.profilEditor.form.address);
        Form.append('greet',   $rootScope.profilEditor.form.greeting);
        clientFactory
          .sendPostForm(Form)
          .then(resp => {
            let response = resp.data;
            if (response.success) {
              $rootScope.profilEditor.loading = false;
              setTimeout(() => {
                location.reload(true);
              }, 1200);
            }
          }, (error) => {
            $rootScope.profilEditor.loading = false;
          });
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