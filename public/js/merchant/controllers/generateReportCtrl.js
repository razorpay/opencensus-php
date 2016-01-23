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
    var date = new Date();

    Date.prototype.getWeekNumber = function(){
      var d = new Date(+this);
      d.setHours(0,0,0);
      d.setDate(d.getDate()+4-(d.getDay()||7));
      return Math.ceil((((d-new Date(d.getFullYear(),0,1))/8.64e7)+1)/7);
    };

    function range(start, stop, step) {
      if (typeof stop == 'undefined') {
        // one param defined
        stop = start;
        start = 0;
      }

      if (typeof step == 'undefined') {
        step = 1;
      }

      if ((step > 0 && start >= stop) || (step < 0 && start <= stop)) {
        return [];
      }

      var result = [];
      for (var i = start; step > 0 ? i < stop : i > stop; i += step) {
        result.push(i);
      }

      return result;
    };

    $scope.report = {
      day: date.getDate(),
      month: date.getMonth() == 0 ? 12 : date.getMonth(),
      year: date.getMonth() == 0 ? date.getFullYear() - 1 : date.getFullYear(),
      week: date.getWeekNumber(),
      type: 'monthly'
    };

    $scope.days = range(1,31);
    $scope.weeks = range(1,53);

    $scope.generateReport = function () {

      var data = {
        'month': $scope.report.month,
        'year' : $scope.report.year,
        'type' : $scope.report.type
      };
      if ($scope.report.type=='daily') {
        data.day = $scope.report.day;
      };

      var request = $http({
        method: 'GET',
        responseType: 'arraybuffer',
        url: '/' + $scope.mode + '/reports',
        params: data,
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
          saveAs(blob, 'transaction_report.xlsx');
        }
      }).error(function (data) {
        $scope.alerts.resetAlerts();
        $scope.alerts.addAlert('danger', 'No data found for given time range');
      });
    };
  }
]);
