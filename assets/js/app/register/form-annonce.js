var companyApp = angular.module('AnnonceApp', ['ui.router', 'ngMessages', 'ui.tinymce', 'ngCookies', 'ngSanitize'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    const states = [
      {
        name: 'annonce',
        url: '/annonce',
        templateUrl: itOptions.helper.partials + '/annonce/annonce.html?ver=' + itOptions.version,
        controller: 'annonceController'
      },
      {
        name: 'annonce.form',
        url: '/form',
        templateUrl: itOptions.helper.partials + '/annonce/form.html?ver=' + itOptions.version,
        resolve: {
          abranchs: function (Services) {
            return Services.getBranchActivity();
          },
          regions: ['$http', function ($http) {
            return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=region', {cache: true})
              .then(function (resp) {
                return resp.data;
              });
          }],
          categories: ['$http', function ($http) {
            return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=categorie', {cache: true})
              .then(function (resp) {
                return resp.data;
              });
          }],
          allCity: ['$http', function ($http) {
            return $http.get(itOptions.ajax_url + '?action=get_city', {cache: true})
              .then(function (resp) {
                return resp.data;
              });
          }]
        },
        controller: ['$rootScope', '$scope', '$cookies', '$state', 'Factory', 'regions', 'abranchs', 'allCity', 'categories',
          function ($rootScope, $scope, $cookies, $state, Factory, regions, abranchs, allCity, categories) {
            this.$onInit = () => {
              $scope.regions = _.clone(regions);
              $scope.abranchs = _.clone(abranchs);
              $scope.allCity = _.clone(allCity);
              $scope.categories = _.clone(categories);
            };
            $rootScope.tinymceOptions = {
              language: 'fr_FR',
              menubar: false,
              plugins: ['lists', 'paste'],
              theme_advanced_buttons3_add : "pastetext,pasteword,selectall",
              paste_auto_cleanup_on_paste : true,
              paste_remove_styles_if_webkit: true,
              paste_remove_styles: true,
              paste_postprocess : function(pl, o) {
              },
              content_css: [
                '//fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i',
                itOptions.template_url + '/assets/vendors/tinymce/css/content.min.css'
              ],
              selector: 'textarea',
              toolbar: 'undo redo | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat '
            };
            $scope.submitForm = function (Form) {
              if (Form.$invalid) {
                angular.forEach(Form.$error, function (field) {
                  angular.forEach(field, function (errorField) {
                    errorField = (typeof errorField.$setTouched !== 'function') ? errorField.$error.required : errorField;
                    if (_.isArray(errorField)) {
                      angular.forEach(errorField, function (error) {
                        error.$setTouched();
                      });
                    } else {
                      errorField.$setTouched();
                    }
                  });
                });
                Form.email.$validate();
                $('select.form-control').trigger('blur');
              }

              if (!Form.$valid) return;
              let annonce = Form.type_annonce.$modelValue;
              annonce = parseInt( annonce );
              $rootScope.isSubmit = !$rootScope.isSubmit;
              var Fm = new FormData();
              Fm.append('action', 'add_annonce');
              Fm.append('annonce', annonce);
              Fm.append('title', Form.title.$modelValue);
              Fm.append('description', Form.description.$modelValue);
              Fm.append('region', Form.region.$modelValue);
              Fm.append('town', Form.town.$modelValue);
              Fm.append('address', Form.address.$modelValue);
              Fm.append('cellphone', Form.phone.$modelValue);
              Fm.append('price', Form.price.$modelValue);
              Fm.append('email', Form.email.$modelValue);
              Fm.append('activity_area', parseInt(Form.activity_area.$modelValue));

              if (annonce === 2) { // Autres type d'annonce
                Fm.append('categorie', parseInt(Form.categorie.$modelValue));
                Fm.append('type', Form.type.$modelValue);
              }
              Factory
                .$send(Fm)
                .then(result => {
                  var data = result.data;
                  if (data.success) {
                    let obj = {title: Fm.get('title'), type: annonce, email: Fm.get('email')};
                    $cookies.putObject('annonce', obj);
                    $state.go('annonce.success');
                  } else {
                    $rootScope.isSubmit = !1;
                  }
                }, error => {
                  swal("Enregistrement", "Une erreur s'est produite pendant l'ajout de votre annonce", 'error');
                  $rootScope.isSubmit = !1;
                }); //.end then
            };
            $rootScope.searchCityFn = (city) => {
              if (!_.isUndefined($rootScope.annonce.region)) {
                let region = parseInt($rootScope.annonce.region);
                rg = _.findWhere($scope.regions, {term_id: region});
                if (rg) {
                  let cityname = city.name.toLowerCase();
                  let regionname = rg.name.toLowerCase();
                  regionname = regionname === "amoron'i mania" ? "mania" : regionname;
                  if (cityname.indexOf(regionname) > -1) {
                    return true;
                  }
                }
              }
              return false;
            };

            var $ = jQuery.noConflict();
            /** Load jQuery elements **/
            var jqSelects = $("select.form-control");
            $.each(jqSelects, function (index, element) {
              var selectElement = $(element);
              var placeholder = (selectElement.attr('title') === undefined) ? 'Please select' : selectElement.attr('title');
              $(element)
                .select2({
                  placeholder: placeholder,
                  allowClear: true,
                  width: '100%'
                })
                .on('select2:closing', function (e) {
                  var el = e.currentTarget;
                  $(el).blur();
                });
            });

            $(".form-control.country, .form-control.categorie").select2({
              placeholder: "Selectioner un choix",
              allowClear: true,
              matcher: function (params, data) {
                var inTerm = [];
                // If there are no search terms, return all of the data
                if ($.trim(params.term) === '') {
                  return data;
                }

                // Do not display the item if there is no 'text' property
                if (typeof data.text === 'undefined') {
                  return null;
                }

                // `params.term` should be the term that is used for searching
                // `data.text` is the text that is displayed for the data object

                var dataContains = data.text.toLowerCase();
                var paramTerms = $.trim(params.term).split(' ');
                $.each(paramTerms, (index, value) => {
                  if (dataContains.indexOf($.trim(value).toLowerCase()) > -1) {
                    inTerm.push(true);
                  } else {
                    inTerm.push(false);
                  }
                });
                var isEveryTrue = _.every(inTerm, (boolean) => {
                  return boolean === true;
                });
                if (isEveryTrue) {
                  var modifiedData = $.extend({}, data, true);
                  // modifiedData.text += ' (matched)';
                  return modifiedData;
                } else {
                  return null;
                }

                // Return `null` if the term should not be displayed
                return null;
              }
            });

            $('[data-toggle="tooltip"]').tooltip();
            $('#text-loading').hide();
          }]
      },
      {
        name: 'annonce.success',
        url: '/success',
        templateUrl: itOptions.helper.partials + '/annonce/success.html?ver=' + itOptions.version,
        resolve: {
          annonce: ['$cookies', '$q', function ($cookies, $q) {
            let redir = {redirect: 'annonce.form'};
            return !_.isUndefined($cookies.getObject('annonce')) ?
              (_.isEmpty($cookies.getObject('annonce')) ? $q.reject(redir) : $q.resolve($cookies.getObject('annonce'))) :
              $q.reject(redir);
          }]
        },
        controller: ['$rootScope', 'annonce', function($rootScope, annonce) {
          this.$onInit = () => {

          }
        }]
      }
    ];
    // Loop over the state definitions and register them
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
    $urlServiceProvider.rules.otherwise({state: 'annonce.form'});

  })
  .service('Services', ['$http', '$q', 'Factory', function ($http, $q, companyFactory) {
    return {
      getBranchActivity: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_branch_activity', {cache: true})
          .then(function (resp) {
            return resp.data;
          });
      }
    }
  }])
  .factory('Factory', ['$http', '$q', function ($http, $q) {
    return {
      $send: function (formData) {
        return $http({
          url: itOptions.ajax_url,
          method: "POST",
          headers: {'Content-Type': undefined},
          data: formData
        });
      }
    };
  }])
  .controller('annonceController', ['$rootScope', function ($rootScope) {
    $rootScope.isSubmit = !1;
    $rootScope.annonce = {};

  }]).run(['$state', function ($state) {
    var loadingPath = itOptions.template + '/img/loading.gif';
    $state.defaultErrorHandler(function (error) {
      // This is a naive example of how to silence the default error handler.
      if (error.detail !== undefined) {
        $state.go(error.detail.redirect);
      }

    });
  }]);