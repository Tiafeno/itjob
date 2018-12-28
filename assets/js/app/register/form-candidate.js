const API_COUNTRY_URL = 'https://restcountries.eu';

angular.module('formCandidateApp', ['ngAnimate', 'ngMessages', 'ui.select2', 'ui.router', 'ngTagsInput', 'ngFileUpload'])
  .config(function ($stateProvider, $urlRouterProvider) {
    $stateProvider
      .state('form', {
        url: '/form',
        templateUrl: itOptions.partials_url + '/candidate/form.html',
        resolve: {
          abranchs: function (Services) {
            return Services.getTaxonomy('branch_activity');
          },
          languages: function (Services) {
            return Services.getTaxonomy('language');
          },
          jobSougths: function (Services) {
            return Services.getTaxonomy('job_sought');
          },
          candidate: function (Services) {
            return Services.collectCandidatInfo();
          },
          access: ['$q', function ($q) {}]
        },
        controller: 'formController'
      })
      // nested states
      // each of these sections will have their own view
      // url will be nested (/form/career)
      .state('form.career', {
        url: '/career',
        templateUrl: itOptions.partials_url + '/candidate/career.html?version=' + itOptions.version,
        resolve: {
          access: ['$q', '$rootScope', function ($q, $rootScope) {

            if (typeof $rootScope.formData === 'undefined') {
              // Refuser et retourner si le formlaire n'est pas complete
              return $q.reject({
                redirect: 'form.informations'
              });
            }
            if (typeof $rootScope.formData.status === 'undefined' ||
              typeof $rootScope.formData.jobSougths === 'undefined' ||
              _.isEmpty($rootScope.formData.jobSougths) ||
              typeof $rootScope.formData.abranch === 'undefined') {
              // Refuser l'accès si les champs ou les variables si-dessus ne sont pas valide
              return $q.reject({
                redirect: 'form.informations'
              });
            }
          }],
          driveLicences: ['$q', function ($q) {
            // Permis de conduire (Schema)
            const licences = [{
                _id: 0,
                label: "A`",
                slug: "a_"
              },
              {
                _id: 1,
                label: "A",
                slug: "a"
              },
              {
                _id: 2,
                label: "B",
                slug: "b"
              },
              {
                _id: 3,
                label: "C",
                slug: "c"
              },
              {
                _id: 4,
                label: "D",
                slug: "d"
              }
            ];
            return $q.resolve(licences);
          }],
        },
        controller: function ($rootScope, $scope, $http, driveLicences) {
          $scope.driveL = false;
          // Effacer une nouvelle formation
          $scope.removeTraining = id => {
            // Ne pas effacer le premier champ de formation
            if (id === 0) return;
            $rootScope.formData.trainings = _.reject($rootScope.formData.trainings, (training) => training.id === id);
          };

          // Effacer une experience
          $scope.removeExperience = id => {
            if (id === 0) return;
            $rootScope.formData.experiences = _.reject($rootScope.formData.experiences, experience => {
              return experience.id === id;
            });
          };

          // Generateur d'auto-completion
          $rootScope.queryItems = function ($query, taxonomy = null) {
            if (_.isNull(taxonomy)) {
              console.warn('Le taxonomie n\'est pas definie dans l\'arguments');
              return false;
            }
            return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=' + taxonomy, {
                cache: true
              })
              .then(function (response) {
                const dataTerms = response.data;
                return dataTerms.filter(function (term) {
                  let termName = term.name.toLowerCase();
                  return termName.indexOf($query.toLowerCase()) != -1;
                });
              });
          };

          // Evenement d'initialisation du controller
          this.$onInit = function () {
            $rootScope.initDatePicker();
            $rootScope.driveLicences = _.clone(driveLicences);
          };

          $rootScope.onSwitchDriveLicence = (hasDl) => {
            if (hasDl) {
              $scope.driveL = false;
            } else {
              $rootScope.formData.driveLicence = [];
            }
          };

          // Effacer tout les champs input des permis.

          $rootScope.initDatePicker = function () {
            window.setTimeout(() => {
              moment.locale('fr');
              jQuery('.input-daterange-years').datepicker({
                format: {
                  toDisplay: (date, format) => {
                    let dateFormat = new Date(date);
                    return moment(dateFormat).format('MMMM YYYY');
                  },
                  toValue: (date, format) => {
                    return new Date(date);
                  }
                },
                minViewMode: "months",
                startView: 2,
                autoclose: true,
                language: "fr",
                clearBtn: true,
              });
              jQuery('.input-daterange').datepicker({
                format: "mm/dd/yyyy",
                language: "fr",
                startView: 2,
                todayBtn: false,
                keyboardNavigation: false,
                forceParse: false,
                autoclose: true
              });

              // Trouver un pays dans la liste API
              var country = new Bloodhound({
                datumTokenizer: function (datum) {
                  return Bloodhound.tokenizers.whitespace(datum.value);
                },
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                  url: API_COUNTRY_URL + '/rest/v2/name/%QUERY',
                  prepare: function (query, settings) {
                    settings.url = settings.url.replace('%QUERY', query);
                    return settings;
                  },
                  filter: function (allCountry) {
                    return _.map(allCountry, (country) => {
                      return {
                        value: country.name
                      };
                    });
                  }
                }
              });
              // Initialize the Bloodhound suggestion engine
              country.initialize();
              // Initier l'element DOM
              jQuery('.country-apirest').typeahead(null, {
                hint: false,
                highlight: true,
                minLength: 2,
                displayKey: 'value',
                source: country.ttAdapter()
              });
            }, 600);
          }
        }
      })
      // url will be /form/interests
      .state('form.interests', {
        url: '/interests',
        templateUrl: itOptions.partials_url + '/candidate/Interests.html?version=' + itOptions.version,
        resolve: {
          access: ['$q', '$rootScope', function ($q, $rootScope) {
            if (typeof $rootScope.formData === 'undefined') {
              return $q.reject({
                redirect: 'form.informations'
              });
            }
            // Verifier les valeurs des champs (Formations, experiences & langue)
            let training = !$rootScope.formData.hasOwnProperty('trainings');
            if (training) return $q.reject({
              redirect: 'form.career'
            });
            for (let item of $rootScope.formData.trainings) {
              training = !item.hasOwnProperty('city') ||
                !item.hasOwnProperty('country') ||
                !item.hasOwnProperty('diploma') ||
                !item.hasOwnProperty('establishment');
              if (training) return $q.reject({
                redirect: 'form.career'
              });
            }
            // Verifier les valeurs du champs experiences s'il sont bien definie
            let experiences = !$rootScope.formData.hasOwnProperty('experiences');
            if (experiences) return $q.reject({
              redirect: 'form.career'
            });
            for (let item of $rootScope.formData.experiences) {
              experiences = !item.hasOwnProperty('city') ||
                !item.hasOwnProperty('country') ||
                !item.hasOwnProperty('company') ||
                !item.hasOwnProperty('positionHeld');
              if (experiences) return $q.reject({
                redirect: 'form.career'
              });
            }

            let languages = !$rootScope.formData.hasOwnProperty('languages');
            if (languages) return $q.reject({
              redirect: 'form.career'
            });
          }]
        },
        controller: ['$rootScope', '$scope', 'initScripts', 'Services',
          function ($rootScope, $scope, initScripts, Services) {
            this.$onInit = () => {
              window.setTimeout(() => {
                jQuery('.tagsinput').tagsinput({
                  tagClass: 'label label-success'
                });
                initScripts.__init__();
              }, 600);
            };

            $scope.queryJobs = function ($query) {
              return Services.getJobs($query);
            };
          }
        ]
      })

      // Default route (/form/informations)
      .state('form.informations', {
        url: '/informations',
        templateUrl: itOptions.partials_url + '/candidate/informations.html?version=' + itOptions.version,
        controller: ['$rootScope', '$http', '$state', 'initScripts',
          function ($rootScope, $http, $state, initScripts) {
            const fileFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
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
            $rootScope.imgPromise = (file) => {
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

            this.$onInit = () => {
              initScripts.__init__();
            };

            /**
             * Page suivante
             * @param {path} state 
             */
            $rootScope.next = (state) => {
              if (!$rootScope.formData.abranch) {
                alertify.error("Veillez remplir correctement les champs obligatoire");
                return false;
              }
              if (typeof $rootScope.formData.featuredImage === 'undefined') {
                alertify
                  .okBtn("Oui")
                  .cancelBtn("Plus tard")
                  .confirm("Votre profil a plus de chances d'être sélectionner si vous mettez une Photo d’identité." +
                    " <br><br> <b>Voulez vous ajouter un photo maintenant?</b>",
                    function (ev) {
                      // Oui
                      ev.preventDefault();
                    },
                    function (ev) {
                      // Plus tard
                      ev.preventDefault();
                      $state.go(state);
                    });
              } else {
                $state.go(state);
              }
            };

          }
        ]
      });

    $urlRouterProvider.otherwise('/form/informations');
  })
  .factory('initScripts', function () {
    return {
      __init__: function () {
        jQuery('.input-group.date').datepicker({
          format: "mm/dd/yyyy",
          startView: 2,
          todayBtn: false,
          keyboardNavigation: true,
          forceParse: false,
          autoclose: true
        });
      }
    }
  })
  .service('Services', ['$http', function ($http) {
    return {
      getTaxonomy: function (Taxonomy) {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=' + Taxonomy, {
            cache: true
          })
          .then(resp => {
            return resp.data;
          });
      },
      getJobs: function ($query) {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=job_sought', {
            cache: true
          })
          .then(function (response) {
            const jobs = response.data;
            return jobs.filter(_j => _j.name.toLowerCase().indexOf($query.toLowerCase()) != -1);
          });
      },
      collectCandidatInfo: function () {
        return $http.get(`${itOptions.ajax_url}?action=collect_candidat_informations`, {
            cache: true
          })
          .then(response => {
            const query = response.data;
            if (!query.success) return false;
            return query.data;
          })
      },
      getStatus: function () {
        const status = [{
            _id: 1,
            label: 'Je cherche un emploi'
          },
          {
            _id: 2,
            label: 'Je souhaite entretenir mon réseau'
          },
          {
            _id: 3,
            label: 'Je cherche un stage'
          }
        ];
        return status;
      },
      sendForm: function (formData) {
        return $http({
          url: itOptions.ajax_url,
          method: "POST",
          headers: {
            'Content-Type': undefined
          },
          data: formData
        });
      },
    };
  }])
  .directive('preUpload', [function () {
    return {
      restrict: 'AE',
      scope: true,
      link: (scope, element, attrs) => {
        element
          .bind('click', e => {
            document.querySelector('input').click();
          })
      }
    };
  }])
  .directive('inputOnChange', [function () {
    return {
      restrict: 'A',
      scope: true,
      link: (scope, element) => {
        let onChangeHandler = scope.$eval(attrs.inputOnChange);
        element.on("change", onChangeHandler);
        element.on('$destroy', function () {
          element.off();
        });
      }
    };
  }])
  .controller('formController', [
    "$scope", "$http", "$rootScope", "Services", "abranchs", "candidate", "languages", "jobSougths", "Upload",
    function ($scope, $http, $rootScope, Services, abranchs, candidate, languages, jobSougths, Upload) {
      let training_id = 0;
      let experience_id = 0;

      // we will store all of our form data in this object
      $scope.languages = _.clone(languages);
      $scope.status = Services.getStatus();
      $rootScope.abranchs = _.clone(abranchs);
      $rootScope.Candidate = _.clone(candidate);
      $rootScope.loading = false;
      $rootScope.jobSougths = _.clone(jobSougths);
      $rootScope.formData = {};
      $rootScope.isValidTag = true;
      $rootScope.select2Options = {
        allowClear: true,
        placeholder: "Selectionner",
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
      };

      $rootScope.onAddingLangTag = ($tag) => {
        return $rootScope.onAddingTag($tag);
      };

      $rootScope.onAddingJobTag = ($tag) => {
        if (_.isArray($rootScope.formData.jobSougths) && $rootScope.formData.jobSougths.length >= 2)
          return false;
        return $rootScope.onAddingTag($tag);
      };

      // Call before added tag
      $rootScope.onAddingTag = ($tag) => {
        let isValid = true;
        let splitTag = '|;_\/*';
        for (let i in splitTag) {
          let str = splitTag.charAt(i);
          if ($tag.name.indexOf(str) > -1) {
            isValid = false;
            break;
          }
        }
        if (isValid) $rootScope.isValidTag = true;
        return isValid;
      };

      // Call if tag in invalid
      $rootScope.onTagInvalid = ($tag) => {
        $rootScope.isValidTag = false;
      };

      /**
       * Recuperer les emplois et les filtres
       * @param {string} $query
       * @param {string} taxonomy
       */
      $rootScope.queryJobs = function ($query, taxonomy) {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=' + taxonomy, {
            cache: true
          })
          .then(function (response) {
            const jobs = response.data;
            return jobs.filter(function (job) {
              return job.name.toLowerCase().indexOf($query.toLowerCase()) != -1;
            });
          });
      };

      this.$onInit = () => {
        $rootScope.formData.featuredImage = {};
        if (!_.isUndefined($rootScope.Candidate.privateInformations.avatar) && !_.isNull($rootScope.Candidate.privateInformations.avatar))
          $rootScope.formData.featuredImage.src = $rootScope.Candidate.privateInformations.avatar[0];

        let abranch = $rootScope.Candidate.branch_activity;
        if (!_.isUndefined(abranch) && !_.isNull(abranch) && !_.isEmpty(abranch)) {
          $rootScope.formData.abranch = $rootScope.Candidate.branch_activity.term_id;
        }

        let status = $rootScope.Candidate.status
        if (!_.isUndefined(status) && !_.isNull(status) && !_.isEmpty(status)) {
          $rootScope.formData.status = parseInt($rootScope.Candidate.status.value);
        }
      };

      $rootScope.formData.trainings = [{
        id: training_id,
        start: 2018,
        end: 2018
      }];

      // Ajouter une formation
      $rootScope.addNewTraining = function () {
        training_id += 1;
        $rootScope.formData.trainings.push({
          id: training_id,
          start: 0,
          end: 0
        });
        $rootScope.initDatePicker();
      };

      $rootScope.formData.experiences = [{
        id: experience_id,
        start: '08/08/2018'
      }];

      // Ajouter une nouvelle experience
      $rootScope.addNewExperience = function () {
        experience_id += 1;
        $rootScope.formData.experiences.push({
          id: experience_id,
          start: '',
          end: ''
        });
        $rootScope.initDatePicker();
      };

      $scope.uploadFiles = function (file, errFiles) {
        $rootScope.f = file;
        $scope.errFile = errFiles && errFiles[0];
        if (_.isNull(file)) return;
        $rootScope.imgPromise($rootScope.f)
          .then(result => {
            $rootScope.$apply(() => {
              $rootScope.formData.featuredImage = angular.copy(result);
            });
          })
          .catch(e => {
            swal({
              title: 'Erreur',
              text: e,
              type: 'error',
            });
          });
      };

      // function to process the form
      $scope.processForm = function (isValid) {
        if (!isValid) return;
        $rootScope.loading = true;
        // Crée une formulaire
        var dataForm = new FormData();
        dataForm.append('action', 'update_user_cv');
        var formKeys = Object.keys($rootScope.formData);
        // Mettre en format internationnal les dates
        let Experiences = $rootScope.formData.experiences;
        let Trainings = $rootScope.formData.trainings;
        moment.locale('fr');
        $rootScope.formData.experiences = [];
        for (let Experience of Experiences) {
          Experience.start = moment(Experience.start, 'MMMM YYYY', 'fr').format('MM/DD/YYYY');
          Experience.end = moment(Experience.end, 'MMMM YYYY', 'fr').format('MM/DD/YYYY');
          $rootScope.formData.experiences.push(Experience);
        }

        $rootScope.formData.trainings = [];
        for (let Training of Trainings) {
          Training.start = moment(Training.start, 'MMMM YYYY', 'fr').format('MM/DD/YYYY');
          Training.end = moment(Training.end, 'MMMM YYYY', 'fr').format('MM/DD/YYYY');
          $rootScope.formData.trainings.push(Training);
        }

        angular.forEach(formKeys, (property) => {
          var value = Reflect.get($rootScope.formData, property);
          dataForm.set(property, JSON.stringify(value));
        });

        if ($rootScope.f) {
          $rootScope.f.upload = Upload.upload({
            url: itOptions.ajax_url,
            data: {
              file: $rootScope.f,
              action: 'ajx_upload_media'
            }
          });

          $rootScope.f.upload
            .then(function (response) { // Success
              $rootScope.f.result = response.data;
              $scope.__sendForm(dataForm);
            }, function (response) { // Error
              if (response.status > 0)
                $scope.errorMsg = response.status + ': ' + response.data;
            }, function (evt) { // Progress
              $rootScope.f.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        } else {
          $scope.__sendForm(dataForm);
        }
      };

      $scope.__sendForm = (dataForm) => {
        Services
          .sendForm(dataForm)
          .then(resp => {
            const Data = resp.data;
            if (Data.success) {
              swal({
                title: 'Reussi',
                text: "CV bien ajouté",
                type: "info",
              }, function () {
                $rootScope.loading = false;
                window.location.href = itOptions.urlHelper.redir;
              });
            } else {
              swal({
                title: 'Erreur',
                text: Data.msg,
                type: "error",
              });
              $rootScope.loading = false;
            }
          })
          .catch(e => {
            swal({
              title: 'Erreur',
              text: e,
              type: "error",
            });
            $rootScope.loading = false;
          });
      };

      $rootScope.$watch('formData', value => {
        console.log(value);
      }, true);


    }
  ]).run(["$state", function ($state) {
    $state.defaultErrorHandler(function (error) {
      // This is a naive example of how to silence the default error handler.
      if (error.detail !== undefined) {
        $state.go(error.detail.redirect);
      }
    });
  }]);