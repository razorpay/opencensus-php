app.controller('AddOrgCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  '$upload',
  'organization',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, $stateParams, $upload, organization, transformRequestAsFormPost) {
    $scope.selected_permissions = {};
    $scope.select_all = false;

    var getAllPermissions = function () {
      var request = $http({
        url: '/admin/generic',
        params: {
          route_name: 'permission_get_by_type',
          url_params: {
            '{type}': 'all'
          }
        }
      });

      request.success(function (data) {
        $scope.permissions = data.data.items;
      });
    };

    getAllPermissions();

    $scope.selectAll = function() {
      $scope.selected_permissions = {};

      if (!$scope.select_all) {
        return;
      }

      $scope.permissions.map(function(perm){
        $scope.selected_permissions[perm.id] = true;
      });
    }

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

          organization.permissions.forEach(function (perm) {
            $scope.selected_permissions[perm.id] = true;
          })
        }
      })
    };

    $scope.fetchAssignablePermissions = function () {
      var request = $http({
        url: '/admin/generic',
        params: {
          route_name: 'permission_get_by_type',
          url_params: {
            '{type}': 'assignable'
          }
        }
      });

      request.success(function (data) {
        if (data.success) {
          data.data.items.forEach(function (perm) {
            $scope.selected_permissions[perm.id] = true;
          })
        }
      });
    };

    $scope.organization = {
      auth_type: 'password'
    };

    if ($stateParams.id) {
      // Edit page
      $scope.fetchOrg($stateParams.id)
    }
    else {
      // Add page

      // Fetch all the assignable permissions
      $scope.fetchAssignablePermissions();
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

      // selected_permissions will be like:
      // { perm_id: true, perm_id2: false, perm_id3: true, ... }

      // unset the array first
      organization.permissions = [];

      for (var key in $scope.selected_permissions) {
        if ($scope.selected_permissions.hasOwnProperty(key)) {

          if ($scope.selected_permissions[key]) {
            organization.permissions.push(key);
          }

        }
      }

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
          },
          transformRequest: transformRequestAsFormPost
        });
      }
      // add
      else {
        data.body = organization;
        data.route_name = 'org_create';

        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
          transformRequest: transformRequestAsFormPost
        });
      }

      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Organization saved successfully.', true);
          $state.go('app.orgs.edit', {id: data.data.id});
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
