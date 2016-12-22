//Signin Controller
app.controller('AuthCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  '$location',
  'alertsFactory',
  'user',
  'transformRequestAsFormPost',
  function ($scope, $http, $state, $stateParams, $location, alertsFactory, user, transformRequestAsFormPost) {
    $scope.data = {};
    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.signUpEmail = $location.search().email || '';
    $scope.right = false;
        // location: '',
    $scope.goToSigninLayout = function () {
      $scope.right = true;
      const toRoute = 'access.signin';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
    }
    $scope.goToSignupLayout = function () {
      $scope.right = false;
      const toRoute = 'access.signup';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
    }
    // window.onpopstate = function (e) {
    //   debugger
    //   e.preventDefault
    // };
    // $scope.$on('$stateChangeStart', function (e, route) {
    //   // debugger
    //   console.log(e)
    //   console.log(route)
    //   if (['access.signin', 'access.signup'].indexOf(route.name) !== -1) {
    //     e.preventDefault()
    //     const toRoute = route.name === 'access.signup' ? '#/access/signup' : '#/access/signin'
    //     window.history.pushState({}, '', toRoute)

    //   }
    // });

    // $scope.$on('$stateChangeSuccess', function (e, route) {
    //   debugger
    //   console.log(e)
    //   console.log(route)
    // });
    // $scope.$on('$stateChangeError', function (e, route) {
    //   debugger
    //   console.log(e)
    //   console.log(route)
    // });

  }
]);
