//Merchant List controller
app.controller('OrgsAddUsersCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  'organization',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal, organization) {

    $scope.roles = organization.fetchRoles('org_6dLbNSpv5XbCOG');
    $scope.groups = organization.fetchGroups('org_6dLbNSpv5XbCOF');

    $scope.user = {};

    /**
     * Actions
     */
  }
])