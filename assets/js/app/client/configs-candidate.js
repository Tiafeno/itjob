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
              $http.get(itOptions.Helper.ajax_url + '?action=client_area', {
                cache: false
              })
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
          templateUrl: `${itOptions.Helper.tpls_partials}/oc-candidate.html?ver=${itOptions.version}`,
          controller: 'candidateController',
        },
        {
          name: 'manager.profil',
          url: '/profil',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/candidate/profil.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.index',
          url: '/index',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/candidate/index.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {
            UIkit.util.on('#modal-candidate-profil-editor', 'show', function (e) {
              e.preventDefault();
              $rootScope.$apply(() => {
                $rootScope.onViewModalCandidateProfil();
              })
            });

            UIkit.util.on('#modal-candidate-profil-editor', 'hide', function (e) {
              e.preventDefault();
              $rootScope.avatarFile = false;
              $rootScope.profilEditor = {};
            });

            UIkit.util.on('#modal-information-editor', 'show', function (e) {
              e.preventDefault();
              jQuery("select.input-select2").select2({
                placeholder: "Selectionner une ville",
                allowClear: true,
                width: '100%',
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
                  $.each(paramTerms, (index, value) => {
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
              });
            });

            jQuery('[data-toggle="popover"]').popover();
          }]
        },
        {
          name: 'manager.profil.cv',
          url: '/cv',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/candidate/cv.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        },
        {
          name: 'manager.profil.candidacy',
          url: '/candidacy',
          templateUrl: `${itOptions.Helper.tpls_partials}/route/candidate/view-candidacy.html?ver=${itOptions.version}`,
          controller: ["$rootScope", function ($rootScope) {

          }]
        }
      ];
      // Loop over the state definitions and register them
      states.forEach(function (state) {
        $stateProvider.state(state);
      });
      $urlServiceProvider.rules.otherwise({state: 'manager.profil.index'});

    }])
  .directive('generalInformationCandidate', [function () {
    return {
      restrict: 'E',
      templateUrl: `${itOptions.Helper.tpls_partials}/general-information-candidate.html?ver=${itOptions.version}`,
      scope: {
        Candidate: '=candidate',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      controller: ['$scope', '$q', 'clientFactory', function ($scope, $q, clientFactory) {
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
      templateUrl: `${itOptions.Helper.tpls_partials}/job-search.html?ver=${itOptions.version}`,
      scope: {
        inJobs: "=jobs"
      },
      controller: ['$scope', '$http', function ($scope, $http) {
        $scope.isValidTag = true;
        $scope.jobs = [];
        $scope.jobLoading = false;
        this.$onInit = () => {
          $scope.jobs = _.isArray($scope.inJobs) ? _.clone($scope.inJobs) : [];
        };
        // Call before added tag
        $scope.onAddingTag = ($tag) => {
          if (_.isArray($scope.jobs) && $scope.jobs.length >= 2) return false;
          let isValid = true;
          let splitTag = '|;_\/*';
          for (let i in splitTag) {
            let str = splitTag.charAt(i);
            if ($tag.name.indexOf(str) > -1) {
              isValid = false;
              break;
            }
          }
          if (isValid) $scope.isValidTag = true;
          return isValid;
        };

        // Call if tag in invalid
        $scope.onTagInvalid = ($tag) => {
          $scope.isValidTag = false;
        };

        $scope.onSave = () => {
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
      templateUrl: `${itOptions.Helper.tpls_partials}/add-softwares.html?ver=${itOptions.version}`,
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
          $scope.form.softwares = _.clone($scope.softwares);
          $scope.softwareLists = _.clone($scope.softwares);
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
                $scope.softwareLists = _.clone($scope.form.softwares);
                UIkit.modal('#modal-software-editor-overflow').hide();
                alertify.success(data.data);
              }
            });
        };

        // @event Se déclanche quand un tag est ajouter dans l'input
        $scope.onAddingTag = ($tag) => {
          $scope.status = null;
          let isValid = true;
          let splitTag = ',;|/\:.*_•';
          for (let i in splitTag) {
            let str = splitTag.charAt(i);
            if ($tag.name.indexOf(str) > -1) {
              isValid = false;
              break;
            }
          }
          if (isValid) {
            let inSoftware = _.find($scope.form.softwares, (software) => {
              return software.name.toLowerCase() === $tag.name.toLowerCase()
            });
            if (inSoftware) {
              // Le logiciel existe déja dans la liste
              $scope.status = "Logiciel déja présent dans votre liste";
              return false;
            }
            // Limiter le nombre des logiciels pour 10
            if ($scope.form.softwares.length < 10) {
              $scope.form.softwares.push($tag);
              setTimeout(() => {
                $scope.$apply(() => {
                  $scope.tags = null;
                });
              }, 200);
            } else {
              $scope.status = "Vous avez atteint la limite maximum. <b>Vous avez droit à seulement dix (10) logiciels</b>";
              return false;
            }
          }
          return isValid;
        };

        $scope.removeInList = (software) => {
          $scope.form.softwares = _.reject($scope.form.softwares, {
            name: software.name
          });
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
              return {
                id: resp.term_id,
                name: resp.name
              };
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

        UIkit.util.on('#modal-software-editor-overflow', 'hide', (e) => {
          e.preventDefault();
        });

      }]
    }
  }])
  .directive('candidacy', [function () {
    return {
      restrict: 'E',
      templateUrl: `${itOptions.Helper.tpls_partials}/candidacy.html?ver=${itOptions.version}`,
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
  .directive('experiences', ['clientService', function (clientService) {
    return {
      restrict: 'E',
      templateUrl: `${itOptions.Helper.tpls_partials}/experiences.html?ver=${itOptions.version}`,
      scope: {
        Candidate: "=candidate",
        abranchFn: '&abranchFn' // Function pass
      },
      controller: ['$scope', '$http', '$q', function ($scope, $http, $q) {
        const self = this;
        $scope.tinymceOptions = {
          language: 'fr_FR',
          menubar: false,
          content_css: [
            '//fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i',
            '//www.tinymce.com/css/codepen.min.css'
          ],
          content_style: ".mce-content-body p { margin: 5px 0; }",
          inline: false,
          statusbar: true,
          resize: true,
          browser_spellcheck: true,
          height: 320,
          min_height: 230,
          selector: 'textarea',
          toolbar: 'undo redo | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat ',
          plugins: ['lists'],
        };
        /**
         * 0: Nouvelle experience
         * 1: Modifier l'experience
         * 2: Supprimer l'experience
         */
        $scope.mode = null;
        $scope.status = '';
        $scope.Exp = {};
        $scope.abranchs = [];
        $scope.newExperience = {};
        $scope.months = clientService.months;
        $scope.years = _.range(1959, new Date().getFullYear() + 1);
        $scope.dateEndRange = [];
        $scope.loading = false;
        $scope.loadExperiences = false;

        this.$onInit = () => {
          moment.locale('fr');
          let experiences = _.clone($scope.Candidate.experiences);
          $scope.Candidate.experiences = _.map(experiences, (experience) => {
            let oldValue = experience.old_value;
            let begin = _.isNull(experience.exp_dateBegin) || _.isEmpty(experience.exp_dateBegin) ? oldValue.exp_dateBegin : experience.exp_dateBegin;
            begin = _.isNull(begin) || _.isEmpty(begin) ? '' : begin;
            let __bg = begin.indexOf('/') > -1 ? moment(begin) : (begin.indexOf(' ') > -1 ? moment(begin, 'MMMM YYYY', true) : moment(begin));
            if (__bg.isValid()) {
              begin = __bg.format('MM/DD/YYYY');
            }

            let end = _.isNull(experience.exp_dateEnd) || _.isEmpty(experience.exp_dateEnd) ? oldValue.exp_dateEnd : experience.exp_dateEnd;
            end = _.isNull(end) || _.isEmpty(end) ? '' : end;
            let __nd = end.indexOf('/') > -1 ? moment(end) : (end.indexOf(' ') > -1 ? moment(end, 'MMMM YYYY', true) : moment(end));
            if (__nd.isValid()) {
              end = __nd.format('MM/DD/YYYY');
            }
            experience.exp_dateBegin = begin;
            experience.exp_dateEnd = end;

            return experience;
          });
          $scope.loadExperiences = true;

          $scope.Candidate.trainings = _.map($scope.Candidate.trainings, (training) => {
            let dateBegin = training.training_dateBegin;
            let mmBg = moment(dateBegin, "MM/DD/YYYY", true);
            if (!mmBg.isValid()) {
              let dateEnd = training.training_dateEnd;
              training.training_dateBegin = moment(dateBegin).format('MM/DD/YYYY');
              training.training_dateEnd = moment(dateEnd).format('MM/DD/YYYY');
            }

            return training;
          });
        };

        /**
         * Ajouter une nouvelle expérience
         */
        $scope.addNewExperience = () => {
          $scope.mode = 0;
          $scope.newExperience.position_currently_works = false;
          $scope.newExperience.validated = 0;
          $q.all([$scope.abranchFn()]).then(data => {
            $scope.abranchs = _.clone(data[0]);
            jQuery('#modal-new-experience-overflow').modal('show');
          });

        };

        /**
         * Supprimer une experience dans la base de donnée
         * @param {int} experienceId
         */
        $scope.onDeleteExperience = (experienceId) => {
          $scope.mode = 2;
          UIkit.modal.confirm('Une fois supprimé, vous ne pourrez plus revenir en arrière', {
            labels: {
              ok: 'Supprimer',
              cancel: 'Annuler'
            }
          })
            .then(function () {
              let Experiences = _.reject($scope.Candidate.experiences, (experience) => experience.id === parseInt(experienceId));
              self.updateExperience(Experiences)
                .then(response => {
                  if (response.success) {
                    alertify.success("Expérience supprimer avec succès");
                    $scope.mode = null;
                  }
                });
            }, () => {
              alertify.error("Une erreur s'est produite pendant la suppression.");
              $scope.mode = null;
            });
        };


        /**
         * Envoyer le fomulaire d'ajout pour une nouvelle experience
         * @param isValid
         */
        $scope.submitNewExperienceForm = (isValid) => {
          if (!isValid || !$scope.newExperienceForm.$dirty) return;
          $scope.loading = true;
          self.formatFormEntry($scope.newExperience)
            .then(Experience => {
              // Mettre à jour l'expérience
              self.updateExperience(Experience)
                .then(response => {
                  if (response.success) {
                    $scope.status = "Expérience ajouter avec succès";
                    window.setTimeout(() => {
                      jQuery('#modal-new-experience-overflow').modal('hide');
                      $scope.newExperience = {};
                    }, 1200);
                  } else {
                    $scope.status = response.msg;
                  }
                  $scope.loading = false;
                });
            });
        };

        /**
         * Cette fonction permet de formater les entrés dans le formulaire
         *
         * @param {object} model
         * @returns {*|Array}
         */
        self.formatFormEntry = (model) => {
          let deferred = $q.defer();
          let beginFormat = model.dateBegin.month + ", " + model.dateBegin.year;
          let dateBegin = moment(beginFormat, 'MMMM, YYYY', 'fr').format("MM/DD/Y");
          let dateEnd = '';
          let Experiences = [];
          if (!model.position_currently_works) {
            let endFormat = model.dateEnd.month + ", " + model.dateEnd.year;
            dateEnd = moment(endFormat, 'MMMM, YYYY', 'fr').format("MM/DD/Y");
          }
          if ($scope.mode === 1) {
            // Récuperer les experiences sauf celui qu'on est entrain de modifier
            Experiences = _.reject($scope.Candidate.experiences, exp => {
              return exp.id === modelid;
            });
          }
          let listOfExperiences = ($scope.mode === 0) ? _.clone($scope.Candidate.experiences) : Experiences;
          Experiences = _.map(listOfExperiences, exp => {
            if (_.isEmpty(exp.exp_dateEnd)) {
              exp.position_currently_works = true;
            }
            return exp;
          });
          Experiences.push({
            exp_positionHeld: model.position,
            exp_branch_activity: model.abranch,
            exp_company: model.company,
            exp_country: model.country,
            exp_city: model.city,
            exp_mission: model.mission,
            exp_dateBegin: dateBegin,
            exp_dateEnd: dateEnd,
            validated: model.validated
          });
          deferred.resolve(Experiences);
          return deferred.promise;
        };

        /**
         * Cette fonction permet d'envoyer une requete pour mettre à jours les expériences
         * @param {object} Experiences
         * @return
         */
        self.updateExperience = (Experiences) => {
          let deferred = $q.defer();
          const subForm = new FormData();
          subForm.append('action', 'update_experiences');
          subForm.append('experiences', JSON.stringify(Experiences));
          $scope.status = "Enregistrement en cours...";
          $http({
            url: itOptions.Helper.ajax_url,
            method: "POST",
            headers: {
              'Content-Type': undefined
            },
            data: subForm
          })
            .then(resp => {
              let data = resp.data;
              if (data.success) {
                $scope.Candidate.experiences = _.map(data.experiences, (exp, index) => {
                  exp.id = index;
                  return exp;
                });
                $scope.mode = null;
                deferred.resolve({
                  success: true
                });
              } else {
                deferred.reject({
                  success: false,
                  msg: "Une erreur s'est produite."
                })
              }
            });
          return deferred.promise;
        };

        /**
         * Envoyer le formulaire d'ajout pour la modification
         * @param {bool} isValid
         */
        $scope.submitForm = (isValid) => {
          if (!isValid || !$scope.eform.$dirty) return;
          self.formatFormEntry($scope.Exp)
            .then(Experience => {
              self.updateExperience(Experience)
                .then(response => {
                  if (response.success) {
                    $scope.status = "Enregistrer avec succès";
                    window.setTimeout(() => {
                      UIkit.modal('#modal-edit-experience-overflow').hide();
                    }, 1200);
                  } else {
                    $scope.status = response.msg;
                  }
                });
            });
          // Mettre à jour l'expérience

        };

        /**
         * Modifier une expérience
         * @param {string} positionHeld
         */
        $scope.editExperience = (experienceId) => {
          $scope.mode = 1;
          let experience = _.find($scope.Candidate.experiences, experience => experience.id == parseInt(experienceId));
          let momentDateBegin = moment(experience.exp_dateBegin, 'MM/DD/YYYY', 'fr');
          let dateEndObj = {};
          if (!_.isEmpty(experience.exp_dateEnd)) {
            let momentDateEnd = moment(experience.exp_dateEnd, 'MM/DD/YYYY', 'fr');
            dateEndObj = {
              month: momentDateEnd.format('MMMM'),
              year: parseInt(momentDateEnd.format('YYYY'))
            };
          }
          $scope.Exp = {
            id: experience.id,
            validated: experience.validated,
            position: experience.exp_positionHeld,
            company: experience.exp_company,
            city: experience.exp_city,
            country: experience.exp_country,
            mission: experience.exp_mission,
            position_currently_works: _.isEmpty(experience.exp_dateEnd) ? true : false,
            dateBegin: {
              month: momentDateBegin.format('MMMM'),
              year: parseInt(momentDateBegin.format('YYYY'))
            },
            dateEnd: dateEndObj
          };
          UIkit.modal('#modal-edit-experience-overflow').show();
        };

        /**
         * Récuperer une date de fin
         * @param beginYears
         * @returns {*}
         */
        $scope.dateEndRange = (beginYears) => {
          if (beginYears) {
            let yBegin = parseInt(beginYears);
            return _.range(yBegin, new Date().getFullYear() + 1)
          } else {
            return $scope.years;
          }
        };

        // Event on modal dialog close or hide
        UIkit.util.on('#modal-edit-experience-overflow', 'hide', e => {
          e.preventDefault();
          $scope.Exp = {};
          $scope.eform.$setPristine();
          $scope.eform.$setUntouched();
          $scope.status = '';
        });

        jQuery('#modal-new-experience-overflow').on('hidden.bs.modal', e => {
          e.preventDefault();
          $scope.status = '';
          $scope.newExperience = {};
        });

      }]
    }
  }])
  .directive('trainings', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/trainings.html?ver=' + itOptions.version,
      scope: {
        Candidate: "=candidate",
      },
      controller: ['$scope', '$http', '$q', 'clientService', function ($scope, $http, $q, clientService) {
        const self = this;
        /**
         * 0: Nouvelle formation
         * 1: Modifier la formation
         * 2: Supprimer la formation
         */
        $scope.mode = null;
        // Cette variable contient les nouvelles informations a modifier ou ajouter
        $scope.Train = {};
        $scope.months = clientService.months;
        $scope.years = _.range(1959, new Date().getFullYear() + 1);
        this.$onInit = () => {
        };

        // Ajouter une nouvelle formation
        $scope.newTraining = () => {
          $scope.mode = 0;
          $scope.Train.validated = false;
          UIkit.modal('#modal-edit-training-overflow').show();
        };

        $scope.dateEndRange = (beginYears) => {
          if (beginYears) {
            let yBegin = parseInt(beginYears);
            return _.range(yBegin, new Date().getFullYear() + 1)
          } else {
            return $scope.years;
          }
        };

        /**
         * Modifier la formation
         * @param {int} trainingId
         */
        $scope.editTraining = (trainingId) => {
          $scope.mode = 1;
          let editTraining = _.find($scope.Candidate.trainings, training => training.id === parseInt(trainingId));
          $scope.Train = _.mapObject(editTraining, (value, key) => {
            moment.locale('fr');
            switch (key) {
              case 'training_dateBegin':
                var dateBegin = moment(value, 'MM/DD/YYYY');
                value = {
                  month: dateBegin.format('MMMM'),
                  year: parseInt(dateBegin.format('YYYY'))
                };
                return value;
                break;
              case 'training_dateEnd':
                var dateEnd = moment(value, 'MM/DD/YYYY');
                value = {
                  month: dateEnd.format('MMMM'),
                  year: parseInt(dateEnd.format('YYYY'))
                };
                return value;
                break;

              default:
                return value;
                break;
            }
            return value;
          });
          UIkit.modal('#modal-edit-training-overflow').show();
        };

        /**
         * Supprimer une formation dans la base de donnée
         * @param {int} trainingId
         */
        $scope.onDeleteTraining = (trainingId) => {
          $scope.mode = 2;
          UIkit.modal.confirm('Une fois supprimé, vous ne pourrez plus revenir en arrière', {
            labels: {
              ok: 'Supprimer',
              cancel: 'Annuler'
            }
          })
            .then(function () {
              let Trainings = _.reject($scope.Candidate.trainings, (training) => training.id === parseInt(trainingId));
              self.updateTraining(Trainings);
              $scope.mode = null;
            }, () => {
              alertify.error("Une erreur s'est produite pendant la suppression.");
              $scope.mode = null;
            });
        };

        /**
         * Envoyer le formulaire pour mettre a jour les formations\
         * @param {bool} isValid
         */
        $scope.submitForm = (isValid) => {
          if (!isValid || _.isNull($scope.mode) || !$scope.tform.$dirty) return;
          let Trainings = _.clone($scope.Candidate.trainings);
          switch ($scope.mode) {
            case 0:
              // Nouvelle formation
              moment.locale('fr');
              let TrainingFormat = _.clone($scope.Train);
              TrainingFormat.validated = 0;
              let dateBegin = TrainingFormat.training_dateBegin;
              let dateEnd = TrainingFormat.training_dateEnd;
              TrainingFormat.training_dateBegin = moment(`${dateBegin.month} ${dateBegin.year}`, 'MMMM YYYY').format('MM/DD/YYYY');
              TrainingFormat.training_dateEnd = moment(`${dateEnd.month} ${dateEnd.year}`, 'MMMM YYYY').format('MM/DD/YYYY');
              Trainings.push(TrainingFormat);
              break;
            case 1:
              // Modifier une formation
              // FEATURED: Ajouter un code pour modifier une formation
              Trainings = _.map($scope.Candidate.trainings, (training) => {
                if (training.id === $scope.Train.id) {
                  return $scope.Train;
                }
                return training;
              });
              break;
            default:
              alertify.error("Une erreur s'est produite dans le formulaire");
              return false;
              break;
          }
          self.updateTraining(Trainings);
        };

        /**
         * Mettre a jour les formations ajouter ou modifier dans l'OC
         * @param {object} trainings
         */
        self.updateTraining = (trainings) => {
          const subForm = new FormData();
          subForm.append('action', 'update_trainings');
          subForm.append('trainings', JSON.stringify(trainings));
          $http({
            url: itOptions.Helper.ajax_url,
            method: "POST",
            headers: {
              'Content-Type': undefined
            },
            data: subForm
          })
            .then(resp => {
              let data = resp.data;
              if (data.success) {
                UIkit.modal('#modal-edit-training-overflow').hide();
                $scope.Candidate.trainings = _.map(data.trainings, (training, index) => {
                  training.id = index;
                  return training;
                });
                $scope.mode = null;
              } else {
                // La mise à jours n'a pas reussi
              }
            })
        };

        /**
         * Récuperer une date de fin
         * @param beginYears
         * @returns {*}
         */
        $scope.dateEndRange = (beginYears) => {
          if (beginYears) {
            let yBegin = parseInt(beginYears);
            return _.range(yBegin, new Date().getFullYear() + 1)
          } else {
            return $scope.years;
          }
        };

        UIkit.util.on('#modal-edit-training-overflow', 'hide', function (e) {
          e.preventDefault();
          $scope.Train = {};
          $scope.tform.$setPristine();
          $scope.tform.$setUntouched();
        });
      }]
    }
  }])
  .controller('candidateController', ['$rootScope', '$scope', '$http', '$filter', 'Client', 'Regions', 'Towns', 'Areas',
    function ($rootScope, $scope, $http, $filter, Client, Regions, Towns, Areas) {
      const self = this;

      $rootScope.alertLoading = false; // Directive alert
      $rootScope.alerts = [];
      $rootScope.jobSearchs = [];
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

      // Contient les valeurs d'introduction
      $rootScope.profilEditor = {};
      $rootScope.profilEditor.loading = false;
      $rootScope.profilEditor.form = {};
      $rootScope.Helper = {};
      // Contient l'image par default de l'OC
      $rootScope.featuredImage = '';
      // La valeur reste `false` si la photo de profil n'est pas toucher
      $rootScope.avatarFile = false;
      $rootScope.cv = {};
      $rootScope.cv.hasCV = false;
      $rootScope.cv.addCVUrl = itOptions.Helper.add_cv;
      $rootScope.Candidate = {};


      $rootScope.preloaderToogle = () => {
        $rootScope.preloader = !$rootScope.preloader;
      };

      $rootScope.searchCityFn = (city) => {
        if (!_.isUndefined($rootScope.profilEditor.form.region)) {
          let region = parseInt($rootScope.profilEditor.form.region);
          rg = _.findWhere(Regions, {
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

      this.$onInit = () => {
        var $ = jQuery.noConflict();
        let sexe = Client.iClient.greeting === null || _.isEmpty(Client.iClient.greeting) ? '' :
          (Client.iClient.greeting.value === 'mr') ? 'male' : 'female';
        $rootScope.featuredImage = itOptions.Helper.img_url + "/icons/administrator-" + sexe + ".png";
        const Candidate = _.clone(Client.iClient);
        $rootScope.Helper = _.clone(Client.Helper);
        $rootScope.Candidate = _.mapObject(Candidate, (value, key) => {
          switch (key) {
            case 'experiences':
            case 'trainings':
              return _.map(value, (element, index) => {
                element.id = index;
                return element;
              });
              break;
            case 'privateInformations':
              // avatar
              let privateInformations = _.clone(value);
              privateInformations = _.mapObject(privateInformations, (infoValue, infoKey) => {
                if (infoKey === 'avatar') {
                  return !infoValue ? $rootScope.featuredImage : infoValue[0];
                }
                return infoValue
              });
              return privateInformations;
              break;
            default:
              return value;
              break;
          }
        }); // .mapObject
        let greeting = $rootScope.Candidate.greeting;
        $rootScope.Greet = _.isObject(greeting) ? $filter('Greet')($rootScope.Candidate.greeting.value).toLowerCase() : '';
        $rootScope.cv.hasCV = $rootScope.Candidate.has_cv;
        let private = $rootScope.Candidate.privateInformations;
        const region = private.address.region;
        const country = private.address.country;
        const address = private.address.address;
        const abranch = $rootScope.Candidate.branch_activity;
        let updateActivity = $rootScope.Candidate.has_cv ? (!!(_.isNull(abranch) || !abranch)) : false;

        if (!country || !region || _.isEmpty($rootScope.Candidate.greeting) || !address || updateActivity) {
          $rootScope.profilEditor.abranchs = _.clone(Areas);
          $rootScope.profilEditor.regions = _.clone(Regions);
          $rootScope.profilEditor.city = [];
          $rootScope.profilEditor.city = _.map(Towns, (term) => {
            term.name = `(${term.postal_code}) ${term.name}`;
            return term;
          });

          if (!_.isEmpty($rootScope.Candidate.greeting)) {
            $rootScope.profilEditor.form.greeting = $rootScope.Candidate.greeting.value;
          }
          if (!_.isNull($rootScope.Candidate.branch_activity) || $rootScope.Candidate.branch_activity) {
            $rootScope.profilEditor.form.abranch = $rootScope.Candidate.branch_activity.term_id;
          }
          $rootScope.profilEditor.form.country = _.isEmpty(country) || _.isNull(country) ? '' : country.term_id;
          if (!_.isNull(region) || region) {
            $rootScope.profilEditor.form.region = region.term_id;
          } else {
            // Effacer la valeur d'une ville si la region n'est pas definie
            $rootScope.profilEditor.form.country = '';
          }
          $rootScope.profilEditor.form.name = `${private.firstname} ${private.lastname}`;
          $rootScope.profilEditor.form.email = $rootScope.Candidate.privateInformations.author.data.user_email;
          // Récuperer l'adresse
          $rootScope.profilEditor.form.address = _.isEmpty(address) || _.isNull(address) ? '' : address;
          $rootScope.preloaderToogle();
          UIkit.modal('#modal-information-editor').show();
        }

        $rootScope.alerts = _.reject(Client.Alerts, alert => _.isEmpty(alert));

      };

      /**
       * Afficher la boite de dialogue pour modifier un candidate
       */
      $rootScope.onViewModalCandidateProfil = () => {
        console.log('init');
        $rootScope.profilEditor.featuredImage = $rootScope.Candidate.privateInformations.avatar;
        let hasStatus = !_.isNull($rootScope.Candidate.status) && !_.isEmpty($rootScope.Candidate.status) && !_.isUndefined($rootScope.Candidate.status);
        $rootScope.profilEditor.status = hasStatus ? $rootScope.Candidate.status.value : '';
        $rootScope.profilEditor.newsletter = $rootScope.Candidate.newsletter;
        if (jQuery().validate) {
          jQuery("#editProfilForm").validate({
            rules: {
              status: {
                required: true,
              }
            },
            messages: {
              status: {
                required: "Ce champ est obligatoire"
              }
            },
            submitHandler: function (form) {
              //if (!$scope.editProfilForm.$dirty) return;
              $rootScope.profilEditor.loading = true;
              const Fm = new FormData();
              Fm.append('action', 'update-candidate-profil');
              Fm.append('status', $rootScope.profilEditor.status);
              Fm.append('newsletter', $rootScope.profilEditor.newsletter ? 1 : 0);
              if ($rootScope.avatarFile) {
                $rootScope.avatarFile.upload = Upload.upload({
                  url: itOptions.Helper.ajax_url,
                  data: {
                    file: $rootScope.avatarFile,
                    action: 'ajx_upload_media'
                  }
                });
                $rootScope.avatarFile.upload
                  .then(function (response) { // Success
                    $rootScope.avatarFile.result = response.data;
                    $rootScope.onSaveCandidateProfil(Fm);
                  }, response => { // Error
                    if (response.status > 0) {
                      alertify.error(response.status + ': ' + response.data);
                      $rootScope.profilEditor.loading = false;
                    }
                  }, evt => { // Progress
                    $rootScope.avatarFile.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                  });
              } else {
                $rootScope.onSaveCandidateProfil(Fm);
              }
            }
          });
        }
      };


      /**
       * Enregistrer les introduction de l'utilisateur
       * @param {FormData} formData
       */
      $rootScope.onSaveCandidateProfil = (formData) => {
        $http({
          url: itOptions.Helper.ajax_url,
          method: "POST",
          headers: {
            'Content-Type': undefined
          },
          data: formData
        })
          .then(
            resp => {
              let data = resp.data;
              if (!data.success) return;
              UIkit.modal('#modal-candidate-profil-editor').hide();
              $rootScope.profilEditor.loading = false;
              location.reload();
            }, error => {
              swall("Erreur", "Une erreur s'est produite, veuillez réessayer ultérieurement.", "error");
              $rootScope.profilEditor.loading = false;
            })
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

      $rootScope.getOptions = () => {
        return {
          Helper: $scope.Helper
        };
      };

      $rootScope.onSubmitCandidateInformation = (isValid) => {
        if (!isValid) return false;
        $rootScope.profilEditor.loading = true;
        const Form = new FormData();
        Form.append('action', 'update_candidate_information');
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
              setTimeout(() => {
                location.reload(true);
              }, 1200);

            }
          }, (error) => {
            $rootScope.profilEditor.loading = false;
          });
      };


    }])