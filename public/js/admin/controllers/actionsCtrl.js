// Admin Actions Controller
app.controller('ActionsCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'transformRequestAsFormPost',
  '$modal',
  function ($scope, $http, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.response = null;
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
      iin.emi = iin.emi ? 1 : 0;

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
    $scope.addEMI = function (emi) {
      var request = $http({
        method: 'post',
        url: '/admin/emi',
        transformRequest: transformRequestAsFormPost,
        data: emi
      });
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'EMI Plan added successfully', true);
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
      console.log(data);
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
          $scope.alerts.addAlert('danger', data.errors);
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
    $scope.toJson = function (data) {
      return angular.toJson(data, 4);
    };
    $scope.apiRequest = function (data, url) {

      if (typeof url === "undefined") {
        var url = '/api/' + data.url;
      }

      data = data.form;

      var req = $http.post(url, data, {
        transformRequest: angular.identity,
        // We set this to undefined to let angular auto-detect this
        // And convert to a multipart file upload
        headers: {
          'Content-Type': undefined
        }
      });

      req.success(function (data) {
        if (data.success) {
          $scope.alerts.resetAlerts();
          $scope.response = data;
          $scope.alerts.addAlert('success', 'API Request successful', true);
        } else {
          $scope.alerts.resetAlerts();
          $scope.response = null;
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.resetAlerts();
        $scope.alerts.addAlert('danger', 'The API request failed on the dashboard side.', true);
      });

    };

    $scope.reconUpload = function (data) {

      var url = data.url;
      data = data.form;

      var req = $http.post(url, data, {
        transformRequest: angular.identity,

        // We set this to undefined to let angular auto-detect this
        // And convert to a multipart file upload
        headers: {
          'Content-Type': undefined
        }
      });

      req.success(function (data) {
        if (data.success) {
          $scope.alerts.resetAlerts();
          $scope.response = data;
          $scope.alerts.addAlert('success', 'Reconciliation Response successful', true);
        } else {
          $scope.alerts.resetAlerts();
          $scope.response = null;
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.resetAlerts();
        $scope.alerts.addAlert('danger', 'The API request failed on the dashboard side.', true);
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
    $scope.authorizeFailedPayment = function (payment_id, mode) {
      var request = $http({
        method: 'post',
        url: '/admin/' + mode + '/payments/' + payment_id + '/authorize_failed'
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

    $scope.openSetlUpload = function () {
      var modalInstance = $modal.open({
        templateUrl: 'uploadSetlRecon.html',
        controller: 'ApiRequestCtrl'
      });
      modalInstance.result.then(function (data) {
        $scope.apiRequest(data, '/settlements/reconcile')
      }, $.noop);
    };

    $scope.openReconUpload = function () {
      var modalInstance = $modal.open({
        templateUrl: 'uploadRecon.html',
        controller: 'ReconUploadCtrl'
      });
      modalInstance.result.then($scope.reconUpload, $.noop);
    };

    $scope.openApiRequest = function () {
      var modalInstance = $modal.open({
        templateUrl: 'makeApiCallModalContent.html',
        controller: 'ApiRequestCtrl'
      });
      modalInstance.result.then(function (data) {
        $scope.apiRequest(data);
      }, $.noop);
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
    $scope.openAddEMI = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addEMIModalContent.html',
        controller: 'addEMIModalCtrl'
      });
      modalInstance.result.then($scope.addEMI, $.noop);
    };
    $scope.openVerifyPayment = function () {
      var modalInstance = $modal.open({
        templateUrl: 'verifyPaymentModalContent.html',
        controller: 'verifyPaymentModalCtrl'
      });
      modalInstance.result.then($scope.verifyPayment, $.noop);
    };

    $scope.archiveMerchant = function (id) {
      var request = $http.get('/admin/merchant/' + id + '/archive');
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Merchant archived successfully', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.confirmMerchant = function (id) {
      var request = $http.put('/admin/merchants/' + id + '/confirmed');
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Merchant confirmed successfully', true);
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.openArchiveMerchant = function () {
      var modalInstance = $modal.open({
        templateUrl: 'archiveMerchantModal.html',
        controller: 'archiveMerchantModalCtrl'
      });
      modalInstance.result.then($scope.archiveMerchant, $.noop);
    };

    $scope.openConfirmMerchant = function () {
      var modalInstance = $modal.open({
        templateUrl: 'confirmMerchantModal.html',
        controller: 'archiveMerchantModalCtrl'
      });
      modalInstance.result.then($scope.confirmMerchant, $.noop);
    };
    $scope.openAuthorizeFailedPayment = function () {
      var modalInstance = $modal.open({
        templateUrl: 'authorizeFailedPaymentModalContent.html',
        controller: 'authorizeFailedPaymentModalCtrl'
      });
      modalInstance.result.then(function (data) {
        $scope.authorizeFailedPayment(data.id, data.mode);
      }, $.noop);
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
        console.debug(data);
        if (data.lists) {
          // Send live email newsletter
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
]).controller('addEMIModalCtrl', [
  '$scope',
  '$modalInstance',
  '$http',
  function ($scope, $modalInstance, $http) {
    $scope.emi = {
      bank: 'HDFC',
      duration: 3,
      methods: ''
    };
    $scope.ok = function (emi) {
      $modalInstance.close(emi);
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
]).controller('archiveMerchantModalCtrl', [
  '$scope',
  '$modalInstance',
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
    $scope.mode = 'live';
    $scope.ok = function (id, mode) {
      $modalInstance.close({
        id: id,
        mode: mode
      });
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
    $scope.template = 'newsletter';
    $scope.lists = { all: true };
    admin.identity().then(function (admin) {
      $scope.adminEmail = admin.email;
    });
    $scope.test = function (subject, msg, template) {
      $modalInstance.close({
        subject: subject,
        msg: msg,
        template: template
      });
    };
    $scope.ok = function (lists, subject, msg, template) {
      $modalInstance.close({
        lists: Object.keys(lists).join(),
        subject: subject,
        msg: msg,
        template: template
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
]).controller('ApiRequestCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {

    $scope.url = '';
    $scope.data = {
      mode: 'test',
      method: 'GET',
      auth: 'admin',
      merchant_id: '',
      content_type: 'application/x-www-form-urlencoded',
      body: '',
      file: null,
      file_name: null
    };

    $scope.ok = function (url, data) {
      var fd = new FormData();

      // If we are sending a file
      // We don't add the content type header
      // because this needs to be auto-generated
      if (data.file) {
        delete data.content_type;
      };

      // We push all data fields
      // into the formdata object
      for (var field in data) {
        var value = data[field];
        fd.append(field, value);
      }



      // We pass an instance of FormData
      // And the URL separately because extracting and deleting
      // items from formdata is not supported most browsers
      // including chrome
      $modalInstance.close({
        form: fd,
        url: url
      });
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]).controller('ReconUploadCtrl', [
  '$scope',
  '$modalInstance',
  function ($scope, $modalInstance) {

    $scope.counter = Array;

    $scope.files = [];

    $scope.ok = function (mode, input) {

      var url = '/admin/' + mode + '/reconciliate';

      var data ={
        manual: 1,
        'attachment-count': input.files.length,
        gateway: input.gateway
      };

      for (var i in input.files)
      {
        if (input.files[i]) {
          // attachment-X starts from 1
          // following mailgun conventions
          var num = parseInt(i) + 1;
          data['attachment-' + (num)] = input.files[i];
        }
      }

      var fd = new FormData();

      for (var field in data) {
        var value = data[field];
        fd.append(field, value);
      }

      $modalInstance.close({
        form: fd,
        url: url
      });
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
