(function () {
  angular.module('interestsApp', [])
    .directive('askCv', [function () {
      return {
        restrict: 'E',
        templateUrl: itOptions.Helper.partialsUrl + '/ask-cv.html',
        scope: {
          cvId: '@'
        },
        link: function (scope, element, attrs) {
        },
        controller: ['$scope', '$http', function ($scope, $http) {
          const elButton = jQuery('#ask-cv');
          const textButton = elButton.html();
          $scope.message = { title: 'Impossible d\'ajouter le cv', body: '', error: true };
          $scope.i_am_interested_this_candidate = () => {
            let askForm = new FormData();
            askForm.append('action', 'get_ask_cv');
            askForm.append('cvId', $scope.cvId);
            elButton.text('Chargement en cours ...');
            $http({
              url: itOptions.Helper.ajax_url,
              method: "POST",
              headers: {
                'Content-Type': undefined
              },
              data: askForm
            })
              .then(resp => {
                let data = resp.data;
                elButton.html(textButton);
                if (!data.success) {
                  $scope.message.error = data.success;
                  $scope.message.body  = data.msg;

                  if (data.status === 'logged') {
                    $scope.login = data.data.loginUrl;
                    $scope.singup = data.data.singupUrl;
                  }
                  jQuery('#modal-error').modal('toggle')
                } else {
                  alertify.confirm("Voulez-vous vraiment voir le CV du candidat au complet?",
                    () => {
                      // redirect to CV
                      window.location.href = data.client.cv_url + "?token=" + data.client.token + "&cvId=" + $scope.cvId;
                    },
                    () => {
                      // Close alert
                    });
                }
              })
          }
        }]
      }
    }])
    .run([function () {

    }]);
})();