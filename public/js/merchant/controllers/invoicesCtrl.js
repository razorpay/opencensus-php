app.controller('InvoicesCtrl', [
  '$rootScope',
  '$scope',
  '$state',
  function ($rootScope, $scope, $state) {
    var titleHash = {
      'app.invoices.list': 'Invoices',
      'app.invoices.details': 'Invoice Detail',
      'app.invoices.new': 'New Invoice',
      'app.invoices.edit': 'Edit Invoice',
      'app.invoices.customers': 'Customers',
      'app.invoices.items': 'Items'
    }

    function setTitle(state) {
      $scope.title = titleHash[state]
    }

    $rootScope.$on('$stateChangeSuccess', function(event, toState) {
      setTitle(toState.name)
    })

    setTitle($state.current.name)
  }
]);
