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
    $scope.report = {
      month: 1,
      year: 2015
    };
    
    $scope.generateReport = function () {
      
      var request = $http({
        method: 'GET',
        responseType: 'arraybuffer',
        url: '/' + $scope.mode + '/generatereport/' + $scope.report.month + '/' + $scope.report.year,
        transformRequest: transformRequestAsFormPost,
        data: $scope.report,
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
          saveAs(blob, 'transaction_report' + '.xlsx');
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