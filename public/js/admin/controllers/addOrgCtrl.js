app.controller('AddOrgCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  '$upload',
  function ($scope, $http, alertsFactory, $stateParams, $upload) {
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

    $scope.uploadFile = function (file, fieldName, type) {
      return $upload.upload({
        url: '/admin/org/' + $scope.organization.id,
        method: 'POST',
        file: file,
        fileFormDataName: fieldName,
        data: { type: type }
      })
    }

    $scope.onInvoiceLogoSelect = function ($files) {
      var file = $files[0];
      $scope.uploadFile(file, 'invoice_logo', 'invoice').success(function(response) {
        if (response.success) {
          $scope.organization.invoice_logo_url = response.data;
        }
      });
    };

    $scope.onMainLogoSelect = function ($files) {
      var file = $files[0];
      $scope.uploadFile(file, 'main_logo', 'main').success(function(response) {
        if (response.success) {
          $scope.organization.main_logo_url = response.data;
        }
      });
    };

    $scope.onLoginLogoSelect = function ($files, fieldname) {
      var file = $files[0];
      $scope.uploadFile(file, 'login_logo', 'login').success(function(response) {
        if (response.success) {
          $scope.organization.login_logo_url = response.data;
        }
      });
    };

    $scope.save = function(organization) {
      var data = {};

      // edit
      if (organization.id) {
        data.body = jQuery.extend(true, {}, organization);
        delete data.body.id;
        delete data.body.created_at;
        delete data.body.admin;
        delete data.body.entity;

        var request = $http.put('/admin/generic', data, {
          params: {
            route_name: 'org_edit',
            url_params: {
              '{id}' : $scope.organization.id
            }
          }
        });
      }
      // add
      else {
        data.body = organization;
        data.route_name = 'org_create';

        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data
        });
      }

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Organization saved successfully.', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });

      return request
    }
  }
])
