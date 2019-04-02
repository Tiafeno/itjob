angular.module('FormationApp', ['ui.router', 'ngMessages'])
  .config(function ($interpolateProvider, $stateProvider, $urlServiceProvider) {
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
        },
        options: function (services) {
          return services.getOptions();
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
      },
      getOptions: function () {
        return $http.get(itOptions.helper.rest_options, {cache: true}).then(resp => resp.data);
      }
    };
  }])
  .component('formComponent', {
    bindings: {
      region: '<',
      area: '<',
      options: '<'
    },
    templateUrl: itOptions.helper.partials + '/formation/form.html?ver=' + itOptions.version,
    controllerAs: 'vm',
    controller: ['$scope', '$http', 'services', function ($scope, $http, services) {
      $scope.WPEndpoint = null;
      $scope.Form = {};
      $scope.regions = [];
      $scope.options = {};
      $scope.sectorArea = [];
      this.$onInit = () => {
        let origin = document.location.origin;
        $scope.WPEndpoint = new WPAPI({endpoint: `${origin}/wp-json`});
        let namespace = 'wp/v2'; // use the WP API namespace
        let wc_namespace = 'wc/v3'; // use the WOOCOMMERCE API namespace
        let route_formation = '/formation/(?P<id>\\d+)';
        let route_product = '/products/(?P<id>\\d+)';
        $scope.WPEndpoint.setHeaders({'X-WP-Nonce': `${WP.nonce}`});
        $scope.WPEndpoint.formation = $scope.WPEndpoint.registerRoute(namespace, route_formation);
        $scope.WPEndpoint.product = $scope.WPEndpoint.registerRoute(wc_namespace, route_product);

        // Variable dependant du formulaire
        $scope.regions = _.clone(this.region);
        $scope.sectorArea = _.clone(this.area);
        $scope.options = _.clone(this.options);

        $scope.Form.establish_name = itOptions.company.title;
      };
      $scope.error = false;
      $scope.buttonDisable = false;
      $scope.uri = {};
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
              var response = resp.data;
              swal({
                title: 'Notification',
                text: "Votre formation modulaire a bien été envoyée. " +
                "Votre annonce sera validée par nos soins avant la mise en ligne sous 24 heures jours ouvrés et en ligne" +
                " pour une durée de un mois.",
                type: response.success ? 'info' : 'error',
              }, function () {
                if (response.success) {
                  window.location.href = response.data;
                }
                if (!response.success) $scope.error = true;
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
    }]
  });