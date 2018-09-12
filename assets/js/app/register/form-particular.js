angular.module('formParticular', ['ui.router'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    //$interpolateProvider.startSymbol('[[').endSymbol(']]');
    var states = [
      {
        name: 'form',
        url: '/form',
        component: 'formComponent',
        resolve: {
          region: function (services) {
            return services.getRegion();
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
  .service('services', ["$http", function ($http) {
    return {
      getRegion: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=region', {cache: true})
          .then(function (resp) {
            return resp.data;
          });
      },
      sendPostForm: function (formData) {
        return $http({
          url: itOptions.ajax_url,
          method: "POST",
          headers: {'Content-Type': undefined},
          data: formData
        });
      },
      mailExists: function (email) {
        return new Promise(function (resolve) {
          if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email)) {
            $http.get(itOptions.ajax_url + '?action=ajx_user_exist&log=' + email, {cache: true})
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
  .directive('ngMail', ['services', function (services) {
    return {
      require: 'ngModel',
      scope: true,
      link: function (scope, element, attrs, model) {
        element.bind('blur', function () {
          services
            .mailExists(element.val())
            .then(function (status) {
              scope.$apply(function () {
                model.$setValidity('mail', !status);
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
  .controller('particularCtrl', ["$scope", function ($scope) {
    $scope.error = false;
    $scope.particularForm = {};
  }])
  .component('formComponent', {
    bindings: {region: '<'},
    templateUrl: itOptions.partials_url + '/particular/form.html',
    controller: function ($scope) {
      $scope.formSubmit = function (isValid) {
        // Submit form here ...
      };
      //  JQLite
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

      jQuery('#birthday .input-group.date').datepicker({
        format: "dd-mm-yyyy",
        startView: 2,
        todayBtn: "linked",
        keyboardNavigation: false,
        forceParse: false,
        autoclose: true
      });
    }
  })