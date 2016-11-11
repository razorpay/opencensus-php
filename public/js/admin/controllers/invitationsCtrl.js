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

      /**
       * TODO: remove mocked data
       */
      $scope.invitations = [{
        id: '1223',
        merchant_email: 'first@lead.com',
      },{
        id: '5432',
        merchant_email: 'second@lead.com',
      }]

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
