(function () {
  angular.module('interestsApp', [])
    .directive('askCv', [function () {
      return {
        restrict: 'E',
        templateUrl: itOptions.Helper.partialsUrl + '/ask-cv.html',
        scope: {
          cvId: '@'
        },
        link: function (scope, element, attrs) {},
        controller: ['$scope', '$http', function ($scope, $http) {
          const elButton = jQuery('#ask-cv');
          const textButton = elButton.html();
          $scope.offer_id = 0;
          $scope.offers = [];
          $scope.singup = '';
          $scope.login = '';
          $scope.loadingWorklow = false;
          $scope.errorMessage = {
            title: 'Impossible d\'ajouter le cv',
            body: '',
            error: true
          };
          $scope.successMessage = {
            body: '',
            limit_reach: false
          };
          $scope.i_am_interested_this_candidate = () => {
            elButton.text('Chargement en cours ...');
            let offerForm = new FormData();
            offerForm.append('action', 'get_current_user_offers');
            $http({
                url: itOptions.Helper.ajax_url,
                method: "POST",
                headers: {
                  'Content-Type': undefined
                },
                data: offerForm
              })
              .then(response => {
                let query = response.data;
                if (query.success) {
                  jQuery('#modal-offer-interest').modal('toggle');
                  $scope.offers = _.clone(query.data);
                } else {
                  // User is not logged in
                  let resp = query.data;
                  switch (resp.status) {
                    case 'access':
                      $scope.message.error = true;
                      break;
                    case 'logged':
                      $scope.message.error = false;
                      $scope.login = resp.helper.login;
                      $scope.singup = resp.helper.singup;
                      break;
                  }
                  $scope.message.body = resp.msg;
                  elButton.html(textButton);
                  jQuery('#modal-error').modal('toggle')
                }
              })
          };

          $scope.nextInterestWorkflow = () => {
            if ($scope.offer_id) {
              let offer_id = parseInt($scope.offer_id);
              sendInterest(offer_id);
            }
          };

          $scope.gtPaiement = () => {

          }

          // Envoyer la requete
          let sendInterest = (offer_id) => {
            if (!_.isNumber(offer_id)) return;
            $scope.loadingWorklow = true;
            let askForm = new FormData();
            askForm.append('action', 'inspect_cv');
            askForm.append('cv_id', $scope.cvId);
            askForm.append('offer_id', offer_id);
            let inspectQuery = $http({
              url: itOptions.Helper.ajax_url,
              method: "POST",
              headers: {
                'Content-Type': undefined
              },
              data: askForm
            });

            inspectQuery
              .then(resp => {
                let query = resp.data;
                if (!query.success) {
                  // Error
                  let response = query.data;
                  jQuery('#modal-offer-interest').modal('hide');
                  openErrorDialog(response);
                }
              });

            inspectQuery
              .then(resp => {
                elButton.html(textButton);
                let query = resp.data;
                let reach_limit_msg = ` Vous venez de sélectionner 5 candidats et vous vous apprêter à en sélectionner un sixième savez vous
                 qu’à partir de là les CV sont payants au prix de 25.000 HT / CV ?. Souhaitez vous continuer ?`;
                // Success
                if (query.success) {
                  let interests = query.data.interests;
                  interests = _.reject(interests, (interest) => interest.type === 'apply');
                  if (interests.length >= 6) {
                    jQuery('#modal-offer-interest').modal('hide');
                    $scope.successMessage.limit_reach = true;
                    $scope.successMessage.body = reach_limit_msg;
                    jQuery("#modal-continue").modal('show');
                  } else {
                    $scope.addedInterest();
                  }
                }
              });
          };

          $scope.addedInterest = () => {
            if (_.isEmpty($scope.offer_id)) return;
            let askForm = new FormData();
            askForm.append('action', 'get_ask_cv');
            askForm.append('cv_id', $scope.cvId);
            askForm.append('offer_id', $scope.offer_id);
            let addedInterestQuery = $http({
              url: itOptions.Helper.ajax_url,
              method: "POST",
              headers: {
                'Content-Type': undefined
              },
              data: askForm
            });
            addedInterestQuery
              .then(resp => {
                jQuery('#modal-offer-interest').modal('hide');
                $scope.loadingWorklow = false;
                let query = resp.data;
                if (query.success) {
                  let msg = `Souhaitez vous sélectionner un autre candidat ?`;
                  $scope.successMessage.limit_reach = false;
                  $scope.successMessage.body = msg;
                  jQuery("#modal-continue").modal('show');
                } else {
                  let data = query.data;
                  openErrorDialog(data);
                }
              })
          };

          // Ouvrir une boite de dialogue pour les erreurs
          let openErrorDialog = (resp) => {
            switch (resp.status) {
              case 'exist':
                $scope.errorMessage.error = true;
                break;
              case 'logged':
              case 'access':
                $scope.errorMessage.error = false;
                $scope.login = resp.login;
                $scope.singup = resp.singup;
                break;
            }
            $scope.errorMessage.body = _.clone(resp.msg);
            $scope.loadingWorklow = false;
            jQuery('#modal-error').modal('show');
          }

          $scope.gtArchiveCandidat = () => {
            window.location.href = itOptions.Helper.archived_candidat_url;
          }

          $scope.gtClientArea = () => {
            window.location.href = itOptions.Helper.client_area_url;
          }

          jQuery('#modal-offer-interest')
            .on('hidden.bs.modal', () => {
              elButton.html(textButton);
            });

          jQuery('#modal-continue').on('hidden.bs.modal', () => {

          })
        }]
      }
    }])
    .run([function () {

    }]);
})();