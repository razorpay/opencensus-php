"use strict";
//Registration Controller
app.controller('RegisterCtrl', [
  '$scope',
  '$http',
  '$state',
  'alertsFactory',
  'user',
  'transformRequestAsFormPost',
  '$analytics',
  '$location',
  '$window',
  '$cookies',
  function ($scope, $http, $state, alertsFactory, user,
    transformRequestAsFormPost, $analytics, $location, $window, $cookies) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.data = {};
    $scope.more = false;

    $scope.toArray = function(obj){
        if (!obj) {
            return [];
        }
        return Object.keys(obj);
    }

    $scope.business_type = {
      private_ltd: 'Private Limited',
      propreitorship: 'Propreitorship',
      partnership: 'Partnership',
      llp: 'LLP',
      edu: 'Educational Institutes',
      trust_society: 'Trust / Society',
      individual: 'Individual',
      public_ltd: 'Public Limited',
      ngo: 'NGO'
    }

    $scope.monthly_transaction = {
      yet_to_start: 'Haven’t started processing yet',
      below_1lac: 'Less than 1 Lac',
      below_20lac: '1 Lac to 20 Lacs',
      below_1crore: '20 Lacs to 1 Crore',
      above_1crore: 'More than 1 Crore'
    }

    $scope.role = {
      founder: 'Founder / Co-founder',
      svp: 'C-level / SVP',
      head: 'VP / Director / Head',
      manager: 'Manager',
      individual_contributor: 'Individual Contributor',
      others: 'Others'
    }

    $scope.department = {
      engineering: 'Engineering',
      product: 'Product',
      business: 'Business',
      finance: 'Finance',
      strategy: 'Strategy',
      others: 'Others'
    }

    var validations = [
      function () {
        var email = $scope.data.email;
        if (typeof email === 'string' && /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(email)) {
          return true;
        }

        $scope.alerts.addAlert('danger', 'Please enter a valid email', true);

        return false;
      },
      function () {
        if ($scope.data.business_type) {
          return true;
        }

        return false;
      },
      function () {
        if ($scope.data.monthly_transaction) {
          return true;
        }

        return false;
      },
      function () {
        if ($scope.data.role) {
          return true;
        }

        return false;
      },
      function () {
        if ($scope.data.department) {
          return true;
        }

        return false;
      },
      function () {

      },
      function () {

      }
    ]

    /* ms stands for multistep form */
    $scope.ms = {
      step: 1,
      totalSteps: 6,
      width: 240, // width of one step
      moreState: false,
      gotoNext: function(e) {
        console.log('next');
        e.preventDefault();
        if (!validations[this.step]()) {
          return;
        }

        $scope.alerts.resetAlerts()

        this.step = this.step + 1;
      },
      gotoStep: function(n) {
        if (n < this.totalSteps) {
          this.step = n;
        }
        console.log($scope.data);
      }
    }

    $scope.range = function(n) {
      var input = [];
      for (var i = 0; i < n; i ++) {
          input.push(i);
      }
      return input;
    };

    window.addEventListener('keydown', function (e) {
      if (e.keyCode === 9) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);



    // if ($location.search().email) {

    //   $scope.data.email = $location.search().email;

    //   // XHR to save this email in a generic table
    //   // so that even if the user doesn't signup we can
    //   // re-target him.
    //   var request = $http({
    //     method: 'post',
    //     url: '/user/track_lead',
    //     data: { email: $scope.data.email }
    //   });

    //   request.success(function (data) {
    //     if (data.success) {
    //       // do nothing
    //     }
    //   }).error(function () {
    //     // nothing to do
    //   });
    // }

    // if($location.search().invitation) {
    //   $scope.data.invitation = $location.search().invitation;
    // }
    // // We only track referers if they are registering a business
    // else {
    //   if (typeof $window.google_trackConversion === 'function') {
    //     $window.google_trackConversion({
    //       google_conversion_id : 928471290,
    //       google_conversion_language : "en",
    //       google_conversion_format : "3",
    //       google_conversion_color : "ffffff",
    //       google_conversion_label : "CM9fCLm40GMQ-rHdugM",
    //       google_remarketing_only : false
    //     });
    //   }
    // }

    // //Intialise alerts and scope functions
    // $scope.alerts = alertsFactory.getHandler();
    // $scope.agree = false;

    // // Referrer is set only if present
    // if ($location.search().ref) {
    //   $scope.data.ref = $location.search().ref;
    // }

    // if (typeof $state.current.data.ref !== 'undefined') {
    //   $scope.data.ref = $state.current.data.ref;
    // }

    // $scope.submit = function ($valid) {
    //   if (!$valid) {
    //     $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
    //     return true;
    //   }
    //   if (!$scope.agree) {
    //     $scope.alerts.addAlert('danger', 'You must agree to the terms & conditions for using our service', true);
    //     return true;
    //   }
    //   if (window.location.hostname !== 'dashboard.razorpay.com' && window.location.hostname !== 'betadashboard.razorpay.com' && !$scope.data.captcha) {
    //     $scope.data.captcha = 'Faked';
    //   }
    //   $scope.alerts.resetAlerts();
    //   var request = $http({
    //     method: 'post',
    //     url: '/user/register',
    //     transformRequest: transformRequestAsFormPost,
    //     data: $scope.data
    //   });
    //   request.success(function (data) {
    //     if (data.success) {
    //       $cookies.show_rzp_welcome_guide = true;
    //       $analytics.eventTrack('signUp', {
    //         id: data.data.id,
    //         name: data.data.name,
    //         email: data.data.email
    //       });
    //       if(data.data.login) {
    //         user.identity(true);
    //         $state.go('app.dashboard');
    //       }
    //       else {
    //         $scope.alerts.addAlert('success', 'Registration Successful. Please check your inbox for confirmation email from Razorpay.', true);
    //       }
    //     } else {
    //       $scope.alerts.resetAlerts();
    //       angular.forEach(data.errors, function (error) {
    //         $scope.alerts.addAlert('danger', error);
    //       });
    //     }
    //   }).error(function () {
    //     $scope.alerts.addAlert('danger', null, true);
    //   });
    // };
  }
]);
