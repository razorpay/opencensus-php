app.controller('AddOrgFieldMap', [
  '$scope',
  '$http',
  'alertsFactory',
  '$stateParams',
  '$state',
  'transformRequestAsFormPost',
  function(
    $scope,
    $http,
    alertsFactory,
    $stateParams,
    $state,
    transformRequestAsFormPost
  ) {
    var orgId = $stateParams.id;
    var fieldMapId = $stateParams.fieldMapId;

    $scope.fetchOrgFieldMap = function() {
      var data = {
        route_name: 'org_fieldmap_get',
        url_params: {
          '{orgId}': orgId,
          '{id}': fieldMapId,
        },
      };

      var request = $http.get('/admin/generic', {
        params: data,
      });

      request.success(function(data) {
        if (data.success) {
          var fieldMap = data.data;
          $scope.fieldMap = fieldMap;
        }
      });
    };

    if (orgId && fieldMapId) {
      $scope.fetchOrgFieldMap();
    }

    $scope.save = function(fieldMap) {
      // edit
      if (fieldMap.id) {
        var fields = fieldMap.fields;
        if (!Array.isArray(fields)) {
          fieldMap.fields = fields.split(',');
        }

        for (var field in fieldMap.fields) {
          fieldMap.fields[field] = fieldMap.fields[field].trim();
        }

        var data = {
          route_name: 'org_fieldmap_edit',
          url_params: {
            '{orgId}': orgId,
            '{id}': $scope.fieldMap.id,
          },
        };
        data.body = jQuery.extend(true, {}, fieldMap);
        delete data.body.id;

        var request = $http({
          method: 'put',
          url: '/admin/generic',
          data: data,
        });
      } else {
        // add
        var fields = fieldMap.fields;
        if (!Array.isArray(fields)) {
          fieldMap.fields = fields.split(',');
        }

        for (var field in fieldMap.fields) {
          fieldMap.fields[field] = fieldMap.fields[field].trim();
        }

        var data = {
          route_name: 'org_fieldmap_create',
          url_params: {
            '{orgId}': orgId,
          },
          body: fieldMap,
        };

        var request = $http({
          method: 'post',
          url: '/admin/generic',
          data: data,
        });
      }

      request
        .success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert(
              'success',
              'Field Map saved successfully.',
              true
            );
            $state.go('app.orgs.fieldmaps.edit', { fieldMapId: data.data.id });
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value, key) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function() {
          $scope.alerts.addAlert('danger', null, true);
        });

      return request;
    };
  },
]);
