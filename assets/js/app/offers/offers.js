(function () {
  angular.module('OfferApp', ['ngMessages', 'ngSanitize'])
    .factory('offerFactory', ['$http', '$q', function ($http, $q) {
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
})(angular);