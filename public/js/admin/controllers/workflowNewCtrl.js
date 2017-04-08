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
      if ($scope.permissionsSelected.indexOf(id) === -1) {
        $scope.permissionsSelected.push(id);
      }
      $permSelect.val(null).trigger("change");
    });
    
    $scope.deselectPermission = function (id) {
      var permIndex = $scope.permissionsSelected.indexOf(id);
      if (permIndex === -1) {
        return;
      }
      $scope.permissionsSelected.splice(permIndex, 1)
    }

    $scope.roleNames = {};
    organization.fetchRoles().then(function(roles) {
      $scope.roles = roles;
      for (var i = 0; i < roles.length; i++) {
        $scope.roleNames[roles[i].id] = roles[i].name;
      }
    }).catch(function(errors){
      $scope.alerts.resetAlerts();
      angular.forEach(errors, function (value, key) {
        $scope.alerts.addAlert('danger', value);
      });
    });

    var addRoleSelector = function (stepId) {
      var $roleSelect = $($('.role-select2')[$('.role-select2').length - 1]).select2({
        theme: 'classic',
        placeholder: 'Select a role',
      });
      $roleSelect.on("select2:select", function (e) {
        var data = e.params.data;
        var elem = e.params.data.element;
        var roleId = elem.getAttribute('data-role-id');
        var stepIndex = elem.getAttribute('data-step-index');
        if ($scope.steps[stepIndex].filter(function(role){return role.role_id === roleId}).length === 0) {
          $scope.steps[stepIndex].push({
            role_id: roleId,
            reviewer_count: 1
          });
        }
        $roleSelect.val(null).trigger("change");
      });
    }

    $scope.steps = [];
    $scope.addStep = function () {
      $scope.steps.push([])
      setTimeout(addRoleSelector, 0);
    }

    $scope.removeStep = function (stepIndex) {
      $scope.steps.splice(stepIndex, 1);
    }

    $scope.removeRoleFromStep = function (roleIndex, stepIndex) {
      var step = $scope.steps[stepIndex];
      step.splice(roleIndex, 1);
    }

    $scope.new_checker = null;
    $scope.saveWorkflow = function () {
      console.log($scope.steps);
    }
  }
]);
