APPOC
  .config(['$interpolateProvider', '$routeProvider', function ($interpolateProvider, $routeProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');
    $routeProvider
      .when('/oc-company', {
        templateUrl: itOptions.Helper.tpls_partials + '/oc-company.html',
        controller: 'clientCtrl',
        resolve: {
          Client: ['$http', '$q', function ($http, $q) {
            let access = $q.defer();
            $http.get(itOptions.Helper.ajax_url + '?action=client_area', {
              cache: false
            })
              .then(resp => {
                let data = resp.data;
                access.resolve(data);
              });
            return access.promise;
          }]
        }
      })
      .otherwise({
        redirectTo: '/oc-company'
      });
  }])
  .directive('generalInformationCompany', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/general-information-company.html',
      scope: {
        Entreprise: '=company',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      link: function (scope, element, attrs) {
      },
      controller: ['$scope', '$q', '$route', 'clientFactory', function ($scope, $q, $route, clientFactory) {
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
                $route.reload();
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
      scope: true,
      templateUrl: itOptions.Helper.tpls_partials + '/premium-plan.html',
      link: function (scope, element, attr) {
      },
      controller: ['$scope', '$http', function ($scope, $http) {
        $scope.accountUpgrade = !$scope.Company.account;
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
      templateUrl: itOptions.Helper.tpls_partials + '/history-cv.html',
      controller: ["$scope", '$http', function ($scope, $http) {
        const loadingHistoricalElement = jQuery('#modal-history-cv-overflow').find('.loading-historical');
        loadingHistoricalElement.text('Aucun CV');
        $scope.Historicals = [];
        (function ($) {
          $('#modal-history-cv-overflow').on('show.bs.modal', function (e) {
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
      templateUrl: itOptions.Helper.tpls_partials + '/offer-lists.html',
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
          }, 1200);
          jQuery('.input-group.date').datepicker({
            format: "mm/dd/yyyy",
            language: "fr",
            startView: 2,
            todayBtn: false,
            keyboardNavigation: true,
            forceParse: false,
            autoclose: true
          });
        });
      },
      controller: ['$scope', '$http', '$q', 'clientFactory', function ($scope, $http, $q, clientFactory) {
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
          $scope.$parent.preloaderToogle();
          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
            $scope.Regions = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys = _.clone(data[2]);
            $scope.offerEditor = _.mapObject(offer, (val, key) => {
              if (typeof val.term_id !== 'undefined') return val.term_id;
              if (typeof val.post_title !== 'undefined') return val.ID;
              if (key === 'proposedSalary') return parseInt(val);
              return val;
            });
            $scope.offerEditor.contractType = parseInt($scope.offerEditor.contractType.value) === 0 ? 'cdd' : 'cdi';
            if (!_.isEmpty(offer) || !_.isNull($scope.offerEditor)) {
              $scope.$parent.preloaderToogle();
              UIkit.modal('#modal-edit-offer-overflow').show();
            }
          });
        };
        // Modifier une offre
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
              let data = resp.data;
              if (data.success) {
                // Mettre à jours la liste des offres
                $scope.Offers = _.clone(data.offers);
                UIkit.modal('#modal-edit-offer-overflow').hide();
                alertify.success("L'offre a été mise à jour avec succès");
              } else {
                alertify.error("Une erreur s'est produite pendant l'enregistrement");
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
          return item.type === "apply" && item.status === 'validated' || item.type === 'interested';
        };
        // Filtre les candidats qui ont postuler mais qui ne sont pas encore validé
        $scope.filterPostuledCandidates = (item) => {
          return item.type === 'apply' && item.status !== 'validated';
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
          $http.get(itOptions.Helper.ajax_url + '?action=get_postuled_candidate&oId=' + $scope.offerView.ID, {
            cache: false
          })
            .then(resp => {
              $scope.interestCandidats = _.map(resp.data, data => {
                if (_.find($scope.candidateLists, (candidat_id) => candidat_id === data.candidate.ID)) {
                  data.inList = true;
                } else {
                  data.inList = false;
                }
                return data;
              });
              $scope.loadingCandidats = false;
            });
        };
        $scope.viewCandidateInformation = (idCandidate) => {
          $scope.idCandidate = parseInt(idCandidate);
          $scope.mode = 'view';
        };
        // Ajouter un candidat dans la liste de l'entreprise
        $scope.addList = (id_candidate, $event) => {
          $scope.error = '';
          if (!_.isNumber(id_candidate)) return;
          var el = $event.currentTarget;
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
                  self.refreshInterestCandidate();
                });
              } else {
                $scope.error = query.data;
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

      }]
    };
  }])
  .directive('cvConsult', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/cv-consult.html',
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
          }).then(resp => {
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

          }, function (error) {
            $scope.toggleMode();
          })
        };

        $scope.onReturn = () => {
          $scope.toggleMode();
        };

        $scope.$watch('idCandidate', (id) => {
          console.log(id);
          if (id) {
            self.collectInformation();
          }
        });
      }]
    }
  }])