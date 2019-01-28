const APPOC = angular.module('clientApp', ['ngMessages', 'ui.select2', 'ui.tinymce', 'ngRoute', 'ngTagsInput', 'ngSanitize', 'ngFileUpload'])
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
    const Greeting = [
      {
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
    const postStatus = [
      {
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
  .filter('moment_notice', [function () {
    return (entry) => {
      if (_.isEmpty(entry)) return entry;
      return moment(entry, 'YYYY-MM-DD h:mm:ss', 'fr').fromNow();
    }
  }])
  .directive('changePassword', ['$http', function ($http) {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/change-password.html?ver=' + itOptions.version,
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
      templateUrl: itOptions.Helper.tpls_partials + '/alert.html?version=' + itOptions.version,
      scope: {
        onSave: '&', // Function pass
        alerts: '=', // Two way variable pass
        message: '@', // String pass
        alertLoading: '='
      }
    };
  }])
  .directive('notifications', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/notifications.html?ver=' + itOptions.version,
      scope: true,
      controller: ['$scope', '$q', '$http', function ($scope, $q, $http) {
        $scope.Notices = [];
        $scope.Loading = false;

        jQuery('#modal-notification-overflow').on('show.bs.modal', (e) => {
          $scope.Loading = true;
          $http.get(`${itOptions.Helper.ajax_url}?action=collect_current_user_notices`, {
              cache: false
            })
            .then(response => {
              let query = response.data;
              $scope.Notices = _.map(query.data, (data) => {
                data.status = parseInt(data.status);
                data.ID = parseInt(data.ID);
                data.url = `${data.guid}&notif_id=${data.ID}`;
                return data;
              });
              $scope.Loading = false;
            }, (error) => {});
        });

      }]
    }
  }])
  .controller('clientCtrl', ['$scope', '$http', '$q', '$filter', 'clientFactory', 'clientService', 'Client', 'Upload',
    function ($scope, $http, $q, $filter, clientFactory, clientService, Client, Upload) {
      const self = this;
      // Contient les valeurs d'introduction
      $scope.profilEditor = {};
      $scope.profilEditor.loading = false;
      $scope.profilEditor.form = {};
      $scope.alertLoading = false; // Directive alert
      $scope.alerts = [];
      $scope.jobSearchs = [];
      $scope.Helper = {};
      $scope.Greet = '';
      $scope.preloader = false;
      $scope.select2Options = {
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
          rg = _.findWhere($scope.profilEditor.regions, {
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
            $scope.profilEditor.loading = false;
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
            $scope.profilEditor.loading = false;
          });
      };

      self.send

      /**
       * Récuperer les données sur le client
       */
      $scope.Initialize = () => {
        console.info('Client init...');
        $scope.preloaderToogle();
        if (Client.post_type === 'company') {
          $scope.Company = _.clone(Client.iClient);
          $scope.Helper = _.clone(Client.Helper);
          $scope.offerLists = _.clone(Client.Offers);
          $scope.candidateLists = _.clone(Client.ListsCandidate);
          let greeting = $scope.Company.greeting;
          $scope.Greet = !_.isEmpty(greeting) ? $filter('Greet')($scope.Company.greeting).toLowerCase() : '';
          if (_.isNull($scope.Company.branch_activity) || !$scope.Company.branch_activity || !$scope.Company.country ||
            !$scope.Company.region || _.isEmpty($scope.Company.greeting)) {
            $q.all([
                $scope.asyncTerms('branch_activity'),
                $scope.asyncTerms('region'),
                $scope.asyncTerms('city')
              ])
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
                $scope.preloaderToogle();
              }, error => {
                $scope.preloaderToogle();
                swal('Information', "Erreur de chargement de la page. Récupération de la page en cours...", 'info');
                setTimeout(() => {
                  location.reload();
                }, 2000);
              });
          } else {
            $scope.preloaderToogle();
          }

        } else {
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
          let greeting = $scope.Candidate.greeting;
          $scope.Greet = _.isObject(greeting) ? $filter('Greet')($scope.Candidate.greeting.value).toLowerCase() : '';
          $scope.cv.hasCV = $scope.Candidate.has_cv;
          const region = $scope.Candidate.privateInformations.address.region;
          const country = $scope.Candidate.privateInformations.address.country;
          const address = $scope.Candidate.privateInformations.address.address;
          const abranch = $scope.Candidate.branch_activity;
          let updateActivity = $scope.Candidate.has_cv ? (!!(_.isNull(abranch) || !abranch)) : false;

          if (!country || !region || _.isEmpty($scope.Candidate.greeting) || !address || updateActivity) {
            $q.all([
                $scope.asyncTerms('branch_activity'),
                $scope.asyncTerms('region'),
                $scope.asyncTerms('city')
              ])
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
                $scope.profilEditor.form.country = _.isEmpty(country) || _.isNull(country) ? '' : country.term_id;
                if (!_.isNull(region) || region) {
                  $scope.profilEditor.form.region = region.term_id;
                } else {
                  // Effacer la valeur d'une ville si la region n'est pas definie
                  $scope.profilEditor.form.country = '';
                }
                $scope.profilEditor.form.name = `${$scope.Candidate.privateInformations.firstname} ${$scope.Candidate.privateInformations.lastname}`;
                $scope.profilEditor.form.email = $scope.Candidate.privateInformations.author.data.user_email;
                // Récuperer l'adresse
                $scope.profilEditor.form.address = _.isEmpty(address) || _.isNull(address) ? '' : address;
                $scope.preloaderToogle()
                UIkit.modal('#modal-information-editor').show();
              })
          } else {
            $scope.preloaderToogle()
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
          }, error => {
            alertify.error("Une erreur s'est produite,  veuillez réessayer ultérieurement.");
            $scope.alertLoading = false;
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
        let hasStatus = !_.isNull($scope.Candidate.status) && !_.isEmpty($scope.Candidate.status) && !_.isUndefined($scope.Candidate.status);
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
              //if (!$scope.editProfilForm.$dirty) return;
              $scope.profilEditor.loading = true;
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
                    if (response.status > 0) {
                      alertify.error(response.status + ': ' + response.data);
                      $scope.profilEditor.loading = false;
                    }
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
          .then(
            resp => {
              let data = resp.data;
              if (!data.success) return;
              UIkit.modal('#modal-candidate-profil-editor').hide();
              $scope.profilEditor.loading = false;
              location.reload();
            }, error => {
              swall("Erreur", "Une erreur s'est produite, veuillez réessayer ultérieurement.", "error");
              $scope.profilEditor.loading = false;
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