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

    // Add an organization

    $scope.addOrg = function(organization) {
      var data = {};
      data.body = organization;
      data.route_name = organization.route_name;

      delete data.body.route_name;

      var request = $http({
        method: 'post',
        url: '/admin/generic',
        data: data
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Organization added', true);
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

    // Fetch the entire org list to show in a table

    $scope.fetchOrgs = function () {
      var request = $http.get('/admin/generic', { params: { route_name: 'org_get_multiple' } });

      /**
       * TODO: remove mocked data
       */
      $scope.organizations = []
      $scope.count = 0;

      request.success(function (data) {
        if (data.success) {
          $scope.organizations = data.data.items;
          $scope.count = data.data.count;
        }
      });
    }

    $scope.fetchOrgs();

    // Fetch Org details by ID

    $scope.fetchOrgById = function (id) {
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'org_get',

          url_params: {
            '{id}': id
          }
        }
      });

      request.success(function (data) {

      });
    };

    $scope.fetchOrgById('6dLbNSpv5XbCOG');

    // Edit Organization

    $scope.editOrgById = function (id) {
      var request = $http.put('/admin/generic', {
        params: {
          route_name: 'org_edit',

          url_params: {
            '{id}' : id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Organization updated', true);
        }
        else {
          $scope.alerts.resetAlerts();

          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }

      });
    };

    // $scoe.editOrgById(id);
  }
]).controller('addOrgModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.organization = {
      route_name: 'org_create'
    };

    $scope.ok = function (organization) {
      $modalInstance.close(organization);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
])
