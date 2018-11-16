angular.module('importCSVModule', ['ngMessages', 'ui.router', 'ngAria', 'ngAnimate'])
  .config(function ($stateProvider, $urlRouterProvider) {
    $stateProvider
      .state('import', {
        url: '/import',
        abstract: true,
        templateUrl: importOptions.partials_url + '/import.html',
        controller: 'importController'
      })
      .state('import.form', {
        url: '/form',
        templateUrl: importOptions.partials_url + '/form.html',
        controller: ['$scope', 'importService', function ($scope, importService) {
          $scope.chargement = false;
          $scope.entryTypes = _.clone(importService.typeOfEntry);
          $scope.fileContents = _.clone(importService.typeOfContent);
          $scope.onSubmitImport = (isValid) => {
            if (!isValid || _.isEmpty($scope.formData.entryType))
                return false;

            $scope.chargement = true;
            const inputCSV = jQuery('input#files');
            if (!inputCSV[0].files.length) {
              alert("Please choose at least one file to parse.");
              return false;
            }
            inputCSV.parse({
              config: {
                delimiter: ';',
                preview: 0,
                step: $scope.stepFn,
                encoding: "UTF-8",
                complete: $scope.completeFn,
                error: $scope.errorFn,
                //header: true,
                download: false
              },
              before: (file, inputElement) => {

              },
              error: function (err, file) {
                console.log("ERROR:", err, file);
              },
            })
          };
          $scope.stepFn = (results, parser) => {
            if (results || results.data) {
              const column = results.data;
              parser.pause();
              const form = new FormData();
              const fileContent = _.findWhere($scope.fileContents, {_id: $scope.formData.fileContent});
              form.append('action', 'import_csv');
              form.append('entry_type', $scope.formData.entryType);
              form.append('order', $scope.columns);
              form.append('content_type', fileContent.slug);
              form.append('column', JSON.stringify(column[0]));
              importService
                .sendform(form)
                .then(resp => {
                  let query = resp.data;
                  if (query.success) {
                    parser.resume();
                  } else {
                    console.log(query.data);
                    parser.stop();
                    $scope.chargement = false;
                  }

                })
            }
          };
          $scope.errorFn = (err, file) => {

          };
          $scope.completeFn = (results) => {
            $scope.chargement = false;
          };
          $scope.onFileContentChange = () => {
            $scope.columns = importService.getColumns(parseInt($scope.formData.fileContent));
          };
          $scope.$watch('formData', (value) => {

          }, true);
        }]
      });
    $urlRouterProvider.otherwise('/import/form');
  })
  .service('importService', ['$http', function ($http) {
    var self = this;
    self.typeOfEntry = [
      {
        _id: 1,
        slug: 'taxonomy',
        label: 'Terms'
      },
      {
        _id: 2,
        slug: 'user',
        label: 'Utilisateurs'
      },
      {
        _id: 2,
        slug: 'offers',
        label: 'Offres'
      },
    ];
    self.typeOfContent = [
      {
        _id: 1,
        slug: 'city',
        label: "Ville"
      },
      {
        _id: 2,
        slug: 'user',
        label: "Tout les utilisateurs"
      },
      {
        _id: 3,
        slug: 'user_candidate_experience',
        label: "Demandeur emploi - Experience"
      },
      {
        _id: 4,
        slug: 'user_candidate_cv',
        label: "Demandeur emploi - CV"
      },
      {
        _id: 5,
        slug: 'user_candidate_information',
        label: "Demandeur emploi - Information utilisateur"
      },
      {
        _id: 6,
        slug: 'user_candidate_formation',
        label: "Demandeur emploi - Formation"
      },
      {
        _id: 7,
        slug: 'user_company',
        label: 'Entreprise'
      }
    ];
    self.getColumns = (typeofFileId) => {
      if (typeofFileId === 1) {
        return 'parent_term,child_term';
      }
    };
    self.sendform = (formData) => {
      return $http({
        url: importOptions.ajax_url,
        method: "POST",
        headers: {
          'Content-Type': undefined
        },
        data: formData
      });
    };
  }])
  .controller('importController', ['$scope', function ($scope) {
    $scope.formData = {};
  }]);