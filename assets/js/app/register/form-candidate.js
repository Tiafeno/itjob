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
          access: ['$q', function ($q) {
          }]
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
          access: ['$q', '$rootScope', '$state', function ($q, $rootScope, $state) {
            if (typeof $rootScope.formData === 'undefined') {
              //return $q.reject({redirect: 'form.informations'});
            }
            const formDataKeys = Object.keys($rootScope.formData);
            if (formDataKeys.length >= 3) {
              return $q.resolve(true);
            } else {
              //return $q.reject({redirect: 'form.informations'});
            }
          }]
        },
        controller: function ($rootScope, $state) {
          let self = this;
          let training_id = 0;
          $rootScope.formData.trainings = [{id: training_id, start: '08/08/2018', end: '08/13/2018'}];
          $rootScope.addNewTraining = () => {
            training_id += 1;
            $rootScope.formData.trainings.push({
              id: training_id,
              start: '',
              end: ''
            });
            self.initDatePicker();
          };

          $rootScope.removeTraining = id => {
            // Ne pas effacer le premier champ de formation
            if (id ===0) return;
            $rootScope.formData.trainings = _.reject($rootScope.formData.trainings, (training) =>  training.id === id);
          };

          this.$onInit = function () {
            self.initDatePicker();
          };

          self.initDatePicker = function () {
            window.setTimeout(() => {
              jQuery('.input-daterange').datepicker({
                format: "dd/mm/yyyy",
                keyboardNavigation: false,
                forceParse: false,
                autoclose: true,
                language: "fr",
                clearBtn: true,
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
          access: ['$q', function ($q) {
            if (!0) {
              return $q.reject("Not Authorized");
            }
          }]
        },
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
                    })
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
            return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=job_sought', {cache: true})
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
                })
            })
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
          })
        });
      }
    }
  })
  .service('Services', ['$http', '$q', function ($http, $q) {
    return {
      getTaxonomy: function (Taxonomy) {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=' + Taxonomy, {cache: true})
          .then(resp => {
            return resp.data;
          });
      },
      getStatus: function () {
        const status = [
          {_id: 0, label: 'Je cherche un emploi'},
          {_id: 1, label: 'Je souhaite entretenir mon réseau'},
          {_id: 2, label: 'Je cherche un stage'}
        ];
        return status;
      }
    }
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
    }
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
    }
  }])
  .controller('formController', function ($scope, $rootScope, $state, initScripts, Services, abranchs, languages, jobSougths) {
    const self = this;

    $scope.abranchs = _.clone(abranchs);
    $scope.languages = _.clone(languages);
    $rootScope.jobSougths = _.clone(jobSougths);
    $scope.status = Services.getStatus();

    // we will store all of our form data in this object
    $rootScope.formData = {};

    // function to process the form
    $scope.processForm = function () {
      alert('awesome!');
    };
  })
  .run(function ($state) {
    $state.defaultErrorHandler(function (error) {
      // This is a naive example of how to silence the default error handler.
      if (error.detail !== undefined)
        $state.go(error.detail.redirect);
    });
  });