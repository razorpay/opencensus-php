//Activation Form Controller
app.controller('ActivationCtrl', ['$scope', '$http', 'alertsFactory', 'transformRequestAsFormPost', '$upload', 'user',
  function($scope, $http, alertsFactory, transformRequestAsFormPost, $upload, user){
    $scope.steps={
      percent:0,
      step1:true
    };

    $scope.check = {};

    $scope.data = {
      1: {},
      2: {},
      3: {},
      4: {},
      5: {},
      6: {}
    };

    $scope.formAlerts = alertsFactory.getHandler();

    $scope.alerts = {
      1: alertsFactory.getHandler(),
      2: alertsFactory.getHandler(),
      3: alertsFactory.getHandler(),
      4: alertsFactory.getHandler(),
      5: alertsFactory.getHandler(),
      6: alertsFactory.getHandler()
    };

    $scope.fileAlerts = {
      bussiness_proof: alertsFactory.getHandler(),
      bussiness_pan_proof: alertsFactory.getHandler(),
      promoter_pan_proof: alertsFactory.getHandler(),
      address_proof: alertsFactory.getHandler()
    };


    $scope.submit = function(step) {
      if(step !== 6){
        saveStep(step);
      }
      else {
        submitForm(step);
      }
    };

    $scope.onFileSelect = saveFile;

    $scope.genOperation = function(flag) {
      if(flag) {
        $scope.data[2].bussiness_operation_address = $scope.data[2].bussiness_registered_address;
        $scope.data[2].bussiness_operation_state = $scope.data[2].bussiness_registered_state;
        $scope.data[2].bussiness_operation_city = $scope.data[2].bussiness_registered_city;
        $scope.data[2].bussiness_operation_pin = $scope.data[2].bussiness_registered_pin;
      }
    };

    getData();
    
    function getData() {
      var request = $http.get('/activation/details');

      request.success(function(data){
        var steps_finished = JSON.parse(data.data.steps_finished);

        angular.forEach(steps_finished, function(value, key) {
          $scope.check[value] = true;
        });

        angular.forEach(data.data.data, function(value, key){
          $scope.data[key] = value;       
        });

        angular.forEach(data.data.files, function(value, key){
          $scope.fileAlerts[key].addAlert('success', 'File already uploaded');
        });

        if(parseInt(data.data.submitted)) {
          user.identity().then(function(data){
            if(parseInt(data.data.activated)) {
              $scope.formAlerts.addAlert('info', 'User is already live');
            }
            else 
              $scope.formAlerts.addAlert('info', 'Form has been submitted for activation and is pending admin response');
          });
        }

        if(parseInt(data.data.locked)) {
          $scope.locked = true;
          $scope.formAlerts.addAlert('warning', 'Form has been locked by admin, changes are not allowed.');
        }
      });
    };

    function saveStep(step){
      var data = $scope.data[step];

      var request = $http({
                    method: "post",
                    url: "/activation/save/step/" + step,
                    transformRequest: transformRequestAsFormPost,
                    data: data
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts[step].addAlert('success', 'Step Saved Successfully', true);
          $scope.check[step] = true;
        }
        else {
          $scope.alerts[step].resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts[step].addAlert('danger', value);
          });
          $scope.check[step] = false;
        }
      })
      .error(function(){
        $scope.alerts[step].addAlert('danger', 'An error occured. Please refresh and retry.', true);
        $scope.check[step] = false;
      })
    };

    function saveFile($files, fieldname) {
      var file = $files[0];
      
      $scope.locked = true;

      $scope.fileAlerts[fieldname].addAlert('info', 'Uploading...', true);

      var request = $upload.upload({
        url: '/activation/save/file',
        method: 'POST',
        file: file, 
        fileFormDataName: fieldname,
        formDataAppender: function(fd, key, val) {
          if (angular.isArray(val)) {
              angular.forEach(val, function(v) {
                  fd.append(key, v);
              });
          } else {
              fd.append(key, val);
          }
        },
      });

      request
      .progress(function(evt) {
        console.log('percent: ' + parseInt(100.0 * evt.loaded / evt.total));
      })
      .success(function(data, status, headers, config) {
        if(data.success){
          $scope.fileAlerts[fieldname].addAlert('success', 'File Uploaded Successfully', true);
        }
        else{
          $scope.fileAlerts[fieldname].resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.fileAlerts[fieldname].addAlert('danger', value);
          });
        }
      })
      .error(function(){
        $scope.fileAlerts[fieldname].addAlert('danger', 'File upload failed.', true);
      })
      .finally(function(){
        $scope.locked = false;
      });

    };

    function submitForm(step){
      if($scope.data[6].agree_terms !== true) {
        $scope.alerts[step].addAlert('danger', 'You must agree to the terms & conditions to use Razorpay services', true);
        return;
      }

      var request = $http({
                    method: "post",
                    url: "/activation",
                    transformRequest: transformRequestAsFormPost
      });

      request
      .success(function(data){
        if(data.success) {
          $scope.alerts[step].addAlert('success', 'Form submitted Successfully!', true);
          $scope.check[step] = true;
          getData();
        }
        else {
          $scope.alerts[step].resetAlerts();
          angular.forEach(data.errors, function(value, key){
            $scope.alerts[step].addAlert('danger', value);
          });
          $scope.check[step] = false;
        }
      })
      .error(function(){
        $scope.alerts[step].addAlert('danger', 'An error occured. Please refresh and retry.', true);
        $scope.check[step] = false;
      })
    };
}]);