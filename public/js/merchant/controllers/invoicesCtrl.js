app.controller('InvoicesCtrl', [
  '$rootScope',
  'user',
  '$scope',
  '$state',
  function($rootScope, user, $scope, $state) {
    user.identity().then(function(user) {
      $scope.user = user;
      $scope.merchant = !!user.current;
    });

    var titleHash = {
      'app.invoices.list': 'Invoices',
      'app.invoices.details': 'Invoice Detail',
      'app.invoices.new': 'New Invoice',
      'app.invoices.edit': 'Edit Invoice',
      'app.invoices.customers': 'Customers',
      'app.invoices.items': 'Items',
    };

    function setTitle(state) {
      $scope.title = titleHash[state];
    }

    function canShowNav(state) {
      $scope.canShowNav =
        [
          'app.invoices.list',
          'app.invoices.customers',
          'app.invoices.items',
        ].indexOf(state) !== -1;
    }

    $rootScope.$on('$stateChangeSuccess', function(event, toState) {
      setTitle(toState.name);
      canShowNav(toState.name);
    });

    setTitle($state.current.name);
    canShowNav($state.current.name);
  },
]);
