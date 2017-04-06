"use strict";
//Entities Listing Controller
app.controller('WorkflowNewCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'organization',
  '$state',
  '$modal',
  '$stateParams',
  'admin',
  function ($scope, $http, alertsFactory, organization, $state, $modal, $stateParams, admin) {
    $scope.permissions = {};
    $scope.permissionsOptions = [];
    $scope.permissionsSelected = [];
    $scope.roles = [];
    $scope.rolesSelected = [];
    var fetchPermissions = function () {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'permission_get_multiple',
          count: 1000,  /* A very high number, todo discuss with Rishabh */
        }
      });

      request.success(function (data) {
        if (data.success) {
          // $scope.permissions = data.data.items;
          var permissions = data.data.items;
          for (var i = 0; i < permissions.length; i++) {
            $scope.permissions[permissions[i]['id']] = permissions[i];
            $scope.permissionsOptions.push(permissions[i]['id']);
          }
        }
      });
    }
    fetchPermissions()

    var $permSelect = $('.workflow-select2').select2({
      theme: 'classic',  
      placeholder: 'Select an action',
    });
    $permSelect.on("select2:select", function (e) {
      var id = e.params.data.id;
      $scope.permissionsSelected.push(id);
      var index = $scope.permissionsOptions.indexOf(id);
      if (index > -1) {
        $scope.permissionsOptions.splice(index, 1);
      }
      $permSelect.val(null).trigger("change");
      // console.log(e);
    });
    $scope.deselectWorkflow = function (id) {
      var workflow = $scope.permissionsSelected[id];
      $scope.workflows.push(workflow);
      // console.log($scope.workflows)
      delete $scope.permissionsSelected[id];
    }

    organization.fetchRoles().then(function(roles) {
      $scope.roles = roles;
    }).catch(function(errors){
      $scope.alerts.resetAlerts();
      angular.forEach(errors, function (value, key) {
        $scope.alerts.addAlert('danger', value);
      });
    });


    var $roleSelect = $('.role-select2').select2({
      theme: 'classic',  
      placeholder: 'Select a role',
    });
    $roleSelect.on("select2:select", function (e) {
      console.log(e)
      var id = e.params.data.id;
      $scope.rolesSelected.push(id);
      $roleSelect.val(null).trigger("change");
      // console.log(e);
    });


    $scope.new_checker = null;

  }
]);
