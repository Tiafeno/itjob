APPOC.config(['$interpolateProvider', '$routeProvider', function ($interpolateProvider, $routeProvider) {
  $interpolateProvider.startSymbol('[[').endSymbol(']]');
  $routeProvider
    .when('/oc-company', {
      templateUrl: itOptions.Helper.tpls_partials + '/oc-company.html',
      controller: 'clientCtrl',
      resolve: {
        Client: ['$http', '$q', function ($http, $q) {
          let access = $q.defer();
          $http.get(itOptions.Helper.ajax_url + '?action=client_area', {cache: false})
            .then(resp => {
              let data = resp.data;
              access.resolve(data);
            });
          return access.promise;
        }]
      }
    })
    .otherwise({redirectTo: '/oc-company'});
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
                  headers: {'Content-Type': undefined},
                  data: formData
                })
                  .then(resp => {
                    let data = resp.data;
                    btnUpgrade.text("Votre demande a bien été envoyée");
                    $scope.sender = true;
                  });
              }, function (ev) {
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
            $http.get(itOptions.Helper.ajax_url + '?action=get_history_cv_view', {cache: true})
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