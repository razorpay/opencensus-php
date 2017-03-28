"use strict";
//Entities Listing Controller
app.controller('WorkflowNewCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  '$state',
  '$modal',
  '$stateParams',
  'admin',
  function ($scope, $http, alertsFactory, $state, $modal, $stateParams, admin) {
    $scope.new_checker = null;
    $scope.workflows = {
      0: "Initiate Settlement",
      1: "Upload Settlement Reconciliation (UTR)",
      2: "Upload Reconciliation File (Payment/Refund)",
      3: "Add IIN Rule",
      4: "Add EMI Plan",
      5: "Verify Payment",
      6: "Archive Merchant",
      7: "Confirm User",
      8: "Verify All Payments",
      9: "Generate Refunds Excel (Netbanking)",
      10: "Generate Beneficiary File",
      11: "Download Beneficiary File",
      12: "Authorize Failed Payment",
      13: "Send Merchant Newsletter",
      14: "Create Schedule",
      15: "Trigger Dummy Error",
      16: "Make API Call",
    }
    $scope.workflowOptions = Object.keys($scope.workflows);
    $scope.selectedWorkflows = [];
    $scope.checkers = [
    {
      id: 0,
      name: 'Ashmeet'
    }, {
      id: 1,
      name: 'Rishabh'
    }, {
      id: 2,
      name: 'Chetty'
    }];
    var $workflowSelect = $('.workflow-select2').select2({
      theme: 'classic',  
      placeholder: 'Select an action',

    });
    $workflowSelect.on("select2:select", function (e) {
      var id = e.params.data.id;
      $scope.selectedWorkflows.push(id);
      var index = $scope.workflowOptions.indexOf(id);
      if (index > -1) {
        $scope.workflowOptions.splice(index, 1);
      }
      $workflowSelect.val(null).trigger("change");
      console.log(e);
    });
    $scope.deselectWorkflow = function (id) {
      var workflow = $scope.selectedWorkflows[id];
      $scope.workflows.push(workflow);
      console.log($scope.workflows)
      delete $scope.selectedWorkflows[id];
    }
  }
]);
