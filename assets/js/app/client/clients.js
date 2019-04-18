const APPOC = angular.module('clientApp', ['ngMessages', 'ui.select2', 'ui.tinymce', 'ui.router', 'ngTagsInput', 'ngSanitize', 'ngFileUpload'])
  .config(['$stateProvider', function ($stateProvider) {
    const states = [
      {
        name: 'manager.profil.works',
        url: '/works',
        templateUrl: `${itOptions.Helper.tpls_partials}/works.html?ver=${itOptions.version}`,
        controller: ["$rootScope", "$state", function ($rootScope, $state) {
          this.$onInit = () => {
            $state.go('manager.profil.works.lists');
          };
        }]
      },
      {
        name: 'manager.profil.works.lists',
        url: '/lists',
        templateUrl: `${itOptions.Helper.tpls_partials}/work-lists.html?ver=${itOptions.version}`,
        controller: ["$rootScope", function ($rootScope) {
        }]
      },
      {
        name: 'manager.profil.works.featured',
        url: '/{id}/featured',
        resolve: {
          $positions: ["$rootScope", "$http", function ($rootScope, $http) {
            $rootScope.preloaderToogle();
            return $http.get(`${itOptions.Helper.ajax_url}?action=collect_support_featured&type=works`).then(results => {
              $rootScope.preloaderToogle();
              return results.data;
            });
          }],
          Works: ['$rootScope', '$transition$', function ($rootScope, $transition$) {
            let workId = $transition$.params().id;
            return $rootScope.WPEndpoint.works().id(workId).then(works => {
              return works;
            })
          }]
        },
        templateUrl: `${itOptions.Helper.tpls_partials}/work-featured.html?ver=${itOptions.version}`,
        controller: ["$rootScope", "$scope", "Works", "$positions", "$http", function ($rootScope, $scope, Works, $positions, $http) {
          $scope.supportFeatured = [];
          $scope.tariff = [];
          $scope.works = {};
          this.$onInit = () => {
            moment.locale("fr");
            let featured = _.clone($rootScope.options.featured);
            $scope.supportFeatured = _.clone($positions.data);
            $scope.works = _.clone(Works);
            $scope.tariff = _.map(featured.work_tariff, (tarif) => {
              let support = _.findWhere($scope.supportFeatured, {slug: tarif.ugs});
              tarif.available = support.counts >= 4 ? 0 : 1;
              return tarif;
            });
          };

          $scope.checkout = (ugs, price) => {
            const key = $rootScope.options.wc._k;
            const secret = $rootScope.options.wc._s;
            let support = _.findWhere($scope.supportFeatured, {slug: ugs});
            if (!support || support.counts === 4) return false;
            $rootScope.preloaderToogle();
            let work = _.findWhere($rootScope.options.featured.work_tariff, {ugs: ugs});
            $rootScope.WPEndpoint
              .product()
              .param('consumer_key', key)
              .param('consumer_secret', secret)
              .create({
                status: 'publish',
                type: 'simple',
                name: `TRAVAIL TEMPORAIRE SPONSORISE (${$scope.works.title.rendered}) - ${work.position}`,
                price: price.toString(), // string accepted
                regular_price: price.toString(), // string accepted
                sold_individually: true,
                virtual: true,
                sku: `FEATURED${$scope.works.id}`,
                meta_data: [
                  {key: '__type', value: 'featured'},
                  {key: '__id', value: $scope.works.id}
                ]
              })
              .then(product => {
                $scope.$apply(() => {
                  $rootScope.preloaderToogle();
                  $scope.addProductCart(product.id, work.ugs);
                });
              })
              .catch(err => {
                if (!_.isUndefined(err.code)) {
                  if (err.code === "product_invalid_sku") {
                    let resource_id = err.data.resource_id;
                    $scope.$apply(() => {
                      $scope.WPEndpoint
                        .product()
                        .param('consumer_key', key)
                        .param('consumer_secret', secret)
                        .id(resource_id)
                        .update({price: price.toString(), regular_price: price.toString()})
                        .then(product => {
                          $scope.addProductCart(resource_id, work.ugs);
                        });
                    });
                  }
                }
              })
          }; // .end checkout

          $scope.addProductCart = (resource_id, ugs) => {
            $http.get(`${itOptions.Helper.ajax_url}?action=add_cart&product_id=${resource_id}`, {cache: false})
              .then((resp) => {
                const response = resp.data;
                $scope.WPEndpoint
                  .works()
                  .id($scope.works.id)
                  .update({
                    featured_position: ugs,
                    featured_datelimit: moment().format("YYYY-MM-DD H:mm:ss")
                  })
                  .then(() => {
                    if (response.success) {
                      $scope.$apply(() => {
                        swal("Redirection", "Vous serez rediriger vers la page de paiement");
                        $rootScope.preloaderToogle();
                        setTimeout(() => {
                          window.location.href = response.data;
                        }, 2000);
                      });
                    }
                  })
              });
          } // .end addProductCart

        }]
      }
    ];
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
  }])
  .factory('clientFactory', ['$http', function ($http) {
    return {
      getCity: function () {
        return $http.get(itOptions.Helper.ajax_url + '?action=get_city', {cache: true})
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
      },
      getOptions: function () {
        return $http.get(itOptions.Helper.rest_options, {cache: true}).then(resp => resp.data);
      }
    };
  }])
  .service('clientService', ['$rootScope', function ($rootScope) {
    this.offers = _.clone(itOptions.offers);
    this.months = [
      'janvier', 'février', 'mars',
      'avril', 'mai', 'juin',
      'juillet', 'août', 'septembre',
      'octobre', 'novembre', 'décembre'
    ];
    this.setLoading = (loading) => {
      $rootScope.preLoader = true;
    };
  }])
  .filter('FormatStatus', [function () {
    let status = [
      {
        slug: 'pending',
        label: 'En attente'
      },
      {
        slug: 'validated',
        label: 'Confirmer'
      },
      {
        slug: 'reject',
        label: 'Rejeter'
      },
    ];
    return (entryValue) => {
      if (typeof entryValue === 'undefined') return entryValue;
      return _.findWhere(status, {
        slug: jQuery.trim(entryValue)
      }).label;
    }
  }])
  .filter('moment', [function () {
    moment.locale('fr');
    return (entry) => {
      if (_.isEmpty(entry)) return entry;
      return moment(entry, "MM/DD/YYYY", true).format("MMMM YYYY");
    }
  }])
  .filter('experience_date', [function () {
    moment.locale('fr');
    return (experience, handler) => {
      if (!_.isObject(experience)) return experience;
      let date;
      if (handler === 'begin') {
        let dateBegin = experience.exp_dateBegin;
        date = _.isNull(dateBegin) || _.isEmpty(dateBegin) || dateBegin === 'Invalid date' ? experience.old_value.exp_dateBegin : experience.exp_dateBegin;
      } else {
        let dateEnd = experience.exp_dateEnd;
        date = _.isNull(dateEnd) || _.isEmpty(dateEnd) || dateEnd === 'Invalid date' ? experience.old_value.exp_dateEnd : experience.exp_dateEnd;
      }
      date = _.isNull(date) ? '' : date;
      date = date.indexOf('/') > -1 ? moment(date) : moment(date, 'MMMM YYYY', true);
      return date.isValid() ? date.format('MMMM YYYY') : 'n/a';
    }
  }])
  .filter('moment_birthday', [function () {
    return (entry) => {
      if (_.isEmpty(entry)) return entry;
      return moment(entry, 'DD/MM/YYYY', 'fr').format('dddd DD MMMM YYYY');
    }
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
      if (_.isUndefined(inputValue)) return inputValue;
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
  .filter('currency', [function () {
    return (entry) => {
      let numb = parseInt(entry);
      return new Intl.NumberFormat('de-DE', {
        style: "currency",
        minimumFractionDigits: 0,
        currency: 'MGA'
      }).format(numb);
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
                pwdpattern: "Votre mot de passe doit comporter 8 caractères minimum, comprenant des chiffres et des lettres minuscules et" +
                " majuscules, ainsi 1 caractère spécial (-*/@+\_%$=).",
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
      },
      controller: ['$scope', '$rootScope', '$http', function ($scope, $rootScope, $http) {
        this.$onInit = () => {

        };
        /**
         * Mettre a jour les alerts (Ajouter, Supprimer)
         * Une alerte permet de notifier l'utilisateur par email
         * Si une publication (offre, annonce, travaille temporaire) comportent ces mots
         */
        $rootScope.onSaveAlert = () => {
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
      }]
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
            }, (error) => {
            });
        });

      }]
    }
  }])
  .directive('smallAd', [function () {
    return {
      restrict: 'E',
      templateUrl: `${itOptions.Helper.tpls_partials}/small-annonce.html?ver=${itOptions.version}`,
      scope: true,
      controller: ['$rootScope', '$scope', '$q', '$http', '$state', function ($rootScope, $scope, $q, $http, $state) {
        $scope.Works = [];
        $scope.Loading = false;
        this.$onInit = () => {
          $scope.Loading = true;
          $http.get(`${itOptions.Helper.ajax_url}?action=collect_works`, {cache: false})
            .then(resp => {
              const query = resp.data;
              if (query.success) {
                moment.locale('fr');
                const table = jQuery('#small-ad-table')
                  .DataTable({
                    pageLength: 10,
                    fixedHeader: false,
                    responsive: false,
                    dom: '<"top"i><"info"r>t<"bottom"flp><"clear">',
                    data: query.data,
                    columns: [
                      {
                        data: 'title', render: (data, type, row) => {
                          let activate = (row.status === 'publish' && row.activated) ? 1 : 0;
                          if (!activate) return data;
                          return `<a href="${row.url}" target="_blank">${data}</a>`
                        }
                      },
                      {data: 'reference', render: (data) => `<span class="badge badge-info">${data}</span>`},
                      {
                        data: 'status', render: (data, type, row, meta) => {
                          var activated = row.activated;
                          var text = data === 'pending' && !activated ? 'En attente' : (data === 'publish' && activated ? 'Publier' : 'Désactiver');
                          var style = data === "publish" && activated ? 'blue' : 'warning';
                          return `<span class="badge badge-${style}"> ${text} </span>`;
                        }
                      },
                      {data: 'region', render: (data) => data.name},
                      {
                        data: 'featured',
                        render: (data, type, row) => {
                          var text = data ? (!_.isEmpty(row.featured_position) || _.isNull(row.featured_position) ? (row.featured_position === 1 ? 'à la une' : 'la liste') : 'erreur') : 'aucun';
                          var style = data ? "success" : "default";
                          var elClass = style === 'default' ? 'featured-paiement' : '';
                          return `<span class="badge-${style} ${elClass} edit-position badge uppercase">${text}</span>`;
                        }
                      },
                      {
                        data: 'date_publication', render: (data) => {
                          return moment(data).format('LLL');
                        }
                      },
                    ],
                    "sDom": 'rtip',
                    language: {
                      url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
                    }
                  });

                const $ = jQuery.noConflict();
                $("#small-ad-table tbody").on('click', '.edit-position', e => {
                  var el = $(e.currentTarget).parents('tr');
                  var Column = table.row(el).data();
                  $state.go('manager.profil.works.featured', {id: Column.ID});
                });

                $scope.Loading = false;
              } else {
                $scope.loading = false;
              }
            });
        };
      }]
    }
  }])
  .directive('annonces', [function () {
    return {
      restrict: 'E',
      templateUrl: `${itOptions.Helper.tpls_partials}/annonces.html?ver=${itOptions.version}`,
      scope: true,
      controller: ['$scope', '$q', '$http', function ($scope, $q, $http) {
        $scope.Works = [];
        $scope.Loading = false;
        this.$onInit = () => {
          $scope.Loading = true;
          $http.get(`${itOptions.Helper.ajax_url}?action=collect_annonces`, {
            cache: false
          })
            .then(resp => {
              const query = resp.data;
              if (query.success) {
                moment.locale('fr');
                const table = jQuery('#annonce-table')
                  .DataTable({
                    pageLength: 10,
                    fixedHeader: false,
                    responsive: false,
                    dom: '<"top"i><"info"r>t<"bottom"flp><"clear">',
                    data: query.data,
                    columns: [
                      {
                        data: 'title', render: (data, type, row) => {
                          let activate = (row.status === 'publish' && row.activated) ? 1 : 0;
                          if (!activate) return data;
                          return `<a href="${row.url}" target="_blank">${data}</a>`
                        }
                      },
                      {data: 'reference', render: (data) => `<span class="badge badge-info">${data}</span>`},
                      {
                        data: 'status', render: (data, type, row, meta) => {
                          var activated = row.activated;
                          var text = data === 'pending' && !activated ? 'En attente' : (data === 'publish' && activated ? 'Publier' : 'Désactiver');
                          return `<span class="badge badge-pill badge-default"> ${text} </span>`;
                        }
                      },
                      {data: 'region', render: (data) => data.name},
                      {
                        data: 'featured',
                        render: (data) => data ? `<span class="badge badge-blue uppercase">à la une</span>` :
                          `<span class="badge-default badge uppercase">standard</span>`
                      },
                      {
                        data: 'date_publication', render: (data) => {
                          return moment(data).format('LLL');
                        }
                      },
                    ],
                    "sDom": 'rtip',
                    language: {
                      url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
                    }
                  });

                $scope.Loading = false;
              } else {
                $scope.loading = false;
              }
            });
        };
      }]
    }
  }])
  .run(['$rootScope', '$state', '$filter', function ($rootScope, $state, $filter) {
    $rootScope.preLoader = false;
    $rootScope.WPEndpoint = null;

    this.$onInit = () => {

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

    /**
     * Upload featured image
     * @param file
     * @param errFiles
     */
    $rootScope.uploadImage = function (file, errFiles) {
      $rootScope.avatarFile = file;
      if (_.isNull(file)) return;
      imgPromise(file)
        .then(result => {
          $rootScope.$apply(() => {
            $rootScope.profilEditor.featuredImage = result.src;
          });
        })
        .catch(e => {
          alertify.error(e);
        });
    };

    $state.defaultErrorHandler(function (error) {
      // This is a naive example of how to silence the default error handler.
      if (error.detail !== undefined) {
        $state.go(error.detail.redirect);
      }
    });

  }
  ]);