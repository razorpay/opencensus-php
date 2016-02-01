/**
 * Referrals Ctrl
 */
app.controller('ReferralsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();

    var fetchReferrals = function() {
      var request = $http({
        method: 'get',
        url: '/referrals'
      });

      request.success(function (data) {
        if (data.success) {
          $scope.referrals = data.data;
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    fetchReferrals();

}]);
