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
    $('.select2').select2({
    });
  }
]);
