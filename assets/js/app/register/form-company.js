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
  .service('companyService', ['$http', '$q', function ($http, $q) {
    return {
      getBranchActivity: function () {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_branch_activity', {cache: true})
          .then(function (resp) {
            return resp.data;
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
  .controller('formCompanyCtrl', ['$scope', function ($scope) {
    // Code controller here...
    $scope.loadingPath = itOptions.template_url + '/img/loading.gif';
  }])
  .component('formComponent', {
    bindings: {abranchs: '<'},
    templateUrl: itOptions.partials_url + '/company/form.html',
    controller: function (companyData, companyFactory, $log, $scope, $location) {
      $scope.countPhone = 1;
      $scope.isSubmit = !1;
      $scope.company = {};
      $scope.company.greeting = 'mr';
      $scope.company.phones = [
        {
          id: 0,
          value: ''
        }
      ];
      $scope.addPhone = function () {
        $scope.company.phones.push({id: $scope.countPhone, value: ''});
        $scope.countPhone += 1;
      };
      $scope.removePhone = function (id) {
        $scope.company.phones = _.filter($scope.company.phones, function (phone) {
          return phone.id != id;
        });
        $log.info($scope.company.phones, id);
      };

      $scope.submitForm = function (isValid) {
        if (!isValid) return;
        $scope.isSubmit = !$scope.isSubmit;
        companyData.formCompanyValue = _.clone($scope.company);
        var companyForm = new FormData();
        companyForm.append('action', 'ajx_insert_company');
        companyForm.append('greeting', $scope.company.greeting);
        companyForm.append('title', $scope.company.title);
        companyForm.append('address', $scope.company.address);
        companyForm.append('phones', JSON.stringify($scope.company.phones));
        companyForm.append('nif', $scope.company.nif);
        companyForm.append('stat', $scope.company.stat);
        companyForm.append('name', $scope.company.name);
        companyForm.append('email', $scope.company.email);
        companyForm.append('abranchID', parseInt($scope.company.branch_activity));
        companyForm.append('newsletter', parseInt($scope.company.newsletter));
        companyForm.append('notification', parseInt($scope.company.notification));

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
    }
  })
  .component('validateComponent', {
    templateUrl: itOptions.partials_url + '/company/validate.html',
    controller: function (companyData, $location) {
      this.message = _.clone(companyData.message);
      if (_.isNull(this.message.title))
        $location.path('/form');

    }
  });