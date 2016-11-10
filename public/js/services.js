'use strict';
/* Services */
angular.module('app.services', [])
// User Service
// Fetches & stores details of currently logged in user
// This actually returns the merchant currently
.factory('user', [
  '$q',
  '$http',
  '$timeout',
  '$idle',
  function ($q, $http, $timeout, $idle) {
    var _identity, _authenticated = false;
    return {
      isIdentityResolved: function () {
        return angular.isDefined(_identity);
      },
      isAuthenticated: function () {
        return _authenticated;
      },
      authenticate: function (identity) {
        _identity = identity;
        _authenticated = identity !== null;
      },
      identity: function (force) {
        var deferred = $q.defer();
        if (force === true)
          _identity = undefined;
        // check and see if we have retrieved the identity data from the server. if we have, reuse it by immediately resolving
        if (angular.isDefined(_identity)) {
          deferred.resolve(_identity);
          return deferred.promise;
        }
        $http.get('/user', { ignoreErrors: true }).success(function (data) {
          _identity = data.data;
          if (data.data.steps_finished) {
            _identity.activation_progress = parseInt(data.data.steps_finished.length * 100 / 5);
          }
          _authenticated = data.success === true;
          if (_authenticated)
            $idle.watch();
          else
            $idle.unwatch();
          deferred.resolve(_identity);
        }).error(function () {
          _identity = null;
          _authenticated = false;
          deferred.resolve(_identity);
        });
        return deferred.promise;
      }
    };
  }
])
//Authorisation service
//Checks if the logged in user is allowed to browse to the requested url, redirects him otherwise.
.factory('authorization', [
  '$rootScope',
  '$state',
  'user',
  '$location',
  function ($rootScope, $state, user) {
    return {
      authorize: function () {
        return user.identity().then(function () {
          if ($rootScope.toState.data.role === 'auth') {
            if (user.isAuthenticated() === false)
              $state.go('access.signin');  // user is signed in but not authorized for desired state
          } else if ($rootScope.toState.data.role === 'guest') {
            if (user.isAuthenticated() === true)
              $state.go('app.dashboard');  // user is signed in but not authorized for desired state
          }
        });
      }
    };
  }
]).factory('modeFactory', [
  '$state',
  '$localStorage',
  '$rootScope',
  'user',
  function ($state, $localStorage, $rootScope, user) {
    var modes = {
      test: 'test',
      live: 'live'
    };
    var currentMode = 'test';
    if (angular.isDefined($localStorage.rzp_mode)) {
      currentMode = $localStorage.rzp_mode;
      user.identity().then(function (data) {
        if (currentMode == 'live' && parseInt(data.activated) !== 1) {
          currentMode = 'test';
        }
      });
    } else {
      $localStorage.rzp_mode = currentMode;
    }
    $rootScope.$watch(function () {
      return currentMode;
    }, function watchCallback(newValue) {
      $localStorage.rzp_mode = newValue;
    }, true);
    return {
      getMode: function () {
        return currentMode;
      },
      selectMode: function (mode) {
        currentMode = modes[mode];
        $state.go($state.$current, null, { reload: true });
        return currentMode;
      },
      getModes: function () {
        return modes;
      }
    };
  }
])  //Transforms json array to form post fields, also modifies content type of submission
.factory('transformRequestAsFormPost', function () {
  // I prepare the request data for the form post.
  function transformRequest(data, getHeaders) {
    var headers = getHeaders();
    headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
    return serializeData(data);
  }
  // Return the factory value.
  return transformRequest;
  function serializeData(data) {
    // If this is not an object, defer to native stringification.
    if (!angular.isObject(data)) {
      return data === null ? '' : data.toString();
    }
    var buffer = [];
    // Serialize each key in the object.
    for (var name in data) {
      if (!data.hasOwnProperty(name)) {
        continue;
      }
      var value = data[name];
      buffer.push(encodeURIComponent(name) + '=' + encodeURIComponent(value === null ? '' : value));
    }
    // Serialize the buffer and clean it up for transportation.
    var source = buffer.join('&').replace(/%20/g, '+');
    return source;
  }
})  //Alerts factory.
    //Used for creating/removing alerts for display in a page.
.factory('alertsFactory', function () {
  var handler = function() {
    this.alerts = [];
    this.getAlerts = function () {
      return this.alerts;
    };

    this.closeAlert = function (index) {
      this.alerts.splice(index, 1);
    };

    this.addAlert = function ($type, $message, reset) {
      $message = $message || 'An error occured.';
      if (reset) {
        this.alerts = [];
      }
      this.alerts.push({
        type: $type,
        msg: $message
      });
    };

    this.resetAlerts = function (last) {
      if (!last) {
        this.alerts = [];
      }
      else {
        this.alerts.pop();
      }
    };
  };
  return {
    getHandler: function () {
      return new handler();
    }
  };
}).factory('dateFactory', function () {
  var handler = function($scope) {
    this.endDate = new Date();
    this.startDate = new Date(new Date().setMonth(new Date().getMonth() - 1));
    this.opened = {};
    this.dateOptions = {
      formatYear: 'yy',
      startingDay: 1,
      class: 'datepicker'
    };
    this.clear = function () {
      $scope.date.endDate = null;
      $scope.date.startDate = null;
    };
    this.open = function ($event, key) {
      $event.preventDefault();
      $event.stopPropagation();
      $scope.date.opened = {};
      $scope.date.opened[key] = true;
    };
  };
  return {
    getHandler: function ($scopeVar) {
      return new handler($scopeVar);
    }
  };
})
// Fetches & stores details of currently logged in user
.factory('admin', [
  '$q',
  '$http',
  '$timeout',
  '$idle',
  function ($q, $http, $timeout, $idle) {
    var _identity, _authenticated = false;
    return {
      isIdentityResolved: function () {
        return angular.isDefined(_identity);
      },
      isAuthenticated: function () {
        return _authenticated;
      },
      authenticate: function (identity) {
        _identity = identity;
        _authenticated = identity !== null;
      },
      identity: function (force) {
        var deferred = $q.defer();
        if (force === true)
          _identity = undefined;
        // check and see if we have retrieved the identity data from the server. if we have, reuse it by immediately resolving
        if (angular.isDefined(_identity)) {
          deferred.resolve(_identity);
          return deferred.promise;
        }
        $http.get('/admin/user', { ignoreErrors: true }).success(function (data) {
          _identity = data.data;
          _authenticated = data.success === true;
          if (_authenticated)
            $idle.watch();
          else
            $idle.unwatch();
          deferred.resolve(_identity);
        }).error(function () {
          _identity = null;
          _authenticated = false;
          deferred.resolve(_identity);
        });
        return deferred.promise;
      }
    };
  }
])
//Authorisation service
//Checks if the logged in user is allowed to browse to the requested url, redirects him otherwise.
.factory('adminAuthorization', [
  '$rootScope',
  '$state',
  'admin',
  '$location',
  '$http',
  function ($rootScope, $state, admin, $location) {
    return {
      authorize: function () {

        var promise = admin.identity().then(function () {

          // Need auth ?
          if ($rootScope.toState.data.role === 'auth') {

            // If you are not logged in and not on the signin page
            if (admin.isAuthenticated() === false) {
              // Will cause redirect
              window.location.href = '/admin/auth';
            }

            // user is signed in but not authorized for desired state
            if ($rootScope.toState.data.superadmin) {
              admin.identity().then(function (data) {
                if (data.superadmin != 1)
                  $state.go('app.dashboard');
              });
            }
          }
          // Don't need auth!
          else if ($rootScope.toState.data.role === 'guest') {
            if (admin.isAuthenticated() === true) {
              $state.go('app.dashboard');  // user is signed in but not authorized for desired state
            }
          }
        });

        return promise;
      }
    };
  }
])
.factory('statusClass', [function() {
  return function (status) {
    var mapper = {
      // Common
      created: 'bg-light',
      failed: 'bg-danger',

      // payment
      authorized: 'bg-info',
      captured: 'bg-success',
      refunded: 'bg-primary',

      // order
      attempted: 'bg-info',
      paid: 'bg-success',

      // settlement
      processed: 'bg-success',

      // billdesk
      cancelled: 'bg-danger',
      null: 'bg-warning',

      // batch
      processing: 'bg-info'
    };

    return mapper[status];
  };
}])
.factory('isStatusKey', [function() {
  return function (key) {
    var statusKeys = [
      'status',
      'refund_status'
    ];

    return (statusKeys.indexOf(key) > -1);
  };
}])
// Force will pick up from the row.entity field
// rather than the key
.factory('getState', [function() {
  return function (type, force) {
    var state = '.';
    switch (type) {
      case 'merchant_id':
      case 'merchant':
        state = 'app.merchants.detail({id: value})';
        break;

      case 'pricing_plan_id':
      case 'plan_id':
        state = 'app.pricingdetail({id: value})';
        break;

      case 'pricing_rule_id':
        state = 'app.entitiesdetail({id:value, mode:mode, type: "pricing"})';
        break;

      case 'payment_id':
      case 'payment':
        state = 'app.payments({id:value, mode:mode})';
        break;

      case 'iin':
        state = 'app.entitiesdetail({id:value, mode:mode, type: "iin"})';
        break;

      default:
        if (force === true) {
          state = 'app.entitiesdetail({id:value, mode:mode, type: row.entity})';
        }

        if (type.substr(-3) === '_id') {
          var key = type.slice(0,-3);
          state = 'app.entitiesdetail({id:value, mode:mode, type: "'+key+'"})';
        }

      }
      return state;
    };
  }
]).factory('riskMap', [function() {
  return {
    1: ['Very Low', 'bg-success'],
    2: ['Low', 'bg-success'],
    3: ['Default', 'bg-info'],
    4: ['High', 'bg-danger'],
    5: ['Very High', 'bg-danger']
  };
}])
.factory('jqTourbusService', function() {
  return {
    start: $.noop,
    next: $.noop,
    prev: $.noop,
    stop: $.noop
  };
})
.factory('permissionsFactory', function () {
  var _permissions = {};
  return {
    getPermissions: function () {
      return _permissions;
    },
    setPermissions: function(permissions) {
      console.log("Setting Permissions", permissions);
      _permissions = permissions;
    }
  };
});
