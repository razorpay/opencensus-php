"use strict";
//Add Funds Controller
app.controller('GenerateReportCtrl', [
  '$scope',
  '$http',
  'alertsFactory',
  'user',
  function ($scope, $http, alertsFactory, user) {
    $scope.alerts = alertsFactory.getHandler();

    user.identity().then(function (data) {
      $scope.user = data;
    });

    function range(start, stop, step) {
      if (typeof stop == 'undefined') {
        // one param defined
        stop = start;
        start = 0;
      }

      if (typeof step == 'undefined') {
        step = 1;
      }

      if ((step > 0 && start > stop) || (step < 0 && start < stop)) {
        return [];
      }

      var result = [];
      for (var i = start; step > 0 ? i <= stop : i >= stop; i += step) {
        result.push(i);
      }

      return result;
    }

    // Let's keep this 1-indexed
    $scope.numberOfDaysInMonth = [
      0, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31
    ];

    var yesterday = moment().add(-1, 'days').toDate();

    $scope.report = {
      day: yesterday.getDate(),
      month: yesterday.getMonth() + 1,
      year: yesterday.getFullYear(),
      type: 'daily',
      entity: 'payment'
    };

    $scope.weeks = range(1,53);

    $scope.update = function() {
      if ($scope.report.entity === 'invoice') {
        $scope.report.type = 'monthly';
      }
      $scope.days = range(1, $scope.numberOfDaysInMonth[$scope.report.month]);

      // If the day is not present in the chosen month
      var day = parseInt($scope.report.day);
      if ($scope.days.indexOf(day) === -1) {
        $scope.report.day = 1;
      }
    };

    $scope.$watch('report.month + report.entity', $scope.update);
    $scope.update();

    var openInvoicePopup = function (data) {
      var url = '/' + $scope.mode  + '/reports/invoice?year=' +
        data.year + '&month=' + data.month;
        var win = window.open(url, '_blank');
        win.focus();
    };

    $scope.generateReport = function () {

      var data = {
        'month': $scope.report.month,
        'year' : $scope.report.year,
      };

      if ($scope.report.entity === 'invoice') {
        return openInvoicePopup(data);
      }

      if ($scope.report.type=='daily') {
        data.day = $scope.report.day;
      }

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
