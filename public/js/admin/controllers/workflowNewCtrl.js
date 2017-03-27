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
    $scope.selectedWorkflows = {};
    $scope.workflows = [{
      id: 0,
      name: "Initiate Settlement"
    }, {
      id: 1,
      name: "Upload Settlement Reconciliation (UTR)"
    }, {
      id: 2,
      name: "Upload Reconciliation File (Payment/Refund)"
    }, {
      id: 3,
      name: "Add IIN Rule"
    }, {
      id: 4,
      name: "Add EMI Plan"
    }, {
      id: 5,
      name: "Verify Payment"
    }, {
      id: 6,
      name: "Archive Merchant"
    }, {
      id: 7,
      name: "Confirm User"
    }, {
      id: 8,
      name: "Verify All Payments"
    }, {
      id: 9,
      name: "Generate Refunds Excel (Netbanking)"
    }, {
      id: 10,
      name: "Generate Beneficiary File"
    }, {
      id: 11,
      name: "Download Beneficiary File"
    }, {
      id: 12,
      name: "Authorize Failed Payment"
    }, {
      id: 13,
      name: "Send Merchant Newsletter"
    }, {
      id: 14,
      name: "Create Schedule"
    }, {
      id: 15,
      name: "Trigger Dummy Error"
    }, {
      id: 16,
      name: "Make API Call"
    }];
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
    });
    $workflowSelect.on("select2:select", function (e) {
      $scope.selectedWorkflows[e.params.data.id] = (e.params.data.text);
      console.log($scope.selectedWorkflows);
      $workflowSelect.val(null).trigger("change");
      console.log(e)
    });
  }
]);
