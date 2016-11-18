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

    function findOrgIndexById(id) {
      var index = null;

      $scope.organizations.forEach(function (v, i) {
        if (v.id === id) {
          index = i;
        }
      });

      // returned index can be 0 so don't just do a if (index)
      return index;
    }

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

    $scope.openEditOrgModal = function (id) {
      $scope.selected = $scope.organizations.filter(function(x) {
        return x['id'] === id;
      });

      var modalInstance = $modal.open({
        templateUrl: 'editOrgModalContent.html',
        controller: 'editOrgModalCtrl',
        resolve: {
          current: function() {
            return jQuery.extend({}, $scope.selected[0]);
          }
      }
      });
      modalInstance.result.then($scope.editOrgById, $.noop);
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
          $scope.organizations.unshift(data.data);
          $scope.count = $scope.organizations.length;
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
      var request = $http.get('/admin/generic', {
        params: {
          route_name: 'org_get_multiple'
        }
      });

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

    // Edit Organization

    $scope.editOrgById = function (organization) {
      var data = {};
      data.body = organization;
      data.route_name = organization.route_name;

      delete data.body.route_name;

      var request = $http.put('/admin/generic', data, {
        params: {
          route_name: 'org_edit',

          url_params: {
            '{id}' : organization.id
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          // Update the org model (todo: make this a helper)

          var index = findOrgIndexById(data.data.id);

          if (index !== null) {
            $scope.organizations[index] = data.data;
          }

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

    $scope.deleteOrg = function(id) {
      var request = $http.delete('/admin/generic', {
        params: {
          route_name: 'org_delete',

          url_params: {
            '{id}': id
          }
        }
      });
      request.success(function (data) {
        /* TODO: change this */
        if (data.success) {
          var index = findOrgIndexById(id);

          if (index !== null) {
            $scope.organizations.splice(index, 1);
          }

          $scope.count = $scope.organizations.length;

          $scope.alerts.addAlert('success', 'Organization deleted', true);
        }
      });
    };
  }
]).controller('addOrgModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.organization = {
      auth_type: 'password',
      route_name: 'org_create'
    };

    $scope.ok = function (organization) {
      $modalInstance.close(organization);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('editOrgModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'current',
  '$upload',
  function ($scope, $modalInstance, $http, current, $upload) {
    $scope.organization = jQuery.extend({
      auth_type: 'password',
      route_name: 'org_edit'
    }, current);

    // File uploads
    $scope.logos = {};

    $scope.onLoginLogoSelect = function ($files, fieldname) {
      var file = $files[0];

      $scope.logos.loginFileName = file;
      $scope.logos.loginFieldName = fieldname;

      // Start upload of the logo as soon as the selection is done

      var request = $upload.upload({
        url: '/admin/org/' + current.id,
        method: 'POST',
        file: $scope.logos.loginFileName,
        fileFormDataName: $scope.logos.loginFieldName,
        data: { type: 'login' }
      });

      request.success(function (data, status, headers, config) {
        if (data.success) {
          var url = data.data;

          var data = {
            body: {
              login_logo_url: url
            }
          };

          // Update org details
          var request = $http.put('/admin/generic', data, {
            params: {
              route_name: 'org_edit',

              url_params: {
                '{id}' : current.id
              }
            }
          });

          // Do nothing on success for now
          request.success(function (data) {});
        }
      });

      // end@onLoginLogoSelect
    };

    $scope.onDashboardLogoSelect = function ($files, fieldname) {
      var file = $files[0];

      $scope.logos.dashboardFileName = file;
      $scope.logos.dashboardFieldName = fieldname;

      var request = $upload.upload({
        url: '/admin/org/' + current.id,
        method: 'POST',
        file: $scope.logos.dashboardFileName,
        fileFormDataName: $scope.logos.dashboardFieldName,
        data: { type: 'main' }
      });

      request.success(function (data, status, headers, config) {
        if (data.success) {
          var url = data.data;

          var data = {
            body: {
              main_logo_url: url
            }
          };

          // Update org details
          var request = $http.put('/admin/generic', data, {
            params: {
              route_name: 'org_edit',

              url_params: {
                '{id}' : current.id
              }
            }
          });

          // Do nothing on success for now
          request.success(function (data) {});
        }
      });
    };

    $scope.ok = function (organization) {
      $modalInstance.close(organization);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
