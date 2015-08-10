// Merchant Details Controller
app.controller('MerchantDetailCtrl', ['$scope', '$http', '$stateParams', 'alertsFactory', 'transformRequestAsFormPost', '$modal',
  function($scope, $http, $stateParams, alertsFactory, transformRequestAsFormPost, $modal) {
    $scope.alerts = alertsFactory.getHandler();

    $scope.merchant = {
      id: $stateParams.id,
      balance: 0
    };

    generateMerchant();

    $scope.lockForm = function(){
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/lock");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Form locked successfully', true);
          $scope.merchant.details.locked = 1;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.captureScreenshot = function(){
      var request = $http.put("/admin/merchant/"+$scope.merchant.id+"/screenshot");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Website screenshots capture started. Wait for notification on Slack', true);
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.unlockForm = function(){
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/unlock");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Form unlocked successfully', true);
          $scope.merchant.details.locked = 0;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.activateMerchant = function(){

      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/activate");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant Activated successfully', true);
          $scope.merchant.details.activated = 1;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.holdMerchantFunds = function(){
      var merchantEdit = {
        hold_funds: 1
      }

      $scope.editMerchant(merchantEdit);
    };

    $scope.releaseMerchantFunds = function(){
      var merchantEdit = {
        hold_funds: 0
      }

      $scope.editMerchant(merchantEdit);
    };

    $scope.enableLive = function() {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/live/enable");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Live transactions for merchant enabled successfully', true);
          $scope.merchant.details.live = 1;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.disableLive = function() {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/live/disable");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Live transactions for merchant disabled successfully', true);
          $scope.merchant.details.live = 0;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.enableMethod = function(method) {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/methods/"+method+"/enable");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', method + ' transactions for merchant enabled successfully', true);
          $scope.merchant.details.methods[method] = true;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.disableMethod = function(method) {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/methods/"+method+"/disable");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', method + ' transactions for merchant disabled successfully', true);
          $scope.merchant.details.methods[method] = false;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.setReceiptEmail = function(value) {
      var editMerchant = {
        'receipt_email_enabled': value
      };

      $scope.editMerchant(editMerchant);
    };

    $scope.assignPricing = function(plan_id){
      var data = {
        pricing_plan_id: plan_id
      };


      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/pricing",
                    transformRequest: transformRequestAsFormPost,
                    data: data
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Plan Assigned successfully', true);
          $scope.merchant.pricing_plan = data.data;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.assignTerminal = function(terminal){
      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/terminal",
                    transformRequest: transformRequestAsFormPost,
                    data: terminal
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Terminal Assigned successfully', true);
          terminal.id = data.data.id;
          terminal.created_at = data.data.created_at;
          $scope.merchant.terminals.items.push(terminal);
          $scope.merchant.terminals.count = $scope.merchant.terminals.count + 1;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.assignBanks = function(bankdata){
      var data = {banks: []};

      angular.forEach(bankdata, function(i,e) {
        if(i==true){
          data.banks.push(e);
        }
      });

      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/banks",
                    data: angular.toJson(data)
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Banks Assigned successfully', true);
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.addAdjustment = function(adjustment){
      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/addadjustment",
                    data: angular.toJson(adjustment)
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Adjustment added successfully', true);
          fetchBalance();
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.editMerchant = function(merchant){
      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/edit",
                    data: angular.toJson(merchant)
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant edited successfully', true);
          generateMerchant();
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.editComment = function(new_comment){
      var data = {comment: new_comment};

      var request = $http({
                    method: "post",
                    url: "/admin/merchant/"+$scope.merchant.id+"/comment/edit",
                    data: angular.toJson(data)
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant comment edited successfully', true);
          $scope.merchant.details.merchant_details.comment = data.data;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.archiveMerchant = function(){
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/archive");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant archived successfully', true);
          $scope.merchant.details.archived_at = Date.now()/1000;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.unarchiveMerchant = function(){
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/unarchive");

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts.addAlert('success', 'Merchant unarchived successfully', true);
          $scope.merchant.details.archived_at = null;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.openAssignPricing = function () {
      var currentPlan = $scope.merchant.pricing_plan.id || "";

      var modalInstance = $modal.open({
        templateUrl: 'assignPricingModalContent.html',
        controller: 'assignPricingModalCtrl',
        resolve: {
          current: function() {
            return currentPlan;
          }
        }
      });

      modalInstance.result.then(
        function (plan_id) {
          $scope.assignPricing(plan_id);
        },
        function () {
          ;
        });
    };

    $scope.openAssignTerminal = function () {
      var modalInstance = $modal.open({
        templateUrl: 'assignTerminalModalContent.html',
        controller: 'assignTerminalModalCtrl',
      });

      modalInstance.result.then(
        function (terminal) {
          $scope.assignTerminal(terminal);
        },
        function () {
          ;
        });
    };

    $scope.openEditMerchant = function () {
      var modalInstance = $modal.open({
        templateUrl: 'editMerchantModalContent.html',
        controller: 'editMerchantModalCtrl',
        resolve: {
          current: function() {
            return $scope.merchant.details;
          }
        }
      });

      modalInstance.result.then(
        function (merchant) {
          $scope.editMerchant(merchant);
        },
        function () {
          ;
        });
    };

    $scope.openUploadScreenshot = function () {
      var currentId = $scope.merchant.id;
      var modalInstance = $modal.open({
        templateUrl: 'uploadScreenshotModalContent.html',
        controller: 'uploadScreenshotModalCtrl',
        resolve: {
          current: function() {
            return currentId;
          }
        }
      });
    };

    $scope.openEditComment = function () {
      var modalInstance = $modal.open({
        templateUrl: 'editCommentModalContent.html',
        controller: 'editCommentModalCtrl',
        resolve: {
          current: function() {
            return $scope.merchant.details.merchant_details.comment;
          }
        }
      });

      modalInstance.result.then(
        function (merchant) {
          $scope.editComment(merchant);
        },
        function () {
          ;
        });
    };

    $scope.openAssignBanks = function () {
      var currentId = $scope.merchant.id;

      var modalInstance = $modal.open({
        templateUrl: 'assignBanksModalContent.html',
        controller: 'assignBanksModalCtrl',
        resolve: {
          current: function() {
            return currentId;
          }
        }
      });

      modalInstance.result.then(
        function (bankdata) {
          $scope.assignBanks(bankdata);
        },
        function () {
          ;
        });
    };

    $scope.openAddAdjustment = function () {
      var modalInstance = $modal.open({
        templateUrl: 'addAdjustmentModalContent.html',
        controller: 'addAdjustmentModalCtrl'
      });

      modalInstance.result.then(
        function (adjustment) {
          $scope.addAdjustment(adjustment);
        },
        function () {
          ;
        }
      );
    };

    $scope.openAutofillForms = function() {
      var merchant = $scope.merchant;

      var modalInstance = $modal.open({
        templateUrl: 'openAutofillForms.html',
        controller: 'openAutofillForms',
        windowClass: 'modal-print',
        resolve: {
          current: function(){
            return merchant.details;
          }
        }
      });
    }


    function generateMerchant() {
      var request = $http.get("/admin/merchant/"+$scope.merchant.id);

      request
      .success(function(data){
        $scope.alerts.resetAlerts(true);

        if(data.success) {
          $scope.merchant = data.data;
          $scope.merchant.id = data.data.details.id;
          $scope.merchant.details.activation_progress = parseInt(($scope.merchant.details.steps_finished.length * 100)/ 5);

          if ($scope.merchant.details.activated == 1)
            fetchBalance();
          else
            $scope.merchant.balance = 0;
        }
        else {
          $scope.alerts.resetAlerts(true);
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.resetAlerts(true);
        $scope.alerts.addAlert('danger', null);
      });
    }

    function fetchBalance(){
      var request = $http.get("/admin/merchant/"+$scope.merchant.id+"/balance");

      request
      .success(function(data){
        if(data.success) {
          $scope.merchant.balance = data.data.balance;
        }
        else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.alerts.addAlert('danger', null, true);
      });
    }

}])
.controller('assignPricingModalCtrl', ['$scope', '$modalInstance', '$http', 'current',
  function ($scope, $modalInstance, $http, current) {
      $scope.loading = true;

      $scope.pricing_plans = [];
      $scope.pricing_plan_id = current;

      var request = $http.get("/admin/pricing/list");
      request
        .success(function(data){
          if(data.success) {
            angular.forEach(data.data, function(value, key){
              $scope.pricing_plans.push({'id': value.id, 'name':value.name});
            });

            $scope.loading = false;
          }
        });

      $scope.ok = function (pricing_plan_id) {
        $modalInstance.close(pricing_plan_id);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('assignTerminalModalCtrl', ['$scope', '$modalInstance',
  function ($scope, $modalInstance) {
      $scope.ok = function (terminal) {
        $modalInstance.close(terminal);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('assignBanksModalCtrl', ['$scope', '$modalInstance', '$http', 'current',
  function ($scope, $modalInstance, $http, current) {
      $scope.loading = true;

      $scope.banks = [];
      $scope.bankdata = {};
      $scope.merchant_id = current;

      $scope.selectAllChange = function(value) {
        angular.forEach($scope.bankdata, function(i,e){
          $scope.bankdata[e] = value;
        });
      };

      var request = $http.get("/admin/merchant/" + current + "/banks");
      request
        .success(function(data){
          if(data.success) {
            $scope.banks = data.data;
            angular.forEach($scope.banks.enabled, function(key, value){
              $scope.bankdata[value] = true;
            });
            angular.forEach($scope.banks.disabled, function(key, value){
              $scope.bankdata[value] = false;
            });
            $scope.loading = false;
          }
        });

      $scope.ok = function (bankdata) {
        $modalInstance.close(bankdata);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('addAdjustmentModalCtrl', ['$scope', '$modalInstance',
  function ($scope, $modalInstance) {
      $scope.ok = function (adjustment) {
        $modalInstance.close(adjustment);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('editMerchantModalCtrl', ['$scope', '$modalInstance', 'current',
  function ($scope, $modalInstance, current) {

      // If these fields were not present in the API db, we copy them to the form from dashboard database

      if(!current.international) current.international = current.merchant_details.business_international;

      if(!current.website) current.website = current.merchant_details.business_website;

      if(!current.billing_label) current.billing_label = current.merchant_details.business_dba;

      if(!current.transaction_report_email) current.transaction_report_email = current.merchant_details.transaction_report_email;

      $scope.current = current;

      $scope.ok = function (merchant) {
        $modalInstance.close(merchant);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('editCommentModalCtrl', ['$scope', '$modalInstance', 'current',
  function ($scope, $modalInstance, current) {
      $scope.current = current;

      $scope.ok = function (merchant) {
        $modalInstance.close(merchant);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('uploadScreenshotModalCtrl', ['$scope', '$modalInstance', '$upload', 'current', 'alertsFactory',
  function ($scope, $modalInstance, $upload, current, alertsFactory) {
      $scope.files = {};
      $scope.merchantId = current;

      $scope.alerts = alertsFactory.getHandler();

      $scope.onFileSelect = function ($files, fieldname){
        var file = $files[0];
        if(file.type !== "image/jpeg") {
          $scope.alerts.addAlert('danger', 'Invalid filetype. Only jpg files are allowed.', true);
          return;
        }

        if(file.size > 2000000){
          $scope.alerts.addAlert('danger', 'Max file size 2 MB. Convert the file to an image before uploading if necessary.', true);
          return;
        }

        $scope.alerts.addAlert('info', 'Uploading...', true);

        var request = $upload.upload({
          url: '/admin/merchant/'+$scope.merchantId+'/screenshot',
          method: 'post',
          file: file,
          alias: fieldname,
          name: fieldname,
          fileFormDataName: fieldname,
          formDataAppender: function(fd, key, val) {
            if (angular.isArray(val)) {
                angular.forEach(val, function(v) {
                    fd.append(key, v);
                });
            } else {
                fd.append(key, val);
            }
          }
        });

        request
        .success(function(data, status, headers, config) {
          if(data.success){
            $scope.alerts.addAlert('success', 'File Uploaded Successfully', true);
          }
          else{
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value, key){
              $scope.alerts.addAlert('danger', value);
            });
          }
        })
        .error(function(){
          $scope.alerts.addAlert('danger', 'File upload failed.', true);
        })

      };

      $scope.ok = function (files) {
        $modalInstance.close(files);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
}])
.controller('openAutofillForms', ['$scope', '$modalInstance', 'current',
  function ($scope, $modalInstance, current) {
      var merchant_details = current && current.merchant_details || {};
      var html = "";
      var bankDocument = "";

      $scope.ok = function () {

        if (bankDocument === 'hdfc-excel') {
            var id = merchant_details.merchant_id;
            window.location = '/admin/merchant/'+id+'/hdfc_excel';
        }

        if (html) {
          var w = window.open();
          w.document.body.innerHTML = html;
        }
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };

      $scope.select = function(doc){
        $('.modal-ok').attr('disabled', 'disabled');

        bankDocument = doc;

        if (bankDocument === 'hdfc-excel') {
            $('.modal-ok').removeAttr('disabled');
            return;
        }

        $.ajax({
          url: '/admin-forms/' + bankDocument + '.html',
          complete: function(){$('.modal-ok').removeAttr('disabled')},
          success: function(resp){
            doT.templateSettings.strip = false;
            var template = doT.template(resp);
            var reg_addr = merchant_details.business_registered_address;
            if(reg_addr)
              reg_addr += ', ';
            if(merchant_details.business_registered_city){
              reg_addr += merchant_details.business_registered_city;
              if(merchant_details.business_registered_pin)
                reg_addr += '-' + merchant_details.business_registered_pin;
              reg_addr += ', ';
            }
            reg_addr += merchant_details.business_registered_state;

            var ops_addr = merchant_details.business_operation_address;
            if(ops_addr)
              ops_addr += ', ';
            if(merchant_details.business_operation_city){
              ops_addr += merchant_details.business_operation_city;
              if(merchant_details.business_operation_pin)
                ops_addr += '-' + merchant_details.business_operation_pin;
              ops_addr += ', ';
            }
            ops_addr += merchant_details.business_operation_state;

            var now = new Date();
            var nowdate = ("0"+now.getDate()).slice(-2);
            var nowmonth = ("0"+(1+now.getMonth())).slice(-2);
            var nowyear = now.getYear()+1900;
            html = template({
              billing_label: current.billing_label || '',
              date: nowdate + '/' + nowmonth + '/' + nowyear,
              reqdate: nowdate + nowmonth + nowyear,
              reqby: "Harshil Mathur",
              reqsign: "",
              contract_merchant: "",
              contract_corporate: "",
              contract_business: "",
              contract_software: "",
              contract_govt: "",
              contract_other: "Y",
              contract_specify: merchant_details.bussiness_model || "",
              mercreg_company: merchant_details.business_name || "",
              mercreg_contact: merchant_details.contact_name || "",
              mercreg_tel_business: merchant_details.contact_mobile || "",
              mercreg_tel_after: merchant_details.contact_mobile || "",
              mercreg_fax: "",
              mercreg_email: merchant_details.contact_email || "",
              mercreg_addr: reg_addr || "",
              mercreg_country: "India",
              mercreg_tz: "GMT + 5:30 (IST)",
              mercop_company: merchant_details.business_name || "",
              mercop_contact: merchant_details.contact_name || "",
              mercop_tel_business: merchant_details.contact_mobile || "",
              mercop_tel_after: merchant_details.contact_mobile || "",
              mercop_fax: "",
              mercop_email: merchant_details.contact_email || "",
              mercop_addr: ops_addr || "",
              cpv_head: "",
              cpv_op: "",
              merctech_contact: "Razorpay Software Private Limited",
              merctech_pos: "Director",
              merctech_tel_business: "+91-8003393912",
              merctech_tel_after: "+91-8003393912",
              merctech_fax: "",
              merctech_email: "harshil@razorpay.com",
              merctech_addr: "35, Vishnupuri, Opp. Malviya Nagar P.O., Jagatpura Road, Jaipur - 302017, Rajasthan",
              merctech_web_addr: current.website || "",
              merctech_return_url: "https://api.razorpay.com",
              mercsetup_auth: "Y",
              mercsetup_purc: "",
              mercsetup_catcode: current.category || "",
              mercsetup_3: "",
              mercsetup_6: "",
              mercsetup_9: "",
              mercsetup_12: "",
              mercsetup_master: "Y",
              mercsetup_visa: "Y",
              mercsetup_maestro: "Y",
              mercsetup_dmid: current.international && "Y" || "",
              mercsetup_smid: !current.international && "Y" || "",
              techpro_company: "",
              techpro_contact: "",
              techpro_pos: "",
              techpro_tel_business: "",
              techpro_tel_after: "",
              techpro_fax: "",
              techpro_email: "",
              techpro_addr: "",
              paycli_merc: "",
              paycli_third: "Y",
              paycli_hosting: "Amazon Web Services",
              paycli_tel: "",
              paycli_win: "",
              paycli_winver: "",
              paycli_unix: "",
              paycli_unixver: "",
              paycli_linux: "Y",
              paycli_linuxver: "14.04",
              paycli_other: "",
              paycli_specify: "",
              payapp_custbool: "",
              payapp_cust: "",
              payapp_thirdbool: "",
              payapp_third: "",
              payapp_otherbool: "Y",
              payapp_specify: "Self developed by Razorpay",
              payapp_langasp: "",
              payapp_langaspx: "",
              payapp_langjsock: "",
              payapp_langjava: "",
              payapp_langperl: "",
              payapp_langoth: "",
              payapp_langspecify: "",
              payapp_sslbool: "Y",
              payapp_4card: "",
              payapp_6card: "",
              payapp_dndcard: "",
              payapp_secyes: "Y",
              payapp_secno: "",
              payapp_uid: "Razorpay",
              payapp_vbvyes: "Y",
              payapp_vbvno: "",
              payapp_mscyes: "Y",
              payapp_mscno: ""
            });
          }
        })
      };
}])

