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

    var yesterday = moment().add(-1, 'days').toDate();

    $scope.report = {
      day: yesterday.getDate(),
      month: yesterday.getMonth() + 1,
      year: yesterday.getFullYear(),
      type: 'daily',
      entity: 'payment'
    };

    $scope.days = range(1,31);
    $scope.weeks = range(1,53);

    $scope.generateReport = function () {

      var data = {
        'month': $scope.report.month,
        'year' : $scope.report.year,
      };

      if ($scope.report.type=='daily') {
        data.day = $scope.report.day;
      };

      var request = $http({
        method: 'GET',
        responseType: 'arraybuffer',
        url: '/' + $scope.mode + '/reports/' + $scope.report.entity,
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
          saveAs(blob, $scope.report.entity+'_report.xlsx');
        }
      }).error(function (data) {
        $scope.alerts.resetAlerts();
         $scope.alerts.addAlert('danger', 'No data found for given time range');
      });
    };
  }
]);
