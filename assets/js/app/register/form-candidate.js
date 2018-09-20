const API_COUNTRY_URL = 'https://restcountries.eu';

angular.module('formCandidateApp', ['ngAnimate', 'ui.router', 'ngTagsInput'])
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
          access: ['$q', function ($q) {}]
        },
        controller: 'formController'
      })
      // nested states
      // each of these sections will have their own view
      // url will be nested (/form/career)
      .state('form.career', {
        url: '/career',
        templateUrl: itOptions.partials_url + '/candidate/career.html',
        resolve: {
          access: ['$q', '$rootScope', function ($q, $rootScope) {

            if (typeof $rootScope.formData === 'undefined') {
              return $q.reject({
                redirect: 'form.informations'
              });
            }
            if (typeof $rootScope.formData.status === 'undefined' ||
              typeof $rootScope.formData.jobSougths === 'undefined' ||
              _.isEmpty($rootScope.formData.jobSougths) ||
              typeof $rootScope.formData.abranch === 'undefined') {

              return $q.reject({
                redirect: 'form.informations'
              });
            }
          }],
          driveLicences: function ($q) {
            const licence = [{
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
              },
            ];
            return $q.resolve(licence);
          }
        },
        controller: function ($rootScope, $scope, $http, driveLicences) {
          let self = this;

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

          
          // Rechercher les langues
          $rootScope.queryLanguages = function ($query) {
            return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=language', {
                cache: true
              })
              .then(function (response) {
                const languages = response.data;
                return languages.filter(function (language) {
                  return language.name.toLowerCase().indexOf($query.toLowerCase()) != -1;
                });
              });
          };


          this.$onInit = function () {
            $rootScope.initDatePicker();
            $rootScope.driveLicences = _.clone(driveLicences);
          };

          $rootScope.initDatePicker = function () {
            window.setTimeout(() => {
              jQuery('.input-daterange').datepicker({
                format: "dd/mm/yyyy",
                keyboardNavigation: false,
                forceParse: false,
                autoclose: true,
                language: "fr",
                clearBtn: true,
              });

              // Trouver un pays dnas la liste API
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
        templateUrl: itOptions.partials_url + '/candidate/interests.html',
        resolve: {
          access: ['$q', '$rootScope', function ($q, $rootScope) {
            if (typeof $rootScope.formData === 'undefined') {
              return $q.reject({
                redirect: 'form.informations'
              });
            }

            // Verifier les valeurs des champs (Formations, experiences & langue)
            let training = !$rootScope.formData.hasOwnProperty('trainings');
            if (training) return $q.reject({redirect: 'form.career'});
            for (let item of $rootScope.formData.trainings) {
              training = !item.hasOwnProperty('city') ||
              !item.hasOwnProperty('country') ||
              !item.hasOwnProperty('diploma') ||
              !item.hasOwnProperty('establishment');
              if (training) return $q.reject({redirect: 'form.career'});
            }

            let languages = !$rootScope.formData.hasOwnProperty('languages');
            if (languages) return $q.reject({redirect: 'form.career'});

            let experiences = !$rootScope.formData.hasOwnProperty('experiences');
            if (experiences) return $q.reject({redirect: 'form.career'});
            for (let item of $rootScope.formData.experiences) {
              experiences = !item.hasOwnProperty('city') || 
              !item.hasOwnProperty('country') ||
              !item.hasOwnProperty('company') || 
              !item.hasOwnProperty('positionHeld');
              if (experiences) return $q.reject({redirect: 'form.career'});

            }
          }]
        },
        controller: ['$rootScope', '$scope', 'initScripts', 'Services', function ($rootScope, $scope, initScripts, Services) {
          var self = this;
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
        }]
      })

      // Default route (/form/informations)
      .state('form.informations', {
        url: '/informations',
        templateUrl: itOptions.partials_url + '/candidate/informations.html',

        controller: function ($rootScope, $http, $window, initScripts) {

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
          const imgPromise = (file) => {
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
          //$rootScope.formData.jobSougths = Services.getTaxonomy('job_sought');
          $rootScope.queryJobs = function ($query) {
            return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=job_sought', {
                cache: true
              })
              .then(function (response) {
                const jobs = response.data;
                return jobs.filter(function (job) {
                  return job.name.toLowerCase().indexOf($query.toLowerCase()) != -1;
                });
              });
          };

          $rootScope.previewFeaturedFile = (event) => {
            const element = event.target;
            if (element.files.length === 0) {
              $rootScope.$apply(() => {
                $rootScope.formData.featuredImage = {};
              });
              return;
            }
            angular.forEach(element.files, file => {
              if (!fileFilter.test(file.type)) {
                swal({
                  title: 'Erreur',
                  text: "Le fichier sélectionné est invalide",
                  type: 'error',
                });
                return;
              }
              imgPromise(file)
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
            });
          };
        }
      });

    $urlRouterProvider.otherwise('/form/informations');
  })
  .factory('initScripts', function () {
    return {
      __init__: function () {
        jQuery('.input-group.date').datepicker({
          format: "dd-mm-yyyy",
          startView: 2,
          todayBtn: false,
          keyboardNavigation: true,
          forceParse: false,
          autoclose: true
        });

        var jqSelects = jQuery("select.form-control");
        jQuery.each(jqSelects, (index, element) => {
          var selectElement = jQuery(element);
          var placeholder = (selectElement.attr('title') === undefined) ? 'Please select' : selectElement.attr('title');
          jQuery(element).select2({
            placeholder: placeholder,
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
      }
    }
  })
  .service('Services', ['$http', '$q', function ($http, $q) {
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
            return jobs.filter(function (_j) {
              return _j.name.toLowerCase().indexOf($query.toLowerCase()) != -1;
            });
          });
      },
      getStatus: function () {
        const status = [{
            _id: 0,
            label: 'Je cherche un emploi'
          },
          {
            _id: 1,
            label: 'Je souhaite entretenir mon réseau'
          },
          {
            _id: 2,
            label: 'Je cherche un stage'
          }
        ];
        return status;
      }
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
      link: (scope, element, attrs) => {
        let onChangeHandler = scope.$eval(attrs.inputOnChange);
        element.on("change", onChangeHandler);
        element.on('$destroy', function () {
          element.off();
        });
      }
    };
  }])
  .controller('formController', function ($scope, $rootScope, $state, initScripts, Services, abranchs, languages, jobSougths) {
    let training_id = 0;
    let experience_id = 0;
    const self = this;

    // we will store all of our form data in this object
    $rootScope.formData = {};

    $rootScope.formData.trainings = [{
      id: training_id,
      start: '08/08/2018',
      end: '08/13/2018'
    }];

    $rootScope.formData.experiences = [{
      id: experience_id,
      start: '08/08/2018',
      end: '08/13/2018'
    }];

    // Ajouter une formation
    $rootScope.addNewTraining = function () {
      training_id += 1;
      $rootScope.formData.trainings.push({
        id: training_id,
        start: '',
        end: ''
      });
      $rootScope.initDatePicker();
    };

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

    $scope.abranchs = _.clone(abranchs);
    $scope.languages = _.clone(languages);
    $rootScope.jobSougths = _.clone(jobSougths);
    $scope.status = Services.getStatus();

    // function to process the form
    $scope.processForm = function (isValid) {
      console.log($rootScope.formData);
      
      if (!isValid) return;
      alert('awesome!');
    };

    $rootScope.$on('$stateChangeStart', function (event, toState, toParams, fromState, fromParams, options) {
      console.log($rootScope.cvForm.$invalid);
      event.preventDefault();
    });
  })
  .run(function ($state, $rootScope) {
    $state.defaultErrorHandler(function (error) {
      // This is a naive example of how to silence the default error handler.
      if (error.detail !== undefined) {
        $state.go(error.detail.redirect);
      }

    });

    $rootScope.$on('$stateChangeStart', function (evt, toState, toParams, fromState, fromParams) {
      //alert("$stateChangeStart " + fromState.name + JSON.stringify(fromParams) + " -> " + toState.name + JSON.stringify(toParams));
    });
    /* if ( ! $rootScope.cvForm.$invalid) {
      return $q.resolve(true);
    } else {
      return $q.reject({redirect: 'form.informations'});
    } */
  });