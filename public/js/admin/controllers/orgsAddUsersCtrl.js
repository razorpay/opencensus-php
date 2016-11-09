//Merchant List controller
app.controller('OrgsAddUsersCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {

    $scope.roles = {'finance': 'Finance', 'manager': 'Manager'}
    $scope.groups = [
      {
        'name': 'Bangalore',
        'code': 'bangalore',
        'description': 'Hello Bangalore'
      }, {
        'name': 'Kolkata',
        'code': 'kolkata',
        'description': 'Hello Kolkata'
      }]

    $scope.user = {};

    /**
     * Actions
     */
  }
])