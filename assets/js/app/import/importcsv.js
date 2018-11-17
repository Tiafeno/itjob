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
        controller: ['$scope', 'importService', '$q', function ($scope, importService, $q) {
          const self = this;
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
                dynamicTyping: true,
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

          // Envoyer l'importation au serveur
          self.sendOffer = (offer, entry_type) => {
            return new Promise((resolve, reject) => {
              const form = new FormData();
              form.append('action', 'import_csv');
              form.append('entry_type', entry_type);
              form.append('column', JSON.stringify(offer));
              importService
                .sendform(form)
                .then(resp => {
                  let query = resp.data;
                  if (query.success) {
                    resolve(query.data);
                  } else {
                    reject(query.data);
                  }
                });
            });
          };

          /**
           * @event ngClick
           */
          $scope.addedOfferForm = () => {
            const Form = new FormData();
            Form.append('action', 'get_offer_data');
            $scope.chargement = true;
            importService
              .sendform(Form)
              .then(resp => {
                let query = resp.data;
                if (query.success) {
                  let inputs = query.data;
                  let response = [];

                  async function loopOffers(inputs) {
                    for (const input of inputs) {
                      await self.sendOffer(input, 'offers')
                        .then(resp => {
                          response.push(resp);
                        });
                    }
                    $scope.chargement = false;
                  }

                  loopOffers(inputs);
                } else {
                  $scope.chargement = false;
                }

              })
          };

          /**
           * @event ngClick
           */
          $scope.deleteAllOffer = () => {
            const Form = new FormData();
            Form.append('action', 'delete_offer_data');
            importService
              .sendform(Form)
              .then(resp => {
                let query = resp.data;
                if (query.success) {
                  let inputs = query.data;
                  $scope.chargement = false;
                } else {
                  $scope.chargement = false;
                }
              })
          };

          $scope.stepFn = (results, parser) => {
            if (results || results.data) {
              const column = results.data;
              parser.pause();
              const form = new FormData();
              if (_.isNumber($scope.formData.fileContent)) {
                const fileContent = _.findWhere($scope.fileContents, {_id: $scope.formData.fileContent});
                form.append('content_type', fileContent.slug);
              }
              console.log($scope.formData.fileContent);
              form.append('action', 'import_csv');
              form.append('entry_type', $scope.formData.entryType);
              form.append('order', $scope.columns);
              form.append('column', JSON.stringify(column[0]));
              importService
                .sendform(form)
                .then(resp => {
                  let query = resp.data;
                  if (query.success) {
                    parser.resume();
                  } else {
                    parser.abort();
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
        label: "Term - Ville"
      },
      {
        _id: 8,
        slug: 'software',
        label: "Term - Logiciels"
      },
      {
        _id: 9,
        slug: 'job_sought',
        label: "Term - Emplois"
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