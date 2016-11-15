//Merchant List controller
app.controller('InvitationsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.invitations = [];
    $scope.count = 0;

    $scope.fetchInvitations = function () {
      var request = $http.get('/invitations');

      request.success(function (data) {
        if (data.success) {
          $scope.invitations = data.data.data;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchInvitations();
  }
])
