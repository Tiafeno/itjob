var companyApp = angular.module('formCompanyApp', ['ui.router', 'ngMessages', 'ngAria', 'ngSanitize'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');

    const states = [
      {
        name: 'company',
        url: '/company',
        templateUrl: itOptions.partials_url + '/company/company.html?ver=' + itOptions.version,
        resolve: {
          abranchs: function (companyService) {
            return companyService.getBranchActivity();
          },
          regions: ['$http', function ($http) {
            return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=region', {cache: true})
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
        controller: 'formController'
      },
      {
        name: 'company.form',
        url: '/form',
        templateUrl: itOptions.partials_url + '/company/form.html?ver=' + itOptions.version,
        controller: ['$rootScope', '$scope', 'companyFactory', 'companyService', function ($rootScope, $scope, companyFactory, companyService) {
          $scope.login_url = itOptions.Helper.login;
          $scope.addPhone = function () {
            $rootScope.company.cellphone.push({id: Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2, 10), value: ''});
          };
          $scope.removePhone = function (id) {
            $rootScope.company.cellphone = _.filter($rootScope.company.cellphone, function (cellphone) {
              return cellphone.id !== id;
            });
          };
          $scope.submitForm = function (isValid) {
            // FEATURED: Vérifier si l'adresse e-mail existe déja
            if ($scope.formCompany.$invalid) {
              angular.forEach($scope.formCompany.$error, function (field) {
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
              $scope.formCompany.email.$validate();
            }

            if (!isValid) return;
            companyService
              .mailCheck($scope.company.email)
              .then(function (status) {
                $rootScope.$apply(function () {
                  $scope.formCompany.email.$setValidity('mail', status);
                  if (status) {
                    $rootScope.isSubmit = !$rootScope.isSubmit;
                    var companyForm = new FormData();
                    companyForm.append('action', 'ajx_insert_company');
                    companyForm.append('greeting', $rootScope.company.greeting);
                    companyForm.append('title', $rootScope.company.title);
                    companyForm.append('region', $rootScope.company.region);
                    companyForm.append('country', $rootScope.company.country);
                    companyForm.append('address', $rootScope.company.address);
                    companyForm.append('cellphone', JSON.stringify($rootScope.company.cellphone));
                    companyForm.append('phone', $rootScope.company.phone);
                    companyForm.append('nif', $rootScope.company.nif);
                    companyForm.append('stat', $rootScope.company.stat);
                    companyForm.append('name', $rootScope.company.name);
                    companyForm.append('email', $rootScope.company.email);
                    companyForm.append('abranchID', parseInt($rootScope.company.branch_activity));
                    companyForm.append('newsletter', parseInt($rootScope.company.newsletter));
                    companyForm.append('notification', parseInt($rootScope.company.notification));
                    companyForm.append('pwd', $rootScope.company.pwdConf);

                    companyFactory
                      .sendPostForm(companyForm)
                      .then(result => {
                        var data = result.data;
                        if (data.success) {
                          swal({
                            title: 'Reussi',
                            text: "Votre compte à étés bien enregistrer. Vous recevrez un message pour confirmer votre inscription. ",
                            type: "info",
                          }, () => {
                            window.location.href = itOptions.Helper.redir;
                          });
                        } else {
                          $rootScope.isSubmit = !1;
                        }
                      }); //.end then
                  } else {
                    return false;
                  }

                });
              });
          };

          $rootScope.searchCityFn = (city) => {
            if (!_.isUndefined($rootScope.company.region)) {
              let region = parseInt($rootScope.company.region);
              rg = _.findWhere($rootScope.regions, {term_id: region});
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

          /** Load jQuery elements **/
          var jqSelects = jQuery("select.form-control");
          jQuery.each(jqSelects, function (index, element) {
            var selectElement = jQuery(element);
            var placeholder = (selectElement.attr('title') === undefined) ? 'Please select' : selectElement.attr('title');
            jQuery(element).select2({
              placeholder: placeholder,
              allowClear: true,
              width: '100%'
            })
          });

          jQuery(".form-control.country").select2({
            placeholder: "Selectioner une ville",
            allowClear: true,
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
                // modifiedData.text += ' (matched)';
                return modifiedData;
              } else {
                return null;
              }

              // Return `null` if the term should not be displayed
              return null;
            }
          });

          jQuery('[data-toggle="tooltip"]').tooltip();
          jQuery('#text-loading').hide();
        }]
      }
    ];
    // Loop over the state definitions and register them
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
    $urlServiceProvider.rules.otherwise({state: 'company.form'});

  })
  .service('companyService', ['$http', '$q', 'companyFactory', function ($http, $q, companyFactory) {
    return {
      getBranchActivity: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_branch_activity', {cache: true})
          .then(function (resp) {
            return resp.data;
          });
      },
      mailCheck: function (email) {
        return new Promise(function (resolve, reject) {
          if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email)) {
            jQuery('#text-loading').show();
            companyFactory
              .checkLogin(email)
              .then(function (resp) {
                var data = _.clone(resp.data);
                if (data.success) {
                  reject(false);
                  jQuery('#text-loading').hide();
                } else {
                  resolve(true);
                  jQuery('#text-loading').hide();
                }
              });
          } else {
            reject(false);
            jQuery('#text-loading').hide();
          }
        });
      }
    }
  }])
  .factory('companyFactory', ['$http', '$q', function ($http, $q) {
    return {
      checkLogin: function (log) {
        return $http.get(itOptions.ajax_url + '?action=ajx_user_exist&mail=' + log, {cache: true});
      },
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.ajax_url,
          method: "POST",
          headers: {'Content-Type': undefined},
          data: formData
        });
      }
    };
  }])
  .directive('compareTo', function () {
    // Directive: Comparer les mots de passes
    return {
      require: "ngModel",
      scope: {
        repeaterPwd: "=compareTo"
      },
      link: function (scope, element, attrs, value) {
        value.$validators.compareTo = function (val) {
          return val == scope.repeaterPwd;
        };
        scope.$watch('repeaterPwd', function () {
          value.$validate();
        })
      }
    }
  })
  .directive('ngMail', ['companyService', function (companyService) {
    return {
      require: 'ngModel',
      scope: true,
      link: function (scope, element, attrs, model) {
        element.bind('keyup', function () {
          companyService
            .mailCheck(element.val())
            .then(
              function (status) {
                scope.$apply(function () {
                  model.$setValidity('mail', true);
                });
              },
              function (error) {
                scope.$apply(function () {
                  model.$setValidity('mail', false);
                });
              }
            )
        });

      }
    }
  }])
  .controller('formController', ['$scope', '$rootScope', 'abranchs', 'regions', 'allCity', function ($scope, $rootScope, abranchs, regions, allCity) {
    $rootScope.isSubmit = !1;
    $rootScope.abranchs = _.clone(abranchs);
    $rootScope.regions = _.clone(regions);
    $rootScope.allCity = _.clone(allCity);
    $rootScope.company = {};
    $rootScope.company.greeting = 'mr';
    $rootScope.company.cellphone = [{id: 0, value: ''}];

  }]).run([function () {
    var loadingPath = itOptions.template_url + '/img/loading.gif';
  }]);