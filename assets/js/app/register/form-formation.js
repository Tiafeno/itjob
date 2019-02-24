angular.module('FormationApp', ['ui.router', 'ngMessages'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
    //$interpolateProvider.startSymbol('[[').endSymbol(']]');
    var states = [{
      name: 'form',
      url: '/form',
      component: 'formComponent',
      resolve: {
        region: function (services) {
          return services.getTaxonomy('region');
        },
        area: function (services) {
          return services.getTaxonomy('branch_activity');
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
      getTaxonomy: function (Tax) {
        return $http.get(itOptions.ajax_url + '?action=ajx_get_taxonomy&tax=' + Tax, {
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
      }
    };
  }])
  .component('formComponent', {
    bindings: {
      region: '<',
      area: '<'
    },
    templateUrl: itOptions.helper.partials + '/formation/form.html?ver=' + itOptions.version,
    controllerAs: 'vm',
    controller: function ($scope, services) {
      $scope.regions = [];
      $scope.sectorArea = [];
      this.$onInit = () => {
        $scope.regions = _.clone(this.region);
        $scope.sectorArea = _.clone(this.area);
      };
      $scope.error = false;
      $scope.buttonDisable = false;
      $scope.uri = {};
      $scope.Form = {};
      $scope.Form.price = 0;
      $scope.Form.distance_learning = '0';
      // Envoyer le formulaire
      $scope.submitForm = function (Form) {
        if (Form.$invalid) {
          angular.forEach(Form.$error.required, function (required) {
            required.$setTouched();
          });
          $scope.error = true;
        }
        if (!Form.$valid) return false;
        $scope.buttonDisable = true;
        var fData = new FormData();
        fData.append('action', 'new_formation');
        var particularFormObject = Object.keys($scope.Form);
        particularFormObject.forEach(function (property) {
          if (property === 'date_limit') {
            var dateLimit = Reflect.get($scope.Form, property);
            fData.set(property, moment(dateLimit, 'DD-MM-YYYY').format('YYYY-MM-DD'));
          } else {
            fData.set(property, Reflect.get($scope.Form, property));
          }
        });

        $scope.error = false;
        services
          .sendPostForm(fData)
          .then(
            function (resp) {
              var status = resp.data;
              swal({
                title: 'Notification',
                text: "Votre formation modulaire a bien été envoyée. " +
                "Votre annonce sera validée par nos soins avant la mise en ligne sous 24 heures jours ouvrés et en ligne" +
                " pour une durée de un mois .",
                type: status.success ? 'info' : 'error',
              }, function () {
                $scope.buttonDisable = false;
                if (status.success) {
                  var redirection = itOptions.helper.redir;
                  window.location.href = redirection;
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

      jQuery('.form-control.date').datepicker({
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