const APPOC = angular.module('clientApp', ['ngMessages', 'ngRoute', 'froala', 'ngTagsInput', 'ngSanitize', 'ngFileUpload'])
  .value('froalaConfig', {
    toolbarInline: false,
    quickInsertTags: null,
    toolbarButtons: ['bold', 'strikeThrough', 'subscript', 'superscript', 'align', 'formatOL', 'formatUL', 'indent', 'outdent', 'undo', 'redo'],
  })
  .factory('clientFactory', ['$http', '$q', function ($http, $q) {
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
  .service('clientService', ['$http', function ($http) {
    this.offers = _.clone(itOptions.offers);
    this.months = [
      'janvier', 'février', 'mars',
      'avril', 'mai', 'juin',
      'juillet', 'août', 'septembre',
      'octobre', 'novembre', 'décembre'
    ];
    this.clientArea = () => {
      return $http.get(itOptions.Helper.ajax_url + '?action=client_area', {
        cache: false
      });
    }
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
                pwdpattern: "Votre mot de passe doit comporter un minimum de 8 caractères, " +
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
                  if ( ! data.success) {
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
            $scope.candidateEditor.country = $scope.Candidate.country.term_id;
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
      link: function (scope, element, attrs) {},
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
  .directive('offerLists', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/offer-lists.html',
      scope: {
        Entreprise: '=company',
        Offers: '=offers',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      link: function (scope, element, attrs) {
        scope.focusFire = false;
        scope.Helper = itOptions.Helper;
        angular.element(document).ready(function () {
          // Load datatable on focus search input
          jQuery('#key-search').focus(function () {
            if (scope.focusFire) return;
            const table = jQuery('#products-table').DataTable({
              pageLength: 10,
              fixedHeader: false,
              responsive: false,
              "sDom": 'rtip',
              language: {
                url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
              }
            });
            jQuery('#key-search').on('keyup', () => {
              table.search(this.value).draw();
            });
            scope.focusFire = true;
          });

          jQuery('.input-group.date').datepicker({
            format: "mm/dd/yyyy",
            language: "fr",
            startView: 2,
            todayBtn: false,
            keyboardNavigation: true,
            forceParse: false,
            autoclose: true
          });
          console.log("Load datatable");

        });
      },
      controller: ['$scope', '$http', '$q', 'clientFactory', function ($scope, $http, $q, clientFactory) {
        $scope.offerEditor = {};
        $scope.loadingCandidats = false;
        $scope.postuledCandidats = [];

        /**
         * Ouvrire une boite de dialoge pour modifier une offre
         * @param {int} offerId 
         */
        $scope.openEditor = (offerId) => {
          let offer = _.findWhere($scope.Offers, {
            ID: parseInt(offerId)
          });

          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
            $scope.Regions = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys = _.clone(data[2]);
            $scope.offerEditor = _.mapObject(offer, (val, key) => {
              if (typeof val.term_id !== 'undefined') return val.term_id;
              if (typeof val.label !== 'undefined') return val.value;
              if (typeof val.post_title !== 'undefined') return val.ID;
              if (key === 'proposedSalary') return parseInt(val);
              return val;
            });
            if (!_.isEmpty(offer) || !_.isNull($scope.offerEditor)) {
              UIkit.modal('#modal-edit-offer-overflow').show();
            }
          });
        };

        /**
         * Modifier une offre
         * @param {int} offerId 
         */
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
              UIkit.modal('#modal-edit-offer-overflow').hide();
              $scope.init();
            });
        };

        /**
         * Afficher les candidates qui ont postulee 
         * @param {int} offer_id 
         */
        $scope.viewApply = (offer_id) => {
          $scope.loadingCandidats = true;
          let offer = _.find($scope.Offers, (item) => item.ID === offer_id);
          if (!offer.my_offer || offer.count_candidat_apply <= 0) return;

          UIkit.modal('#modal-view-candidat').show();
          $http.get(itOptions.Helper.ajax_url + '?action=get_postuled_candidate&oId=' + offer.ID, {
              cache: false
            })
            .then(resp => {
              $scope.postuledCandidats = resp.data;
              $scope.loadingCandidats = false;
            });
        };
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
      controller: ['$scope', '$http', function ($scope, $http) {
        const self = this;
        /**
         * 0: Nouvelle experience
         * 1: Modifier l'experience
         * 2: Supprimer l'experience
         */
        $scope.mode = null;
        $scope.Exp = {};
        $scope.months = clientService.months;
        $scope.years = _.range(1959, new Date().getFullYear() + 1);
        $scope.dateEndRange = [];

        /**
         * Ajouter une nouvelle expérience
         */
        $scope.addNewExperience = () => {
          $scope.mode = 0;
          $scope.Exp.position_currently_works = true;
          UIkit.modal('#modal-add-experience-overflow').show();
        };

        /**
         * Modifier une expérience
         * @param {string} positionHeld 
         */
        $scope.editExperience = (experienceId) => {
          $scope.mode = 1;
          let experience = _.find($scope.Candidate.experiences, experience => experience.id == parseInt(experienceId));
          let momentDateBegin = moment(experience.exp_dateBegin, 'MMMM, YYYY', 'fr');
          let dateEndObj = {};
          if (!_.isEmpty(experience.exp_dateEnd)) {
            let momentDateEnd = moment(experience.exp_dateEnd, 'MMMM, YYYY', 'fr');
            dateEndObj = {
              month: momentDateEnd.format('MMMM'),
              year: parseInt(momentDateEnd.format('YYYY'))
            };
          }
          $scope.Exp = {
            id: experience.id,
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
          UIkit.modal('#modal-add-experience-overflow').show();
        };

        /**
         * Supprimer une experience dans la base de donnée
         * @param {int} experienceId 
         */
        $scope.onDeleteExperience = (experienceId) => {
          $scope.mode = 2;
          UIkit.modal.confirm('Une fois supprimé, vous ne pourrez plus revenir en arrière', { labels: { ok: 'Supprimer', cancel: 'Annuler' } })
          .then(function () {
            let Experiences = _.reject($scope.Candidate.experinces, (experience) => experience.id === parseInt(experienceId));
            self.updateExperience(Experiences);
            $scope.mode = null;
          }, () => {
            alertify.erreur("Une erreur s'est produite pendant la suppression.")
            $scope.mode = null;
          });
        };

        /**
         * Envoyer le formulaire d'ajout
         * @param {bool} isValid 
         */
        $scope.submitForm = (isValid) => {
          if ( ! isValid || ! $scope.eform.$dirty) return;

          let beginFormat = $scope.Exp.dateBegin.month + ", " + $scope.Exp.dateBegin.year;
          let dateBegin = moment(beginFormat, 'MMMM, YYYY', 'fr').format("MM/DD/Y");
          let dateEnd = '';
          if (!$scope.Exp.position_currently_works) {
            let endFormat = $scope.Exp.dateEnd.month + ", " + $scope.Exp.dateEnd.year;
            dateEnd = moment(endFormat, 'MMMM, YYYY', 'fr').format("MM/DD/Y");
          }
          let Experiences = [];
          if ($scope.mode === 1) {
            // Récuperer les experiences sauf celui qu'on est entrain de modifier
            Experiences = _.reject($scope.Candidate.experiences, exp => {
              return exp.id === $scope.Exp.id;
            });
          }
          let listOfExperiences = ($scope.mode === 0) ? _.clone($scope.Candidate.experiences) : Experiences;
          // Modifier les formats des date pour les autres expériences 
          Experiences = _.map(listOfExperiences, exp => {
            exp.exp_dateBegin = moment(exp.exp_dateBegin, 'MMMM, YYYY', 'fr').format("MM/DD/Y");
            if (!_.isEmpty(exp.exp_dateEnd)) {
              exp.exp_dateEnd = moment(exp.exp_dateEnd, 'MMMM, YYYY', 'fr').format("MM/DD/Y");
            } else {
              exp.position_currently_works = true;
            }
            return exp;
          });
          Experiences.push({
            exp_positionHeld: $scope.Exp.position,
            exp_company: $scope.Exp.company,
            exp_country: $scope.Exp.country,
            exp_city: $scope.Exp.city,
            exp_dateBegin: dateBegin,
            exp_dateEnd: dateEnd,
          });
          // Mettre à jour l'expérience
          self.updateExperience(Experiences);
        };
        /**
         * Cette fonction permet de mettre à jours les expériences
         * @param {object} Experiences 
         */
        self.updateExperience = (Experiences) => {
          const subForm = new FormData();
          subForm.append('action', 'update_experiences');
          subForm.append('experiences', JSON.stringify(Experiences));
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
                UIkit.modal('#modal-add-experience-overflow').hide();
                $scope.Candidate.experiences = _.map(data.experiences, (exp, index) => {
                  exp.id = index;
                  return exp;
                });
                $scope.mode = null;
              }
            })
        };
        // Event on modal dialog close or hide
        UIkit.util.on('#modal-add-experience-overflow', 'hide', function (e) {
          e.preventDefault();
          $scope.Exp = {};
          $scope.eform.$setPristine();
          $scope.eform.$setUntouched();
        });
        $scope.$watch('Exp', v => {
          if (v == undefined || v.dateBegin == undefined) return;
          if (!_.isUndefined(v.dateBegin.year)) {
            let year = v.dateBegin.year;
            $scope.dateEndRange = _.range(year, new Date().getFullYear() + 1)
          }
        }, true);
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
      controller: ['$scope', '$http', function ($scope, $http) {
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
        this.$onInit = () => {};

        /**
         * Ajouter une nouvelle formation
         */
        $scope.newTraining = () => {
          $scope.mode = 0;
          UIkit.modal('#modal-add-training-overflow').show();
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
          UIkit.modal('#modal-add-training-overflow').show();
        };

        /**
         * Supprimer une formation dans la base de donnée
         * @param {int} trainingId 
         */
        $scope.onDeleteTraining = (trainingId) => {
          $scope.mode = 2;
          UIkit.modal.confirm('Une fois supprimé, vous ne pourrez plus revenir en arrière', { labels: { ok: 'Supprimer', cancel: 'Annuler' } })
          .then(function () {
            let Trainings = _.reject($scope.Candidate.trainings, (training) => training.id === parseInt(trainingId));
            self.updateTraining(Trainings);
            $scope.mode = null;
          }, () => {
            alertify.erreur("Une erreur s'est produite pendant la suppression.")
            $scope.mode = null;
          });
        }

        /**
         * Envoyer le formulaire pour mettre a jour les formations\
         * @param {bool} isValid 
         */
        $scope.submitForm = (isValid) => {
          if (!isValid || _.isNull($scope.mode) || ! $scope.tform.$dirty) return;
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
              console.log("Une erreur s'est produite dans le formulaire");
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
                UIkit.modal('#modal-add-training-overflow').hide();
                $scope.Candidate.trainings = _.map(data.trainings, (training, index) => {
                  training.id = index;
                  return training;
                });
                $scope.mode = null;
              }
            })
        };

        UIkit.util.on('#modal-add-training-overflow', 'hide', function (e) {
          e.preventDefault();
          $scope.Train = {};
          $scope.tform.$setPristine();
          $scope.tform.$setUntouched();
        });
      }]}
  }])
  .directive('notifications', [function() {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/notifications.html',
      scope: {},
      controller: ['$scope', function($scope) {

      }]
    }
  }])
  .controller('clientCtrl', ['$scope', '$http', '$q', 'clientFactory', 'clientService', 'Client', 'Upload',
    function ($scope, $http, $q, clientFactory, clientService, Client, Upload) {
      const self = this;
      // Contient les valeurs d'introduction
      $scope.profilEditor = {};
      $scope.alertLoading = false;
      $scope.alerts = [];
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
      $scope.Company = {};
      $scope.offerLists = [];
      $scope.countOffer = 0;

      /**
       * Récuperer les données sur le client
       */
      $scope.Initialize = () => {
        console.log('Initialize');
        if (Client.post_type === 'company') {
          $scope.Company = _.clone(Client.Company);
          $scope.offerLists = _.clone(Client.Offers);
        } else {
          /** Crée une image par default */
          let sexe = (Client.Candidate.greeting.value === 'mr') ? 'male' : 'female';
          $scope.featuredImage = itOptions.Helper.img_url + "/icons/administrator-" + sexe + ".png";
          const Candidate = _.clone(Client.Candidate);
          $scope.biography = Client.Candidate.has_cv ? Client.Candidate.status.label : '';
          $scope.Candidate = _.mapObject(Candidate, (value, key) => {
            switch (key) {
              case 'experiences':
              case 'trainings':
                return _.map(value, (element, index) => {
                  element.id = index;
                  return element;
                });
                break;
              case 'avatar':
                return !value ? $scope.featuredImage : value[0];
                break;
              default:
                return value;
                break;
            }
          });
          $scope.cv.hasCV = $scope.Candidate.has_cv;
        }
        $scope.alerts = _.reject(Client.Alerts, alert => _.isEmpty(alert));
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
            if ( ! data.success) {
              alertify.error("Une erreur inconue s'est produit")
            } else {
              alertify.success('Enregistrer avec succès')
            }
          });
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
        $scope.profilEditor.featuredImage = $scope.Candidate.avatar;
        $scope.profilEditor.status = $scope.Candidate.status.value;
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
              if ( ! $scope.editProfilForm.$dirty) return;
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
      }

      // Inititialise controlleur
      $scope.Initialize();


    }
  ])