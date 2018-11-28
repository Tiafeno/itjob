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
  .filter('moment', [function () {
    return (entry) => {
      if (_.isEmpty(entry)) return entry;
      return moment(entry, "MM/DD/YYYY", "fr").format("MMMM, YYYY");
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
  .directive('jobSearch', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/job-search.html',
      scope: {
        inJobs: "=jobs"
      },
      controller: ['$scope', '$http', function ($scope, $http) {
        $scope.isValidTag = true;
        $scope.jobs = [];
        $scope.jobLoading = false;
        this.$onInit = () => {
          $scope.jobs = _.clone($scope.inJobs);
        };
        // Call before added tag
        $scope.onAddingTag = ($tag) =>
        {
          if ($scope.jobs.length >= 2) return false;
          let isValid = true;
          let splitTag = '|;_\/*';
          for (let i in splitTag) {
            let str = splitTag.charAt(i);
            if ($tag.name.indexOf(str) > -1) { isValid = false; break; }
          }
          if (isValid) $scope.isValidTag = true;
          return isValid;
        };

        // Call if tag in invalid
        $scope.onTagInvalid = ($tag) => { $scope.isValidTag = false; };

        $scope.onSave = () =>
        {
          if (!$scope.isValidTag) return false;
          $scope.jobLoading = true;
          var form = new FormData();
          form.append('action', 'update_job_search');
          form.append('jobs', JSON.stringify($scope.jobs));
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
              $scope.jobLoading = false;
              if (!data.success) {
                alertify.error("Une erreur inconue s'est produit")
              } else {
                alertify.success('Enregistrer avec succès')
              }
            });
        };

        /**
         * Recuperer les emplois et les filtres
         * @param {string} $query
         * @param {string} taxonomy
         */
        $scope.queryJobs = function ($query, taxonomy) {
          return $http.get(itOptions.Helper.ajax_url + '?action=ajx_get_taxonomy&tax=' + taxonomy, {
            cache: true
          })
            .then(function (response) {
              const jobs = response.data;
              return jobs.filter(function (job) {
                return job.name.toLowerCase().indexOf($query.toLowerCase()) != -1;
              });
            });
        };

      }]
    }
  }])
  .directive('addSoftwares', [function () {
    return {
      restrict: "E",
      templateUrl: itOptions.Helper.tpls_partials + '/add-softwares.html',
      scope: {
        softwares: "=",
        softwareTerms: "&softwareTerms"
      },
      controller: ["$scope", "$q", "$http", function ($scope, $q, $http) {
        $scope.softwareLists = [];
        $scope.status = '';
        $scope.form = {};
        $scope.form.softwares = [];
        $scope.loading = false;

        this.$onInit = () => {
          $scope.form.softwares = $scope.softwareLists = _.clone($scope.softwares);
          console.info('Init collect softwares');
        };

        $scope.submitForm = (isValid) => {
          if (!isValid) return;
          $scope.loading = true;
          const Form = new FormData();
          Form.append('action', "update_candidate_softwares");
          Form.append('softwares', JSON.stringify($scope.form.softwares));
          $http({
            method: 'POST',
            url: itOptions.Helper.ajax_url,
            headers: {
              'Content-Type': undefined
            },
            data: Form
          })
            .then(response => {
              let data = response.data;
              $scope.loading = false;
              if (!data.success) {
                $scope.status = data.data;
              } else {
                alertify.success(data.data);
                $scope.softwareLists = _.clone($scope.form.softwares);
                //UIkit.modal('#modal-software-editor-overflow').hide();
              }
            });
        };

        // @event Se déclanche quand un tag est ajouter dans l'input
        $scope.onAddedTag = ($tag) => {
          // Limiter le nombre des logiciels pour 10
          if ($scope.form.softwares.length < 10) {
            $scope.form.softwares.push($tag);
          } else {
            $scope.status = "Vous avez atteint la limite maximum. <b>Vous avez droit à seulement dix (10) logiciels</b>";
          }
          $scope.tags = '';
        };

        $scope.removeInList = (software) => {
          $scope.form.softwares = _.reject($scope.form.softwares, {name: software.name});
        };

        // Annuler le formulaire d'ajout et de modification
        $scope.abordModification = () => {
          //this.$onInit();
        };

        $scope.querySoftware = function ($query) {
          return $http.get(itOptions.Helper.ajax_url + '?action=ajx_get_taxonomy&tax=software', {
            cache: true
          })
            .then(function (response) {
              const softwares = response.data;
              return softwares.filter(function (software) {
                return software.name.toLowerCase().indexOf($query.toLowerCase()) != -1;
              });
            });
        };

        $scope.openEditor = () => {
          $scope.loading = true;
          UIkit.modal('#modal-software-editor-overflow').show();
          $q.all([$scope.softwareTerms()]).then(data => {
            $scope.Terms = _.map(data[0], resp => {
              return {id: resp.term_id, name: resp.name};
            });

            $scope.loading = false;
          })
        };

        $scope.$watch('form', (form) => {
        }, true);

        UIkit.util.on('#modal-software-editor-overflow', 'show', function (e) {
          e.preventDefault();
          jQuery(".select2_demo_1").select2({
            placeholder: 'Compétence (ex: Analyses de données)'
          });
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