// Admin Actions Controller
app.controller('ActionsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.initiateSetl = function (channel) {
      var request = $http({
        method: 'post',
        url: '/admin/settlement/initiate/' + channel
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Settlement initiated successfully. Response: ' + JSON.stringify(data.data), true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.addIIN = function (iin) {
      var request = $http({
        method: 'post',
        url: '/admin/iin/add',
        transformRequest: transformRequestAsFormPost,
        data: iin
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'IIN added successfully. Response: ' + JSON.stringify(data.data), true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.verifyAllPayments = function () {
      var request = $http({
        method: 'POST',
        url: '/admin/payments/verify'
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Payments Verified successfully ' + JSON.stringify(data.data), true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.generateNetBankingRefunds = function (params) {
      var url = 'admin/' + params.mode + '/refunds/netbanking';
      var request = $http({
        method: 'POST',
        url: url,
        data: params
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', params.bank.toUpperCase() + ' Refunds Excel Generated (Count = ' + data.data.count + ')', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.sendTestEmail = function (data) {
      var request = $http({
        method: 'post',
        url: '/admin/newsletter/test',
        data: data,
        transformRequest: transformRequestAsFormPost
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Test mail sent successfully to ' + data.data.email, true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.sendNewsletter = function (data) {
      var request = $http({
        method: 'post',
        url: '/admin/newsletter/mail',
        data: data,
        transformRequest: transformRequestAsFormPost
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Test mail sent successfully to ' + data.data.count + ' addresses (' + data.data.email + ')', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.verifyPayment = function (payment_id) {
      var request = $http({
        method: 'get',
        url: '/admin/payment/' + payment_id + '/verify'
      });
      request.success(function (data) {
        if (data.success) {
          var payment = JSON.stringify(data.data.payment);
          $scope.alerts.addAlert('success', 'Payment Verified successfully: ' + payment, true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.authorizeFailedPayment = function (payment_id) {
      var request = $http({
        method: 'post',
        url: '/admin/live/payments/' + payment_id + '/authorize_failed'
      });
      request.success(function (data) {
        if (data.success) {
          var payment = JSON.stringify(data.data.payment);
          $scope.alerts.addAlert('success', 'Payment Authorized Successfully: ' + payment, true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.openInitiateSetl = function () {
      var modalInstance = $modal.open({
        templateUrl: 'initiateSetlModalContent.html',
        controller: 'initiateSetlModalCtrl'
      });
      modalInstance.result.then($scope.initiateSetl, $.noop);
    };
    $scope.openGenerateRefund = function () {
      var modalInstance = $modal.open({
        templateUrl: 'refundGenerateModalContent.html',
        controller: 'generateRefundModalCtrl'
      });
      modalInstance.result.then($scope.generateNetBankingRefunds, $.noop);
    };
    $scope.openAddIIN = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addIINModalContent.html',
        controller: 'addIINModalCtrl'
      });
      modalInstance.result.then($scope.addIIN, $.noop);
    };
    $scope.openVerifyPayment = function () {
      var modalInstance = $modal.open({
        templateUrl: 'verifyPaymentModalContent.html',
        controller: 'verifyPaymentModalCtrl'
      });
      modalInstance.result.then($scope.verifyPayment, $.noop);
    };
    $scope.openAuthorizeFailedPayment = function () {
      var modalInstance = $modal.open({
        templateUrl: 'authorizeFailedPaymentModalContent.html',
        controller: 'authorizeFailedPaymentModalCtrl'
      });
      modalInstance.result.then($scope.authorizeFailedPayment, $.noop);
    };
    $scope.triggerError = function () {
      var request = $http({
        method: 'post',
        url: '/admin/trigger/error'
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Error triggerred successfully', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
    $scope.openEditNewsletter = function () {
      var modalInstance = $modal.open({
        templateUrl: 'sendNewsletter.html',
        controller: 'sendNewsletterCtrl',
        size: 'lg'
      });
      modalInstance.result.then(function (data) {
        if (data.lists) {
          // Confirm email bhena hain
          $scope.sendNewsletter(data);
        } else {
          $scope.sendTestEmail(data);
        }
      }, $.noop);
    };
    $scope.downloadBeneficiaryFile = function () {
      $scope.date = moment().format('yyyy-MM-dd');
      var modalInstance = $modal.open({
        templateUrl: 'downloadBeneficiaryFile.html',
        controller: 'downloadBeneficiaryFileCtrl'
      });
      modalInstance.result.then(function (date) {
        if (date) {
          window.open('/admin/beneficiary/dl');
        } else {
          window.open('/admin/beneficiary/dl?date=' + date);
        }
      }, $.noop);
    };
    $scope.generateBeneficiaryFile = function () {
      var request = $http({
        method: 'post',
        url: '/admin/beneficiary'
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Beneficary file generated successfully', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
  }
]).controller('initiateSetlModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.ok = function (channel) {
      $modalInstance.close(channel);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('addIINModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.ok = function (iin) {
      $modalInstance.close(iin);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('verifyPaymentModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.ok = function (id) {
      $modalInstance.close(id);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('authorizeFailedPaymentModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.ok = function (id) {
      $modalInstance.close(id);
    };
  }
]).controller('sendNewsletterCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  'admin',
  function ($scope, $modalInstance, $http, admin) {
    $scope.mailingLists = {
      all: 'All merchants',
      live: 'Live Merchants',
      recent: 'Recently signed up merchants',
      paytm: 'Paytm enabled merchants',
      mobikwik: 'Mobikwik enabled merchants'
    };
    $scope.message = 'Hi %recipient_name%,\n\nThanks for doing business with Razorpay.\n\n# section heading\n\ncontent\ncontent\n\nmore content\n\n---\n\nTeam Razorpay';
    $scope.lists = { all: true };
    admin.identity().then(function (admin) {
      $scope.adminEmail = admin.email;
    });
    $scope.test = function (subj_1, subj_2, msg) {
      $modalInstance.close({
        subj_1: subj_1,
        subj_2: subj_2,
        msg: msg
      });
    };
    $scope.ok = function (lists, subj_1, subj_2, msg) {
      $modalInstance.close({
        lists: Object.keys(lists).join(),
        subj_1: subj_1,
        subj_2: subj_2,
        msg: msg
      });
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('downloadBeneficiaryFileCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.ok = function (date) {
      $modalInstance.close(date);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('generateRefundModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.bank = 'HDFC';
    $scope.mode = 'live';
    $scope.date = moment().format('YYYY-MM-DD');
    $scope.ok = function (date, from, to, bank, mode) {
      var data = {
        bank: bank,
        mode: mode
      };
      if (from && to) {
        data.from = from;
        data.to = to;
      } else {
        data.date = date;
      }
      $modalInstance.close(data);
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);
