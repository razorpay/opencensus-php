app.controller('AddOrgCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  '$upload',
  function ($scope, $http, alertsFactory, $stateParams, $upload) {
    debugger
    $scope.fetchOrg = function(id) {
      var request = $http({
        url: '/admin/generic',
        params: {
          route_name: 'org_get',
          url_params: {
            '{id}' : id
          }
        }
      });

      request.success(function(data) {
        if (data.success) {
          var organization = data.data
          $scope.organization = organization
        }
      })
    }

    $scope.organization = {
      auth_type: 'password'
    };

    if ($stateParams.id) {
      $scope.fetchOrg($stateParams.id)
    }

    $scope.editOrg = function(organization) {
      var data = {};
      data.body = organization;

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
          $scope.alerts.addAlert('success', 'Organization updated', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      });
    }

    // File uploads
    $scope.logos = {};

    $scope.onLoginLogoSelect = function ($files, fieldname) {
      var file = $files[0];

      $scope.logos.loginFileName = file;
      $scope.logos.loginFieldName = fieldname;

      // Start upload of the logo as soon as the selection is done

      var request = $upload.upload({
        url: '/admin/org/' + $scope.organization.id,
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
                '{id}' : $scope.organization.id
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
        url: '/admin/org/' + $scope.organization.id,
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
                '{id}' : $scope.organization.id
              }
            }
          });

          // Do nothing on success for now
          request.success(function (data) {});
        }
      });
    };

    $scope.save = function(organization) {
      if (organization.id) {
        $scope.editOrg(organization)
        return
      }

      var data = {}
      data.body = organization;
      data.route_name = 'org_create';

      var request = $http({
        method: 'post',
        url: '/admin/generic',
        data: data
      });

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Organization created successfully.', true);
        } else {
          $scope.alerts.addAlert('danger', null, true);
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    }
  }
])
