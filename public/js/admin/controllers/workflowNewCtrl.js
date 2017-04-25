'use strict';
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
  function(
    $scope,
    $http,
    alertsFactory,
    organization,
    $state,
    $modal,
    $stateParams,
    admin
  ) {
    $scope.permissions = {};
    $scope.permissionsOptions = [];
    $scope.permissionsSelected = [];
    $scope.levels = [];
    $scope.roles = [];
    $scope.roleNames = {};
    $scope.workflowName = '';
    $scope.workflowId = '';
    $scope.editLayout = false;
    $scope.alerts = alertsFactory.getHandler();
    admin.identity().then(function(data) {
      $scope.admin = data;
    });

    if ($state.current.name === 'app.workflows.edit') {
      $scope.editLayout = true;
      $scope.workflowId = $stateParams.id;
    }

    var fetchWorkflow = function(id) {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'workflow_get',
          url_params: {
            '{id}': id,
          },
        },
      });
      request.success(function(data) {
        if (data.success) {
          $scope.workflowName = data.data.name;
          $scope.isEditable = data.data.isEditable;

          for (var i = 0; i < data.data.permissions.length; i++) {
            $scope.permissionsSelected.push(data.data.permissions[i].id);
          }

          data.data.levels.forEach(function(level) {
            $scope.levels[level.level - 1] = {
              level: level.level,
              op_type: level.op_type
            };

            if (level.steps && level.steps.length) {

              // For each role(step), keep only role_id and reviewer_count properties
              for (var index in level.steps) {
                level.steps[index] = {
                  role_id: level.steps[index].role_id,
                  reviewer_count: level.steps[index].reviewer_count
                };
              }

              $scope.levels[level.level - 1].steps  = level.steps;
            }
            setTimeout(initRoleSelector, 0);
          });
        }

        // Show warning message on top if user cannot edit
        if (!$scope.isEditable) {
          $scope.alerts.addAlert('warning', 'An action is pending corresponding to this workflow. You cannot edit this workflow right now.');
        }
      });
    };

    if ($scope.editLayout) {
      fetchWorkflow($scope.workflowId);
    } else {
      $scope.isEditable = true;
    }

    // Fetch permissions list corresponding to type workflow
    var fetchPermissions = function() {
      var request = $http.get('/admin/generic', {
        ignoreErrors: true,
        params: {
          route_name: 'permission_get_multiple',
          count: 1000 /* A very high number, todo discuss with Rishabh */,
          url_params: {
            '{type}': 'workflow'
          }
        },
      });

      request.success(function(data) {
        if (data.success) {
          // $scope.permissions = data.data.items;
          var permissions = data.data.items;
          for (var i = 0; i < permissions.length; i++) {
            $scope.permissions[permissions[i]['id']] = permissions[i];
            $scope.permissionsOptions.push(permissions[i]['id']);
          }
        }
      });
    };
    fetchPermissions();

    var $permSelect = $('.workflow-select2').select2({
      theme: 'classic',
      placeholder: 'Select an action',
    });
    $permSelect.on('select2:select', function(e) {
      var id = e.params.data.id;
      if ($scope.permissionsSelected.indexOf(id) === -1) {
        $scope.permissionsSelected.push(id);
      }
      $permSelect.val(null).trigger('change');
    });

    // Remove the selected permission
    $scope.deselectPermission = function(id) {
      var permIndex = $scope.permissionsSelected.indexOf(id);
      if (permIndex === -1) {
        return;
      }
      $scope.permissionsSelected.splice(permIndex, 1);
    };

    organization
      .fetchRoles()
      .then(function(roles) {
        $scope.roles = roles;
        for (var i = 0; i < roles.length; i++) {
          $scope.roleNames[roles[i].id] = roles[i].name;
        }
      })
      .catch(function(errors) {
        $scope.alerts.resetAlerts();
        angular.forEach(errors, function(value, key) {
          $scope.alerts.addAlert('danger', value);
        });
      });

    // Initialize each new step with role selector picker input field
    var addRoleSelector = function(roleSelector) {
      var $roleSelect = roleSelector;
      $roleSelect.select2({
        theme: 'classic',
        placeholder: 'Select a role',
      });

      $roleSelect.on('select2:select', function(e) {
        var data = e.params.data;
        var elem = e.params.data.element;
        var roleId = elem.getAttribute('data-role-id');
        var stepIndex = elem.getAttribute('data-step-index');

        // Check and add a role(step) in the corresponding level, if not present
        if (
          !$scope.levels[stepIndex].steps.some(function(role) {
            return role.role_id === roleId
          })
        ) {
          $scope.levels[stepIndex].steps.push({
            role_id: roleId,
            reviewer_count: 1,
          });
        }

        $roleSelect.val(null).trigger('change');
      });
    };

    // Init role selector for all existing levels in the view
    function initRoleSelector() {
      var $roleSelect = $('.role-select2');
      addRoleSelector($roleSelect);
    }

    // Attach role selector to the newly added level
    function attachRoleSelector() {
      var $roleSelect = $(
        $('.role-select2')[$('.role-select2').length - 1]
      );

      addRoleSelector($roleSelect);
    }

    // Add a new level and initialize with empty roles(steps)
    $scope.addStep = function() {
      $scope.levels.push({
        steps: [],
        op_type: 'and'
      });

      setTimeout(attachRoleSelector, 0);
    };

    // Remove corresponding level box
    $scope.removeStep = function(stepIndex) {
      $scope.levels.splice(stepIndex, 1);
    };

    // Remove roles(step) from level
    $scope.removeRoleFromStep = function(roleIndex, stepIndex) {
      var step = $scope.levels[stepIndex].steps;
      step.splice(roleIndex, 1);
    };

    // Increment count corresponding to the role
    $scope.incReviewerCount = function(roleIndex, stepIndex) {
      if ($scope.levels[stepIndex].steps[roleIndex].reviewer_count >= 99) return;
      $scope.levels[stepIndex].steps[roleIndex].reviewer_count++;
    };

    // Decrement count corresponding to the role
    $scope.decReviewerCount = function(roleIndex, stepIndex) {
      if ($scope.levels[stepIndex].steps[roleIndex].reviewer_count <= 1) return;
      $scope.levels[stepIndex].steps[roleIndex].reviewer_count--;
    };

    $scope.new_checker = null;

    // Iterating all levels currently added in view - Null Role check and populate levels with level no.
    function checkIfLevelsValid(payload) {
      var valid = true;

      for (var s_i = 0; s_i < $scope.levels.length; s_i++) {
        var steps = $scope.levels[s_i].steps;

        // Each level must have atleast one role or else level must be removed by user
        if (!steps.length) {
          $scope.alerts.addAlert(
            'danger',
            'Please add atleast one role to all the steps'
          );
          valid = false;
          break;
        }

        $scope.levels[s_i].level = s_i + 1;
        payload.levels.push($scope.levels[s_i]);
      }

      return valid;
    }

    // Request to Create a new workflow
    $scope.createWorkflow = function() {
      if (!$scope.isEditable) {
        return;
      }

      $scope.alerts.resetAlerts();
      var valid = true;
      var payload = {
        levels: [],
        org_id: $scope.admin.org_id,
      };
      if (!$scope.workflowName) {
        valid = false;
        $scope.alerts.addAlert('danger', 'Please enter workflow name');
      }
      payload.name = $scope.workflowName;
      if (!$scope.permissionsSelected.length) {
        valid = false;
        $scope.alerts.addAlert(
          'danger',
          'Please select atleast one permission'
        );
      }
      payload.permissions = $scope.permissionsSelected.slice();
      if (!$scope.levels.length) {
        valid = false;
        $scope.alerts.addAlert('danger', 'Please add atleast one step');
      }

      valid = checkIfLevelsValid(payload);

      if (!valid) return;

      var request = $http({
        url: '/admin/generic',
        method: 'POST',
        params: {
          route_name: 'workflow_create',
        },
        data: {
          body: payload,
        },
      });

      request.success(function(data) {
        if (data.success) {
          $scope.alerts.addAlert(
            'success',
            $scope.workflowName + ' - Workflow created successfully'
          );
          $state.transitionTo(
            'app.workflows.edit',
            { id: data.data.id },
            { notify: false }
          );
          $scope.editLayout = true;
          $scope.workflowId = data.data.id; // Update workflow id once flow is created.
        }  else {
          $scope.alerts.addAlert('danger', 'Creating workflow failed: ' + data.errors.join(', '))
        }
      });
    };

    // Request to Edit a workflow if user is allowed
    $scope.editWorkflow = function() {
      if (!$scope.isEditable) {
        return;
      }
      $scope.alerts.resetAlerts();
      var valid = true;
      var payload = {
        levels: []
      };

      // Check for empty workflow name
      if (!$scope.workflowName) {
        valid = false;
        $scope.alerts.addAlert('danger', 'Please enter workflow name');
      } else {
        payload.name = $scope.workflowName;
      }

      // Check if no permissions selected
      if (!$scope.permissionsSelected.length) {
        valid = false;
        $scope.alerts.addAlert(
          'danger',
          'Please select atleast one permission'
        );
      } else {
        payload.permissions = $scope.permissionsSelected.slice();
      }

      // Check if no levels selected
      valid = checkIfLevelsValid(payload);

      if (!valid) return;

      var request = $http({
        url: '/admin/generic',
        method: 'PUT',
        params: {
          route_name: 'workflow_update',
          url_params: {
            '{id}': $scope.workflowId,
          },
        },
        data: {
          body: payload,
        },
      });
      request.success(function(data) {
        if (data.success) {
          $scope.alerts.addAlert(
            'success',
            $scope.workflowName + ' - Workflow updated successfully'
          );
        }  else {
          $scope.alerts.addAlert('danger', 'Update failed: ' + data.errors.join(', '))
        }
      });
    };

    // Filter roles(steps)if already added by the user in the corresponding level.
    $scope.filterItems = function(stepInd) {
      return function(role) {

        // Iterate all roles(steps) of corresponding level to check if role id is present
        for (var key in $scope.levels[stepInd].steps) {
          if (
            $scope.levels[stepInd].steps.hasOwnProperty(key) &&
            $scope.levels[stepInd].steps[key].role_id === role.id
          ) {
            return false;
          }
        }

        return true;
      };
    };
  },
]);
