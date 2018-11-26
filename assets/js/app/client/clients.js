const APPOC = angular.module('clientApp', ['ngMessages', 'ngRoute', 'froala', 'ngTagsInput', 'ngSanitize', 'ngFileUpload'])
  .value('froalaConfig', {
    toolbarInline: false,
    quickInsertTags: null,
    toolbarButtons: ['bold', 'strikeThrough', 'subscript', 'superscript', 'align', 'formatOL', 'formatUL', 'indent', 'outdent', 'undo', 'redo'],
  })
  .factory('clientFactory', ['$http', function ($http) {
    return {
      getCity: function () {
        return $http.get(itOptions.Helper.ajax_url + '?action=get_city', {
          cache: true
        })
          .then(function (resp) {
            return resp.data;
          });
      },
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.Helper.ajax_url,
          method: "POST",
          headers: {
            'Content-Type': undefined
          },
          data: formData
        });
      }
    };
  }])
  .service('clientService', [function () {
    this.offers = _.clone(itOptions.offers);
    this.months = [
      'janvier', 'février', 'mars',
      'avril', 'mai', 'juin',
      'juillet', 'août', 'septembre',
      'octobre', 'novembre', 'décembre'
    ];
  }])
  .filter('Greet', [function () {
    const Greeting = [{
      greeting: 'mrs',
      label: 'Madame'
    },
      {
        greeting: 'mr',
        label: 'Monsieur'
      }
    ];
    return value => {
      if (typeof value === 'undefined') return null;
      return _.findWhere(Greeting, {
        greeting: value
      }).label;
    }
  }])
  .filter('Status', [function () {
    const postStatus = [{
      slug: 'publish',
      label: 'Vérifier'
    },
      {
        slug: 'pending',
        label: 'En attente'
      }
    ];
    return (inputValue) => {
      if (typeof inputValue === 'undefined') return inputValue;
      return _.findWhere(postStatus, {
        slug: jQuery.trim(inputValue)
      }).label;
    }
  }])
  .directive('changePassword', ['$http', function ($http) {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/change-password.html',
      scope: {},
      link: function (scope, element, attrs) {
        scope.password = {};
        scope.error = false;
        if (jQuery().validate) {
          jQuery.validator.addMethod("pwdpattern", function (value) {
            return /^(?=(.*\d){2})(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z\d]).{8,}$/.test(value)
          });
          jQuery("#changePwdForm").validate({
            rules: {
              oldpwd: "required",
              pwd: {
                required: true,
                pwdpattern: true,
                minlength: 8,
              },
              confpwd: {
                equalTo: "#pwd"
              }
            },
            messages: {
              oldpwd: {
                required: "Ce champ est obligatoire"
              },
              pwd: {
                required: "Ce champ est obligatoire",
                pwdpattern: "Votre mot de passe doit comporter 8 caractères minimum, " +
                "se composer des chiffres et de lettres et comprendre des majuscules/minuscules et un caractère spéciale.",
              },
              confpwd: {
                required: "Ce champ est obligatoire",
                equalTo: "Les mots de passes ne sont pas identiques."
              }
            },
            submitHandler: function (form) {
              const Fm = new FormData();
              Fm.append('action', 'update-user-password');
              Fm.append('oldpwd', scope.password.oldpwd);
              Fm.append('pwd', scope.password.pwd);
              // Submit form validate
              $http({
                url: itOptions.Helper.ajax_url,
                method: "POST",
                headers: {
                  'Content-Type': undefined
                },
                data: Fm
              })
                .then(resp => {
                  let data = resp.data;
                  // Update password success
                  if (!data.success) {
                    scope.error = true;
                    return;
                  }
                  scope.error = false;
                  UIkit.modal('#modal-change-pwd-overflow').hide();
                  location.reload();
                })
            }
          });
        }

        // Event on modal dialog close or hide
        jQuery('#modal-change-pwd-overflow').on('hidden.bs.modal', function () {
          scope.$apply(() => {
            scope.changePwdForm.$setPristine();
            scope.changePwdForm.$setUntouched();
            scope.password = {};
            scope.error = false;
          });

        });
      },
      controller: ['$scope', function ($scope) {

      }]
    };
  }])
  .directive('alerts', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/alert.html',
      scope: {
        onSave: '&', // Function pass
        alerts: '=', // Two way variable pass
        message: '@', // String pass
        alertLoading: '='
      }
    };
  }])
  .directive('experiences', ['clientService', function (clientService) {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/experiences.html',
      scope: {
        Candidate: "=candidate",
      },
      controller: ['$scope', '$http', '$q', function ($scope, $http, $q) {
        const self = this;
        /**
         * 0: Nouvelle experience
         * 1: Modifier l'experience
         * 2: Supprimer l'experience
         */
        $scope.mode = null;
        $scope.Exp = {};
        $scope.newExperience = {};
        $scope.months = clientService.months;
        $scope.years = _.range(1959, new Date().getFullYear() + 1);
        $scope.dateEndRange = [];

        /**
         * Ajouter une nouvelle expérience
         */
        $scope.addNewExperience = () => {
          $scope.mode = 0;
          $scope.newExperience.position_currently_works = true;
          $scope.newExperience.validated = false;
          UIkit.modal('#modal-new-experience-overflow').show();
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
         * Envoyer le fomulaire d'ajout pour une nouvelle experience
         * @param isValid
         */
        $scope.submitNewExperienceForm = (isValid) => {
          if (!isValid || !$scope.newExperienceForm.$dirty) return;
          self.formatFormEntry($scope.newExperience)
            .then(Experience => {
              // Mettre à jour l'expérience
              self.updateExperience(Experience)
                .then(response => {
                  if (response.success) {
                    $scope.status = "Expérience ajouter avec succès";
                    window.setTimeout(() => {
                      UIkit.modal('#modal-new-experience-overflow').hide();
                      $scope.newExperience = {};
                    }, 1200);
                  } else {
                    $scope.status = response.msg;
                  }
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
            exp_company: model.company,
            exp_country: model.country,
            exp_city: model.city,
            exp_mission: model.mission,
            exp_dateBegin: dateBegin,
            exp_dateEnd: dateEnd,
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
        UIkit.util.on('#modal-edit-experience-overflow', 'hide', function (e) {
          e.preventDefault();
          $scope.Exp = {};
          $scope.eform.$setPristine();
          $scope.eform.$setUntouched();
          $scope.status = '';
        });

        $scope.$watch('Exp', experience => {
        });

        $scope.$watch('newExperienceForm', experience => {
        });
      }]
    }
  }])
  .directive('trainings', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/trainings.html',
      scope: {
        Candidate: "=candidate",
      },
      controller: ['$scope', '$http', '$q', function ($scope, $http, $q) {
        const self = this;
        /**
         * 0: Nouvelle formation
         * 1: Modifier la formation
         * 2: Supprimer la formation
         */
        $scope.mode = null;
        // Cette variable contient les nouvelles informations a modifier ou ajouter
        $scope.Train = {};
        $scope.years = _.range(1959, new Date().getFullYear() + 1);
        this.$onInit = () => {
        };

        // Ajouter une nouvelle formation
        $scope.newTraining = () => {
          $scope.mode = 0;
          $scope.Train.validated = false;
          UIkit.modal('#modal-edit-training-overflow').show();
        };

        /**
         * Modifier la formation
         * @param {int} trainingId
         */
        $scope.editTraining = (trainingId) => {
          $scope.mode = 1;
          let editTraining = _.find($scope.Candidate.trainings, training => training.id === parseInt(trainingId));
          $scope.Train = _.mapObject(editTraining, (value, key) => {
            switch (key) {
              case 'training_dateBegin':
              case 'training_dateEnd':
                return parseInt(value);
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
              Trainings.push($scope.Train);
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
          $scope.mode = null
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
  .directive('notifications', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/notifications.html',
      scope: {},
      controller: ['$scope', function ($scope) {

      }]
    }
  }])
  .controller('clientCtrl', ['$scope', '$http', '$q', 'clientFactory', 'clientService', 'Client', 'Upload',
    function ($scope, $http, $q, clientFactory, clientService, Client, Upload) {
      const self = this;
      // Contient les valeurs d'introduction
      $scope.profilEditor = {};
      $scope.profilEditor.loading = false;
      $scope.profilEditor.form = {};
      $scope.alertLoading = false; // Directive alert
      $scope.alerts = [];
      $scope.jobSearchs = [];
      $scope.Helper = {};
      $scope.preloader = false;
      // Contient l'image par default de l'OC
      $scope.featuredImage = '';
      // La valeur reste `false` si la photo de profil n'est pas toucher
      $scope.avatarFile = false;
      // Candidate
      $scope.cv = {};
      $scope.cv.hasCV = false;
      $scope.cv.addCVUrl = itOptions.Helper.add_cv;
      $scope.Candidate = {};
      $scope.biography = "";
      // Company
      $scope.Company = {}; // Contient l'information de l'utilisateur
      $scope.offerLists = []; // Contient les offres de l'entreprise
      $scope.candidateLists = []; // Contient la list des candidates interesser par l'entreprise

      $scope.preloaderToogle = () => {
        $scope.preloader = !$scope.preloader;
      };

      $scope.searchCityFn = (city) => {
        if (!_.isUndefined($scope.profilEditor.form.region)) {
          let region = parseInt($scope.profilEditor.form.region);
          rg = _.findWhere($scope.profilEditor.regions, {term_id: region});
          if (rg) {
            if (city.name.indexOf(rg.name) > -1) {
              return true;
            }
          }
        }
        return false;
      };

      // Mettre à jours les informations utilisateurs
      $scope.onSubmitCompanyInformation = (isValid) => {
        if (!isValid) return false;
        $scope.profilEditor.loading = true;
        const Form = new FormData();
        Form.append('action', 'update_company_information');
        Form.append('abranch', $scope.profilEditor.form.abranch);
        Form.append('region', $scope.profilEditor.form.region);
        Form.append('country', $scope.profilEditor.form.country);
        Form.append('address', $scope.profilEditor.form.address);
        Form.append('greet', $scope.profilEditor.form.greeting);
        clientFactory
          .sendPostForm(Form)
          .then(resp => {
            let response = resp.data;
            if (response.success) {
              $scope.profilEditor.loading = false;
              setTimeout(() => {
                location.reload(true);
              }, 1200);

            }
          }, (error) => {
          });
      };

      $scope.onSubmitCandidateInformation = (isValid) => {
//update_candidate_information
        if (!isValid) return false;
        $scope.profilEditor.loading = true;
        const Form = new FormData();
        Form.append('action', 'update_candidate_information');
        Form.append('abranch', $scope.profilEditor.form.abranch);
        Form.append('region', $scope.profilEditor.form.region);
        Form.append('country', $scope.profilEditor.form.country);
        Form.append('address', $scope.profilEditor.form.address);
        Form.append('greet', $scope.profilEditor.form.greeting);
        clientFactory
          .sendPostForm(Form)
          .then(resp => {
            let response = resp.data;
            if (response.success) {
              $scope.profilEditor.loading = false;
              setTimeout(() => {
                location.reload(true);
              }, 1200);

            }
          }, (error) => {
          });
      };

      self.send

      /**
       * Récuperer les données sur le client
       */
      $scope.Initialize = () => {
        console.info('Client init...');
        if (Client.post_type === 'company') {
          $scope.Company = _.clone(Client.iClient);
          $scope.Helper = _.clone(Client.Helper);
          $scope.offerLists = _.clone(Client.Offers);
          $scope.candidateLists = _.clone(Client.ListsCandidate);
          if (_.isNull($scope.Company.branch_activity) || !$scope.Company.branch_activity || !$scope.Company.country ||
            !$scope.Company.region || _.isEmpty($scope.Company.greeting)) {
            $q.all([
              $scope.asyncTerms('branch_activity'),
              $scope.asyncTerms('region'),
              $scope.asyncTerms('city')])
              .then(data => {
                $scope.profilEditor.abranchs = _.clone(data[0]);
                $scope.profilEditor.regions = _.clone(data[1]);
                $scope.profilEditor.city = [];
                $scope.profilEditor.city = _.map(data[2], (term) => {
                  term.name = `(${term.postal_code}) ${term.name}`;
                  return term;
                });

                if (!_.isEmpty($scope.Company.greeting)) {
                  $scope.profilEditor.form.greeting = $scope.Company.greeting.value;
                }
                if (!_.isNull($scope.Company.branch_activity) || $scope.Company.branch_activity) {
                  $scope.profilEditor.form.abranch = $scope.Company.branch_activity.term_id;
                }
                if (!_.isNull($scope.Company.region) || $scope.Company.region) {
                  $scope.profilEditor.form.region = $scope.Company.region.term_id;
                }
                UIkit.modal('#modal-information-editor').show();
              })
          }

        }
        else {
          // Candidat
          // Crée une image par default
          let sexe = Client.iClient.greeting === null || _.isEmpty(Client.iClient.greeting) ? '' : (Client.iClient.greeting.value === 'mr') ? 'male' : 'female';
          $scope.featuredImage = itOptions.Helper.img_url + "/icons/administrator-" + sexe + ".png";
          const Candidate = _.clone(Client.iClient);
          $scope.biography = Client.iClient.has_cv ? Client.iClient.status.label : '';
          $scope.Helper = _.clone(Client.Helper);
          $scope.Candidate = _.mapObject(Candidate, (value, key) => {
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
                    return !infoValue ? $scope.featuredImage : infoValue[0];
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
          $scope.cv.hasCV = $scope.Candidate.has_cv;
          const region = $scope.Candidate.privateInformations.address.region;
          const country = $scope.Candidate.privateInformations.address.country;

          if (_.isNull($scope.Candidate.branch_activity) || !$scope.Candidate.branch_activity || !country ||
            !region || _.isEmpty($scope.Candidate.greeting)) {
            $q.all([
              $scope.asyncTerms('branch_activity'),
              $scope.asyncTerms('region'),
              $scope.asyncTerms('city')])
              .then(data => {
                $scope.profilEditor.abranchs = _.clone(data[0]);
                $scope.profilEditor.regions = _.clone(data[1]);
                $scope.profilEditor.city = [];
                $scope.profilEditor.city = _.map(data[2], (term) => {
                  term.name = `(${term.postal_code}) ${term.name}`;
                  return term;
                });

                if (!_.isEmpty($scope.Candidate.greeting)) {
                  $scope.profilEditor.form.greeting = $scope.Candidate.greeting.value;
                }
                if (!_.isNull($scope.Candidate.branch_activity) || $scope.Candidate.branch_activity) {
                  $scope.profilEditor.form.abranch = $scope.Candidate.branch_activity.term_id;
                }
                if (!_.isNull(region) || region) {
                  $scope.profilEditor.form.region = region.term_id;
                }
                $scope.profilEditor.form.name = `${$scope.Candidate.privateInformations.firstname} ${$scope.Candidate.privateInformations.lastname}`;
                $scope.profilEditor.form.email = $scope.Candidate.privateInformations.author.data.user_email;
                // Récuperer l'adresse
                let address = $scope.Candidate.privateInformations.address.address;
                address = _.isEmpty(address) || _.isNull(address) ? '' : address;
                $scope.profilEditor.form.address = address;
                $scope.profilEditor.form.country = _.isEmpty(country) || _.isNull(country) ? '' : country.term_id;
                UIkit.modal('#modal-information-editor').show();
              })
          } else {
            if (!$scope.cv.hasCV) {
              jQuery('#modal-info-editor').modal('show');
            }
          }
        } // .end candidate

        $scope.alerts = _.reject(Client.Alerts, alert => _.isEmpty(alert));

        // jQuery
        // Activate Popovers
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
          });
        });
        jQuery('[data-toggle="popover"]').popover();
      };

      /**
       * Récuperer les terms d'une taxonomie
       * @param {string} Taxonomy
       */
      $scope.asyncTerms = (Taxonomy) => {
        if (Taxonomy !== 'city') {
          return $http.get(itOptions.Helper.ajax_url + '?action=ajx_get_taxonomy&tax=' + Taxonomy, {
            cache: true
          }).then(resp => resp.data);
        } else {
          return clientFactory.getCity();
        }
      };

      /**
       * Mettre a jour les alerts (Ajouter, Supprimer)
       * Une alerte permet de notifier l'utilisateur par email
       * Si une publication (offre, annonce, travaille temporaire) comportent ces mots
       */
      $scope.onSaveAlert = () => {
        if (_.isEmpty($scope.alerts)) return;
        $scope.alertLoading = true;
        var form = new FormData();
        form.append('action', 'update_alert_filter');
        form.append('alerts', JSON.stringify($scope.alerts));
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
            $scope.alertLoading = false;
            if (!data.success) {
              alertify.error("Une erreur inconue s'est produit")
            } else {
              alertify.success('Enregistrer avec succès')
            }
          });
      };

      $scope.getOptions = () => {
        return {
          Helper: $scope.Helper
        };
      };

      /**
       * Cette fonction permet de redimensionner une image
       *
       * @param imgObj - the image element
       * @param newWidth - the new width
       * @param newHeight - the new height
       * @param startX - the x point we start taking pixels
       * @param startY - the y point we start taking pixels
       * @param ratio - the ratio
       * @returns {string}
       */
      const drawImage = (imgObj, newWidth, newHeight, startX, startY, ratio) => {
        //set up canvas for thumbnail
        const tnCanvas = document.createElement('canvas');
        const tnCanvasContext = tnCanvas.getContext('2d');
        tnCanvas.width = newWidth;
        tnCanvas.height = newHeight;

        /* use the sourceCanvas to duplicate the entire image. This step was crucial for iOS4 and under devices. Follow the link at the end of this post to see what happens when you don’t do this */
        const bufferCanvas = document.createElement('canvas');
        const bufferContext = bufferCanvas.getContext('2d');
        bufferCanvas.width = imgObj.width;
        bufferCanvas.height = imgObj.height;
        bufferContext.drawImage(imgObj, 0, 0);

        /* now we use the drawImage method to take the pixels from our bufferCanvas and draw them into our thumbnail canvas */
        tnCanvasContext.drawImage(bufferCanvas, startX, startY, newWidth * ratio, newHeight * ratio, 0, 0, newWidth, newHeight);
        return tnCanvas.toDataURL();
      };

      /**
       * Récuperer les valeurs dispensable pour une image pré-upload
       * @param {File} file
       * @returns {Promise<any>}
       */
      self.imgPromise = (file) => {
        return new Promise((resolve, reject) => {
          const byteLimite = 2097152; // 2Mb
          if (file && file.size <= byteLimite) {
            let fileReader = new FileReader();
            fileReader.onload = (Event) => {
              const img = new Image();
              img.src = Event.target.result;
              img.onload = () => {
                const ms = Math.min(img.width, img.height);
                const mesure = (ms < 600) ? ms : 600;
                const imgCrop = drawImage(img, mesure, mesure, 0, 0, 1);
                resolve({
                  src: imgCrop
                });
              };
            };
            fileReader.readAsDataURL(file);
          } else {
            reject('Le fichier sélectionné est trop volumineux. La taille maximale est 2Mo.');
          }
        });
      };

      /**
       * Upload featured image
       * @param file
       * @param errFiles
       */
      $scope.uploadImage = function (file, errFiles) {
        $scope.avatarFile = file;
        if (_.isNull(file)) return;
        self.imgPromise(file)
          .then(result => {
            $scope.$apply(() => {
              $scope.profilEditor.featuredImage = result.src;
            });
          })
          .catch(e => {
            alertify.error(e);
          });
      };

      /**
       * Afficher la boite de dialogue pour modifier un candidate
       */
      $scope.onViewModalCandidateProfil = () => {
        $scope.profilEditor.featuredImage = $scope.Candidate.privateInformations.avatar;
        let hasStatus = _.isNull($scope.Candidate.status) || _.isEmpty($scope.Candidate.status) || _.isUndefined($scope.Candidate.status);
        $scope.profilEditor.status = hasStatus ? $scope.Candidate.status.value : '';
        $scope.profilEditor.newsletter = $scope.Candidate.newsletter;
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
              if (!$scope.editProfilForm.$dirty) return;
              const Fm = new FormData();
              Fm.append('action', 'update-candidate-profil');
              Fm.append('status', $scope.profilEditor.status);
              Fm.append('newsletter', $scope.profilEditor.newsletter ? 1 : 0);
              if ($scope.avatarFile) {
                $scope.avatarFile.upload = Upload.upload({
                  url: itOptions.Helper.ajax_url,
                  data: {
                    file: $scope.avatarFile,
                    action: 'ajx_upload_media'
                  }
                });
                $scope.avatarFile.upload
                  .then(function (response) { // Success
                    $scope.avatarFile.result = response.data;
                    $scope.onSaveCandidateProfil(Fm);
                  }, response => { // Error
                    if (response.status > 0)
                      alertify.error(response.status + ': ' + response.data);
                  }, evt => { // Progress
                    $scope.avatarFile.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                  });
              } else {
                $scope.onSaveCandidateProfil(Fm);
              }
            }
          });
        }
      };

      /**
       * Enregistrer les introduction de l'utilisateur
       * @param {FormData} formData
       */
      $scope.onSaveCandidateProfil = (formData) => {
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
            if (!data.success) return;
            UIkit.modal('#modal-candidate-profil-editor').hide();
            location.reload();
          })
      };

      UIkit.util.on('#modal-candidate-profil-editor', 'show', function (e) {
        e.preventDefault();
        $scope.$apply(() => {
          $scope.onViewModalCandidateProfil();
        })
      });

      UIkit.util.on('#modal-candidate-profil-editor', 'hide', function (e) {
        e.preventDefault();
        $scope.avatarFile = false;
        $scope.profilEditor = {};
      });

      /**
       * Envoyer une offre dans la corbeille
       * @param {int} offerId
       */
      $scope.trashOffer = function (offerId) {
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

      // Inititialise controlleur
      $scope.Initialize();
    }
  ]);