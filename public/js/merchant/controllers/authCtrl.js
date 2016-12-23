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
  '$analytics',
  '$window',
  '$cookies',
  function ($scope, $http, $state, $stateParams, $location, alertsFactory, user, 
    transformRequestAsFormPost, $analytics, $window, $cookies) {
    $scope.toArray = function (obj) {
      if (!obj) {
        return [];
      }
      return Object.keys(obj);
    }
    $scope.data = {};
    $scope.alerts = alertsFactory.getHandler();
    $scope.right = false;
    $scope.signup = {
      currentStep: 2, // 0, 1, 2, 3
      currentSubStep: 0, // 0, 1, 2, 3, 4
      data: {
        email: $location.search().email || '',
        password: '',
        captcha: null,
        business_type: null,

      },
      details: {
        business_type: {
          private_ltd: 'Private Limited',
          propreitorship: 'Propreitorship',
          partnership: 'Partnership',
          llp: 'LLP',
          edu: 'Educational Institutes',
          trust_society: 'Trust / Society',
          individual: 'Individual',
          public_ltd: 'Public Limited',
          ngo: 'NGO'
        },

        monthly_transaction: {
          yet_to_start: 'Haven’t started processing yet',
          below_1lac: 'Less than 1 Lac',
          below_20lac: '1 Lac to 20 Lacs',
          below_1crore: '20 Lacs to 1 Crore',
          above_1crore: 'More than 1 Crore'
        },

        role: {
          founder: 'Founder / Co-founder',
          svp: 'C-level / SVP',
          head: 'VP / Director / Head',
          manager: 'Manager',
          individual_contributor: 'Individual Contributor',
          others: 'Others'
        },

        department: {
          engineering: 'Engineering',
          product: 'Product',
          business: 'Business',
          finance: 'Finance',
          strategy: 'Strategy',
          others: 'Others'
        },
      },
      showMore: false,
    }

    $scope.goToStep = function (step, subStep) {
      $scope.signup.currentStep = step;
      $scope.signup.currentSubStep = subStep;
    }

    $scope.createAccount = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return true;
      }

      if (window.location.hostname !== 'dashboard.razorpay.com' 
          && window.location.hostname !== 'betadashboard.razorpay.com' 
          && !$scope.signup.data.captcha) {
        $scope.signup.data.captcha = 'Faked';
      }
      // todo call signup api here
    }

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

    // todo check route and go to apt layout

  }
]);
