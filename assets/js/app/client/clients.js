angular.module('clientApp', ['ngMessages', 'froala', 'ngTagsInput', 'ngSanitize'])
  .value('froalaConfig', {
    toolbarInline: false,
    quickInsertTags: null,
    toolbarButtons: ['bold', 'strikeThrough', 'subscript', 'superscript', 'align', 'formatOL', 'formatUL', 'indent', 'outdent', 'undo', 'redo'],
  })
  .config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');
  })
  .factory('clientFactory', ['$http', '$q', function ($http, $q) {
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
      }
    };
  }])
  .service('clientService', ['$http', function ($http) {
    this.offers = _.clone(itOptions.offers);
    this.clientArea = () => {
      return $http.get(itOptions.Helper.ajax_url + '?action=client_area', {
        cache: false
      });
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
  .filter('Status', [function() {
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
      return _.findWhere(postStatus, {slug: jQuery.trim(inputValue)}).label;
    }
  }])
  .directive('generalInformationCandidate', [function() {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/general-information-candidate.html',
      scope: {
        Candidate: '=candidate',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      controller: ['$scope', '$q', 'clientFactory', function ($scope, $q, clientFactory) {
        $scope.candidateEditor = {};
        $scope.loadingEditor = false;
        $scope.status = false;

        $scope.openEditor = () => {
          $scope.loadingEditor = true;
          UIkit.modal('#modal-edit-candidate-overflow').show();
          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
            $scope.loadingEditor = false;
            $scope.Regions        = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys          = _.clone(data[2]);
            const incInput = ['address', 'birthdayDate'];
            incInput.forEach((InputValue) => {
              if ($scope.Candidate.hasOwnProperty(InputValue)) {
                $scope.candidateEditor[InputValue] = _.clone($scope.Candidate[InputValue]);
              }
            });
            $scope.candidateEditor.greeting = $scope.Candidate.greeting.value;
            $scope.candidateEditor.branch_activity = $scope.Candidate.branch_activity.term_id;
            $scope.candidateEditor.region = $scope.Candidate.region.term_id;
            $scope.candidateEditor.country = $scope.Candidate.country.term_id;
            UIkit.modal('#modal-edit-candidate-overflow').show();
          });
        };

        $scope.updateCandidateInformation = () => {
          $scope.status = "Enregistrement en cours ...";
          let candidatForm = new FormData();
          let formObject = Object.keys($scope.candidateEditor);
          candidatForm.append('action', 'update_profil');
          candidatForm.append('candidate_id', parseInt($scope.Entreprise.ID));
          formObject.forEach(function (property) {
            let propertyValue = Reflect.get($scope.userEditor, property);
            candidatForm.set(property, propertyValue);
          });
          clientFactory
            .sendPostForm(candidatForm)
            .then(resp => {
              let dat = resp.data;
              if (dat.success) {
                $scope.status = 'Votre information a bien été enregistrer avec succès';
                $scope.init();
              } else {
                $scope.status = 'Une erreur s\'est produit pendant l\'enregistrement, Veuillez réessayer ultérieurement';
              }
            });
        };

      }]

    }
  }])
  .directive('generalInformationCompany', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/general-information-company.html',
      scope: {
        Entreprise: '=company',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      link: function (scope, element, attrs) {

      },
      controller: ['$scope', '$q', 'clientFactory', function ($scope, $q, clientFactory) {
        $scope.status = false;
        $scope.userEditor = {};
        $scope.openEditor = () => {
          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
            $scope.Regions        = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys          = _.clone(data[2]);
            const incInput = ['address', 'greeting', 'name', 'stat', 'nif'];
            const incTerm = ['branch_activity', 'region', 'country'];
            incInput.forEach((InputValue) => {
              if ($scope.Entreprise.hasOwnProperty(InputValue)) {
                $scope.userEditor[InputValue] = _.clone($scope.Entreprise[InputValue]);
              }
            });

            incTerm.forEach(TermValue => {
              if ($scope.Entreprise.hasOwnProperty(TermValue)) {
                if (typeof $scope.Entreprise[TermValue].term_id !== 'undefined') {
                  $scope.userEditor[TermValue] = $scope.Entreprise[TermValue].term_id;
                } else {
                  $scope.userEditor[TermValue] = '';
                }
              }
            });

            if (!_.isEmpty($scope.userEditor)) {
              UIkit.modal('#modal-edit-user-overflow').show();
            }
          });
        };
        $scope.updateUser = () => {
          $scope.status = "Enregistrement en cours ...";
          let userForm = new FormData();
          let formObject = Object.keys($scope.userEditor);
          userForm.append('action', 'update_profil');
          userForm.append('company_id', parseInt($scope.Entreprise.ID));
          formObject.forEach(function (property) {
            let propertyValue = Reflect.get($scope.userEditor, property);
            userForm.set(property, propertyValue);
          });
          clientFactory
            .sendPostForm(userForm)
            .then(resp => {
              let dat = resp.data;
              if (dat.success) {
                $scope.status = 'Votre information a bien été enregistrer avec succès';
                $scope.init();
              } else {
                $scope.status = 'Une erreur s\'est produit pendant l\'enregistrement, Veuillez réessayer ultérieurement';
              }
            });
        };
        // Event on modal dialog close or hide
        UIkit.util.on('#modal-edit-user-overflow', 'hide', function (e) {
          e.preventDefault();
          e.target.blur();
          $scope.status = false;
        });

      }]
    }
  }])
  .directive('offerLists', [function () {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/offer-lists.html',
      scope: {
        Entreprise: '=company',
        Offers: '=offers',
        regions: '&',
        allCity: '&',
        abranchs: '&',
        init: '&init'
      },
      link: function (scope, element, attrs) {
        scope.Helper = itOptions.Helper;
        jQuery('.input-group.date').datepicker({
          format: "mm/dd/yyyy",
          language: "fr",
          startView: 2,
          todayBtn: false,
          keyboardNavigation: true,
          forceParse: false,
          autoclose: true
        });
      },
      controller: ['$scope', '$http', '$q', 'clientFactory', function ($scope, $http, $q, clientFactory) {
        $scope.offerEditor = {};
        $scope.loadingCandidats = false;
        $scope.postuledCandidats = [];
        $scope.openEditor = (offerId) => {
          let offer = _.findWhere($scope.Offers, {
            ID: parseInt(offerId)
          });

          $q.all([$scope.regions(), $scope.abranchs(), $scope.allCity()]).then(data => {
            $scope.Regions        = _.clone(data[0]);
            $scope.branchActivity = _.clone(data[1]);
            $scope.Citys          = _.clone(data[2]);
            $scope.offerEditor = _.mapObject(offer, (val, key) => {
              if (typeof val.term_id !== 'undefined') return val.term_id;
              if (typeof val.label !== 'undefined') return val.value;
              if (typeof val.post_title !== 'undefined') return val.ID;
              if (key === 'proposedSalary') return parseInt(val);
              return val;
            });
            if (!_.isEmpty(offer) || !_.isNull($scope.offerEditor)) {
              UIkit.modal('#modal-edit-offer-overflow').show();
            }
          });
        };
        $scope.editOffer = (offerId) => {
          let offerForm = new FormData();
          let formObject = Object.keys($scope.offerEditor);
          offerForm.append('action', 'update_offer');
          offerForm.append('post_id', parseInt(offerId));
          formObject.forEach(function (property) {
            let propertyValue = Reflect.get($scope.offerEditor, property);
            offerForm.set(property, propertyValue);
          });
          clientFactory
            .sendPostForm(offerForm)
            .then(resp => {
              UIkit.modal('#modal-edit-offer-overflow').hide();
              $scope.init();
            });
        };
        $scope.viewApply = (offer_id) => {
          $scope.loadingCandidats = true;
          let offer = _.find($scope.Offers, (item) => item.ID === offer_id);
          if (!offer.my_offer || offer.count_candidat_apply <= 0) return;

          UIkit.modal('#modal-view-candidat').show();
          $http.get(itOptions.Helper.ajax_url + '?action=get_postuled_candidate&oId=' + offer.ID, {cache: false})
            .then(resp => {
              $scope.postuledCandidats = resp.data;
              $scope.loadingCandidats = false;
            });
        };
      }]
    };
  }])
  .directive('alerts', [function() {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/alert.html',
      scope: {
        onSave: '&',
        alerts: '=',
        alertLoading: '='
      }
    }
  }])
  .directive('biography', [function() {
    return {
      restrict: 'E',
      templateUrl: itOptions.Helper.tpls_partials + '/biography.html',
      scope: {
      }
    }
  }])
  .controller('clientCtrl', ['$scope', '$http', '$q', 'clientFactory', 'clientService',
    function ($scope, $http, $q, clientFactory, clientService) {
      $scope.alertLoading = false;
      $scope.alerts = [];
      $scope.loading = true;
      $scope.Company = {};
      $scope.offerLists = [];
      $scope.countOffer = 0;

      // Récuperer les données du client
      $scope.Initialize = () => {
        console.log('Initialize');
        $scope.loading = true;
        clientService
          .clientArea()
          .then(resp => {
            var data = resp.data;
            if (itOptions.client_type === 'company') {
              $scope.Company = _.clone(data.Company);
              $scope.offerLists = _.clone(data.Offers);
              $scope.alerts = _.reject(data.Alerts, alert => _.isEmpty(alert) );
              $scope.countOffer = $scope.offerLists.length;
            } else {
              $scope.Candidate = _.clone(data.Candidate);
              $scope.alerts = _.clone(data.Alerts);
            }

            $scope.loading = false;
          });
      };
      $scope.Initialize();
      $scope.asyncTerms = (Taxonomy) => {
        if (Taxonomy !== 'city') {
          return $http.get(itOptions.Helper.ajax_url + '?action=ajx_get_taxonomy&tax=' + Taxonomy, {
            cache: true
          }).then(resp => resp.data);
        } else {
          return clientFactory.getCity();
        }
      };
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
            if (data.success) { console.warn("Une erreur inconue s'est produit")}
          });
      };

      $scope.$watch('alerts', value => { console.log(value);}, true);
      // Trash offert
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


      }
    }
  ])