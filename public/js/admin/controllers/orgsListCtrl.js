//Merchant List controller
app.controller('OrgsListCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.organizations = [];
    $scope.count = 0;

    /**
     * Modal openers
     */
    $scope.openAddOrgModal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addOrgModalContent.html',
        controller: 'addOrgModalCtrl'
      });
      modalInstance.result.then($scope.addOrg, $.noop);
    };


    /**
     * Actions
     */

    $scope.addOrg = function(organization) {
      var request = $http({
        method: 'post',
        url: '/admin/generic',
        data: organization
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Organization added successfully', true);
        }
        else {
          $scope.alerts.resetAlerts();

          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }

    $scope.fetchOrgs = function () {
      var request = $http.get('/orgs');

      /**
       * TODO: remove mocked data
       */
      $scope.organizations = [{
        id: '2sGsPw5xI4aNn',
        business_name: 'Govinda',
        display_name: 'Raja Babu',
        email: 'raja@babu.com',
        email_domains: 'babu.com, raja.com'
      },{
        id: 'Uy3A6sNq0P5hA',
        business_name: 'Batman',
        display_name: 'Batman Kumar',
        email: 'batman@kumar.com',
        email_domains: 'babu.com, raja.com'
      }]
      $scope.count= 2;
      request.success(function (data) {
        if (data.success) {
          $scope.organizations = data.data.data;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchOrgs();
  }
]).controller('addOrgModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.ok = function (organization) {
      $modalInstance.close(organization);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
])
