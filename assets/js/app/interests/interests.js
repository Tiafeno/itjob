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
          $scope.message = {
            title: 'Impossible d\'ajouter le cv',
            body: '',
            error: true
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

          // Envoyer la requete
          let sendInterest = (offer_id) => {
            if (!_.isNumber(offer_id)) return;
            let askForm = new FormData();
            askForm.append('action', 'get_ask_cv');
            askForm.append('cv_id', $scope.cvId);
            askForm.append('offer_id', offer_id);
            jQuery('#modal-offer-interest').modal('hide');
            $http({
                url: itOptions.Helper.ajax_url,
                method: "POST",
                headers: {
                  'Content-Type': undefined
                },
                data: askForm
              })
              .then(resp => {
                let query = resp.data;
                elButton.html(textButton);
                if (!query.success) {
                  let response = query.data;
                  switch (response.status) {
                    case 'exist':
                      $scope.message.error = true;
                      break;
                    case 'logged':
                    case 'access':
                      $scope.message.error = false;
                      $scope.login = response.login;
                      $scope.singup = response.singup;
                      break;
                  }
                  $scope.message.body = response.msg;
                  jQuery('#modal-error').modal('toggle');
                } else {
                  // Success
                  alertify.alert("CV sélectionner avec succès. Pour gérer les candidats: " +
                    "<br>Allez dans votre espace client ensuite dans <b>Mes offres</b>", () => {

                    });
                }
              })
          };

          $scope.$watch('offer_id', (offer) => {
            console.log(offer);
          }, true);

          jQuery('#modal-offer-interest').on('hidden.bs.modal', function () {
            elButton.html(textButton);
            $scope.offer_id = 0;
          })
        }]
      }
    }])
    .run([function () {

    }]);
})();