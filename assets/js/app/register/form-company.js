var companyApp = angular.module('formCompanyApp', ['ui.router', 'ngMessages', 'ngAria', 'ngSanitize'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');

    var states = [
      {
        name: 'form',
        url: '/form',
        component: 'formComponent',
        resolve: {
          abranchs: function (companyService) {
            return companyService.getBranchActivity();
          }
        }
      },
      {
        name: 'validate',
        url: '/validate',
        component: 'validateComponent',
        resolve: {
          message: function (companyData) {
            return companyData.message;
          }
        }
      }
    ];
    // Loop over the state definitions and register them
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
    $urlServiceProvider.rules.otherwise({state: 'form'});

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
        return new Promise(function (resolve) {
          if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email)) {
            companyFactory
              .checkLogin(email)
              .then(function (resp) {
                resolve(resp.data ? true : false);
              });
          } else {
            resolve(false);
          }
        });
      }
    }
  }])
  .service('companyData', [function () {
    var self = this;
    self.formCompanyValue = {};
    self.message = {title: null, msg: null};
    self.setMessage = function (_title, _msg) {
      self.message = {title: _title, msg: _msg};
    };
  }])
  .factory('companyFactory', ['$http', '$q', function ($http, $q) {
    return {
      checkLogin: function (log) {
        return $http.get(itOptions.ajax_url + '?action=ajx_user_exist&log=' + log, {cache: true});
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
        element.bind('blur', function () {
          companyService
            .mailCheck(element.val())
            .then(function (status) {
              scope.$apply(function () {
                model.$setValidity('mail', !status);
              });
            })
        });
      }
    }
  }])
  .controller('formCompanyCtrl', ['$scope', function ($scope) {
    // Code controller here...
    $scope.loadingPath = itOptions.template_url + '/img/loading.gif';
  }])
  .component('formComponent', {
    bindings: {abranchs: '<'},
    templateUrl: itOptions.partials_url + '/company/form.html',
    controller: function (companyData, companyFactory, companyService, $log, $scope, $location) {
      $scope.countPhone = 1;
      $scope.isSubmit = !1;
      $scope.company = {};
      $scope.company.greeting = 'mr';
      $scope.company.cellphone = [
        {
          id: 0,
          value: ''
        }
      ];
      $scope.addPhone = function () {
        $scope.company.cellphone.push({id: $scope.countPhone, value: ''});
        $scope.countPhone += 1;
      };
      $scope.removePhone = function (id) {
        $scope.company.cellphone = _.filter($scope.company.cellphone, function (cellphone) {
          return cellphone.id != id;
        });
      };

      $scope.submitForm = function (isValid) {
        // TODO: Vérifier si l'adresse e-mail existe déja
        if ($scope.formCompany.$invalid) {
          angular.forEach($scope.formCompany.$error, function (field) {
            angular.forEach(field, function (errorField) {
              errorField.$setTouched();
            });
          });
          $scope.formCompany.email.$validate();
        }

        if (!isValid) return;
        companyService
          .mailCheck($scope.company.email)
          .then(function (status) {
            $scope.$apply(function () {
              $scope.formCompany.email.$setValidity('mail', !status);
              if (!status) {
                $scope.isSubmit = !$scope.isSubmit;
                companyData.formCompanyValue = _.clone($scope.company);
                var companyForm = new FormData();
                companyForm.append('action', 'ajx_insert_company');
                companyForm.append('greeting', $scope.company.greeting);
                companyForm.append('title', $scope.company.title);
                companyForm.append('address', $scope.company.address);
                companyForm.append('cellphone', JSON.stringify($scope.company.cellphone));
                companyForm.append('phone', $scope.company.phone);
                companyForm.append('nif', $scope.company.nif);
                companyForm.append('stat', $scope.company.stat);
                companyForm.append('name', $scope.company.name);
                companyForm.append('email', $scope.company.email);
                companyForm.append('abranchID', parseInt($scope.company.branch_activity));
                companyForm.append('newsletter', parseInt($scope.company.newsletter));
                companyForm.append('notification', parseInt($scope.company.notification));
                companyForm.append('pwd', $scope.company.pwdConf);

                companyFactory
                  .sendPostForm(companyForm)
                  .then(function (result) {
                    var data = result.data;
                    if (data.success) {
                      companyData.setMessage('Info',
                        'Votre compte à étés bien enregistrer. <br>Pour confirmer votre inscription ' + companyData.formCompanyValue.email);
                      $location.path('/validate');
                    } else {
                      $scope.isSubmit = !1;
                    }
                  }); //.end then
              } else {
                return false;
              }

            });

          });

      };

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
      jQuery('[data-toggle="tooltip"]').tooltip();
    }
  })
  .component('validateComponent', {
    templateUrl: itOptions.partials_url + '/company/validate.html',
    controller: function (companyData, $location) {
      this.message = _.clone(companyData.message);
      if (_.isNull(this.message.title) || _.isNull(this.message.value))
        $location.path('/form');

    }
  });