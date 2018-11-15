APPOC.config(['$interpolateProvider', '$routeProvider', function ($interpolateProvider, $routeProvider) {
  $interpolateProvider.startSymbol('[[').endSymbol(']]');
  $routeProvider
    .when('/oc-candidate', {
      templateUrl: itOptions.Helper.tpls_partials + '/oc-candidate.html',
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
    .otherwise({redirectTo: '/oc-candidate'});
}])
  .filter('FormatStatus', [function () {
    let status = [
      {slug: 'pending', label: 'En attente'},
      {slug: 'validated', label: 'Confirmer'},
      {slug: 'reject', label: 'Rejeter'},
    ];
    return (entryValue) => {
      if (typeof entryValue === 'undefined') return entryValue;
      return _.findWhere(status, {slug: jQuery.trim(entryValue)}).label;
    }
  }])
  .directive('generalInformationCandidate', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/general-information-candidate.html',
      scope: {
        Candidate: '=candidate',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      controller: ['$scope', '$q', '$route', 'clientFactory', function ($scope, $q, $route, clientFactory) {
        $scope.candidateEditor = {};
        $scope.loadingEditor = false;
        $scope.status = false;

        $scope.openEditor = () => {
          $scope.loadingEditor = true;
          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
            $scope.loadingEditor = false;
            $scope.Regions = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys = _.clone(data[2]);
            const incInput = ['address', 'birthdayDate'];
            incInput.forEach((InputValue) => {
              if ($scope.Candidate.hasOwnProperty(InputValue)) {
                $scope.candidateEditor[InputValue] = _.clone($scope.Candidate[InputValue]);
              }
            });
            $scope.candidateEditor.greeting = $scope.Candidate.greeting.value;
            $scope.candidateEditor.branch_activity = $scope.Candidate.branch_activity.term_id;
            $scope.candidateEditor.region = $scope.Candidate.region.term_id;
            $scope.candidateEditor.country = $scope.Candidate.privateInformations.address.country.term_id;
            UIkit.modal('#modal-edit-candidate-overflow').show();
          });
        };

        $scope.updateCandidateInformation = () => {
          $scope.status = "Enregistrement en cours ...";
          let candidatForm = new FormData();
          let formObject = Object.keys($scope.candidateEditor);
          candidatForm.append('action', 'update_profil');
          candidatForm.append('candidate_id', parseInt($scope.Candidate.ID));
          formObject.forEach(function (property) {
            let propertyValue = Reflect.get($scope.candidateEditor, property);
            candidatForm.set(property, propertyValue);
          });
          clientFactory
            .sendPostForm(candidatForm)
            .then(resp => {
              let dat = resp.data;
              if (dat.success) {
                $scope.status = 'Votre information a bien été enregistrer avec succès';
                UIkit.modal('#modal-edit-candidate-overflow').hide();
                $route.reload();
              } else {
                $scope.status = 'Une erreur s\'est produit pendant l\'enregistrement, Veuillez réessayer ultérieurement';
              }
            });
        };

        UIkit.util.on('#modal-edit-candidate-overflow', 'hide', function (e) {
          e.preventDefault();
          e.target.blur();
          $scope.status = false;
        });
      }]
    }
  }])
  .directive('candidacy', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/candidacy.html',
      scope: {},
      link: function (scope, element, attr) {
        scope.fireData = false;
        scope.collectDatatable = () => {
          if (scope.fireData) return;
          const table = jQuery('#candidacy-table').DataTable({
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
        });
      },
      controller: ['$scope', '$http', function ($scope, $http) {
        const self = this;
        $scope.Candidatures = [];
        $scope.relaunchCandidature = (id_offer, $event) => {
          // envoyer une notification à l'entreprise pour vérifier la candidature
        };
        self.Initialize = () => {
          $http.get(`${itOptions.Helper.ajax_url}?action=get_candidacy`, {
            cache: false
          })
            .then(resp => {
              const query = resp.data;
              if (query.success) {
                $scope.Candidatures = _.clone(query.data);
              } else {
                $scope.error = query.data;
              }
            });
        };
        self.Initialize();
      }]
    }
  }])