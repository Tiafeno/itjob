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
            if (!isValid) return false;
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
              form.append('action', 'import_csv');
              form.append('entry_type', $scope.formData.entryType);
              form.append('order', $scope.columns);
              form.append('content_type', _.findWhere($scope.fileContents,
                (content) => content._id === parseInt($scope.formData.fileContent)).slug);
              form.append('column', JSON.stringify(column[0]));
              importService
                .sendform(form)
                .then(resp => {

                  parser.resume();
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
      {_id: 1, slug: 'taxonomy', label: 'Terms'},
      {_id: 2, slug: 'user', label: 'Utilisateurs'},
    ];
    self.typeOfContent = [
      {_id: 1, slug: 'city', label: "Ville"}
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
        headers: {'Content-Type': undefined},
        data: formData
      });
    };
  }])
  .controller('importController', ['$scope', function ($scope) {
    $scope.formData = {};
  }]);