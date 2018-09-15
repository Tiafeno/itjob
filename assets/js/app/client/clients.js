angular.module('clientApp', ['ngMessages'])
  .config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('[[').endSymbol(']]');
  })
  .factory('clientFactory', ['$http', '$q', function ($http, $q) {
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
  .service('clientService', [function() {
    this.offers = _.clone(itOptions.offers);
  }])
  .controller('clientCompanyCtrl', ['$scope', 'clientFactory', 'clientService',
    function ($scope, clientFactory, clientService) {
    // Trash offert
    $scope.trashOffer = function (offerId) {
      var offer = _.findWhere(clientService.offers, {ID: parseInt(offerId)});
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
  }])