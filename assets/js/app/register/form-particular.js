angular.module('formParticular', ['ui.router', 'ngMessages'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    //$interpolateProvider.startSymbol('[[').endSymbol(']]');
    var states = [{
      name: 'form',
      url: '/form',
      component: 'formComponent',
      resolve: {
        region: function (services) {
          return services.getRegion();
        },
        allCity: function (services) {
          return services.getCity();
        }
      }
    }];
    // Loop over the state definitions and register them
    states.forEach(function (state) {
      $stateProvider.state(state);
    });
    $urlServiceProvider.rules.otherwise({
      state: 'form'
    });
  })
  .service('services', ["$http", function ($http) {
    return {
      getRegion: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=region', {
            cache: true
          })
          .then(function (resp) {
            return resp.data;
          });
      },
      getCity: function () {
        return $http.get(itOptions.ajax_url + '?action=get_city', {
            cache: true
          })
          .then(function (resp) {
            return resp.data;
          });
      },
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.ajax_url,
          method: "POST",
          headers: {
            'Content-Type': undefined
          },
          data: formData
        });
      },
      mailExists: function (email) {
        return new Promise(function (resolve, reject) {
          if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email)) {
            $http.get(itOptions.ajax_url + '?action=ajx_user_exist&mail=' + email, {
                cache: true
              })
              .then(function (resp) {
                var data = _.clone(resp.data);
                if (data.success) {
                  reject(data.data);
                } else {
                  resolve(data.data);
                }
              });
          } else {
            reject(false);
          }
        });
      }
    };
  }])
  .directive('ngMail', ['services', function (services) {
    return {
      require: 'ngModel',
      scope: true,
      link: function (scope, element, attrs, model) {
        element.bind('blur', function () {
          services
            .mailExists(element.val())
            .then(
              function (status) {
                scope.$apply(function () {
                  model.$setValidity('mail', true);
                });
              },
              function (error) {
                console.log(error);
                scope.$apply(function () {
                  model.$setValidity('mail', false);
                });
              })
        });
      }
    }
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
  .component('formComponent', {
    bindings: {
      region: '<',
      allCity: '<'
    },
    templateUrl: itOptions.partials_url + '/particular/form.html?ver=' + itOptions.version,
    controllerAs: 'formulaire',
    controller: function ($scope, services) {
      const self = this;
      self.regions = [];
      this.$onInit = () => {
        self.regions = _.clone($scope.formulaire.region);
      };
      $scope.error = false;
      $scope.buttonDisable = false;
      $scope.uri = {};
      $scope.uri.singin = itOptions.urlHelper.singin;
      $scope.uri.redir = itOptions.urlHelper.redir;
      $scope.particularForm = {};
      $scope.particularForm.greeting = '';
      $scope.particularForm.phone = [];

      $scope.formSubmit = function (isValid) {
        if ($scope.pcForm.$invalid) {
          angular.forEach($scope.pcForm.$error, function (field) {
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
          $scope.pcForm.email.$validate();
          $scope.error = true;
        }

        if (!isValid) return;
        $scope.buttonDisable = true;
        let Data = new FormData();
        Data.append('action', 'insert_user_particular');
        const dataObject = Object.keys($scope.particularForm);
        dataObject.forEach(function (property) {
          if (property === "phone") {
            let phoneNumbers = Reflect.get($scope.particularForm, property);
            Data.set(property, JSON.stringify(phoneNumbers));
            return true;
          }
          Data.set(property, Reflect.get($scope.particularForm, property));
        });
        $scope.error = false;
        services
          .sendPostForm(Data)
          .then(
            function (resp) {
              const status = resp.data;
              var _type = status.success ? 'info' : 'error';
              swal({
                title: 'Notification',
                text: status.msg,
                type: _type,
              }, function () {
                $scope.buttonDisable = false;
                if (status.success) {
                  var redirection = itOptions.urlHelper.redir;
                  window.location.href = _.isNull(redirection) ? itOptions.urlHelper.singin : redirection;
                }
                if (!status.success) $scope.error = true;
              });
            },
            function (error) {
              $scope.buttonDisable = false;
              $scope.error = true;
            })
      };

      //  JQLite
      var jqSelects = jQuery("select.form-control.find");
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
          // `data.text` is the text that is displayed for the data object
          var dataContains = data.text.toLowerCase();
          var searchTyping = _.clone(params.term);
          if (!_.isUndefined($scope.particularForm.region) && !_.isEmpty($scope.particularForm.region)) {
            var region = _.findWhere(self.regions, {
              term_id: parseInt($scope.particularForm.region)
            });
            var findRegion = region.name.toLowerCase();
            findRegion = findRegion === "amoron'i mania" ? 'mania' : findRegion;
            searchTyping += ` ${findRegion} `;
          }
          // If there are no search terms, return all of the data
          if (jQuery.trim(searchTyping) === '') {
            return data;
          }
          // Do not display the item if there is no 'text' property
          if (typeof data.text === 'undefined') {
            return null;
          }
          // `params.term` should be the term that is used for searching
          var paramTerms = jQuery.trim(searchTyping).split(' ');
          paramTerms = _.reject(paramTerms, term => term === 'undefined');
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
            return modifiedData;
          } else {
            return null;
          }
        }
      });

      jQuery('#birthday .input-group.date').datepicker({
        format: "dd-mm-yyyy",
        language: "fr",
        startView: 2,
        todayBtn: false,
        keyboardNavigation: true,
        forceParse: false,
        autoclose: true
      });
    }
  })
  .controller('particularCtrl', ["$scope", function ($scope) {
    $scope.error = false;
  }]);