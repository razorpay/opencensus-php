//Add Funds Controller
app.controller('GenerateReportCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'user',
  'uiLoad',
  'transformRequestAsFormPost',
  function ($scope, $http, alertsFactory, user, uiLoad, transformRequestAsFormPost) {
    $scope.alerts = alertsFactory.getHandler();
    $scope.month = 'January';
    
    $scope.generateReport = function () {
      
      var request = $http({
        method: 'POST',
        responseType: 'arraybuffer',
        url: '/' + $scope.mode + '/generatereport',
        transformRequest: transformRequestAsFormPost,
        data: { month: $scope.month },
        headers: {
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
      });
      
      request.success(function (data) {
        if (data) {
          $scope.alerts.addAlert('success', 'Your report will download shortly', true);
          var blob = new Blob([data], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          });
          var objectUrl = URL.createObjectURL(blob);
          window.open(objectUrl);
        } 
        else {
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
]);