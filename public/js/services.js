'use strict';
/* Services */
angular
  .module('app.services', [])
  // User Service
  // Fetches & stores details of currently logged in user
  // This actually returns the merchant currently
  .factory('user', [
    '$q',
    '$http',
    '$timeout',
    '$idle',
    function($q, $http, $timeout, $idle) {
      var _identity,
        _isPreSignupDone = false,
        _isVerified = false,
        _authenticated = false;

      return {
        isIdentityResolved: function() {
          return angular.isDefined(_identity);
        },
        isAuthenticated: function() {
          return _authenticated;
        },
        authenticate: function(identity) {
          _identity = identity;
          _authenticated = identity !== null;
        },
        getIdentity: function() {
          return _identity;
        },
        isPreSignupDone: function() {
          return _isPreSignupDone;
        },
        isVerified: function() {
          return _isVerified;
        },
        identity: function(force) {
          var deferred = $q.defer();
          if (force === true) _identity = undefined;
          // check and see if we have retrieved the identity data from the server. if we have, reuse it by immediately resolving
          if (angular.isDefined(_identity)) {
            deferred.resolve(_identity);
            return deferred.promise;
          }
          $http
            .get('/user', { ignoreErrors: true })
            .success(function(data) {
              try {
                dataLayer.push({
                  merchant_id: data.data.merchants[0].id,
                });
              } catch (e) {}
              _identity = data.data;
              if (data.data.steps_finished) {
                _identity.activation_progress = data.data.activation_progress;
              }
              if (
                !_identity.user.merchants.length ||
                _identity.pre_signup.length === 0
              ) {
                _isPreSignupDone = true;
              } else {
                _isPreSignupDone = _identity.pre_signup_complete;
              }

              _isVerified = _identity.user.confirmed;

              _authenticated = data.success === true;
              if (_authenticated) $idle.watch();
              else $idle.unwatch();
              deferred.resolve(_identity);
            })
            .error(function() {
              _identity = null;
              _authenticated = false;
              deferred.resolve(_identity);
            });
          return deferred.promise;
        },
      };
    },
  ])
  //Authorisation service
  //Checks if the logged in user is allowed to browse to the requested url, redirects him otherwise.
  .factory('authorization', [
    '$rootScope',
    '$state',
    'user',
    '$location',
    function($rootScope, $state, user) {
      return {
        authorize: function() {
          return user.identity().then(function() {
            if ($rootScope.toState.data.role === 'auth') {
              if (!user.isAuthenticated()) {
                $state.go('access.signin');
              }
              if (!user.isVerified() || !user.isPreSignupDone()) {
                $state.go('access.pre_signup');
              }
            } else if ($rootScope.toState.data.role === 'guest') {
              if (
                user.isAuthenticated() &&
                user.isVerified() &&
                user.isPreSignupDone()
              ) {
                if ($rootScope.role === 'sellerapp') {
                  $state.go('app.invoices');
                } else {
                  $state.go('app.dashboard'); // user is signed in but not authorized for desired state
                }
              }
            }
          });
        },
      };
    },
  ])
  .factory('modeFactory', [
    '$state',
    '$localStorage',
    '$rootScope',
    'user',
    function($state, $localStorage, $rootScope, user) {
      var modes = {
        test: 'test',
        live: 'live',
      };
      var currentMode = 'test';
      if (angular.isDefined($localStorage.rzp_mode)) {
        currentMode = $localStorage.rzp_mode;
        user.identity().then(function(data) {
          if (currentMode == 'live' && parseInt(data.activated) !== 1) {
            currentMode = 'test';
          }
        });
      } else {
        $localStorage.rzp_mode = currentMode;
      }
      $rootScope.$watch(
        function() {
          return currentMode;
        },
        function watchCallback(newValue) {
          $localStorage.rzp_mode = newValue;
        },
        true
      );
      return {
        getMode: function() {
          return currentMode;
        },
        selectMode: function(mode) {
          currentMode = modes[mode];
          $state.go($state.$current, null, { reload: true });
          return currentMode;
        },
        getModes: function() {
          return modes;
        },
      };
    },
  ]) //Transforms json array to form post fields, also modifies content type of submission
  .factory('transformRequestAsFormPost', function() {
    // I prepare the request data for the form post.
    function transformRequest(data, getHeaders) {
      var headers = getHeaders();
      headers['Content-Type'] =
        'application/x-www-form-urlencoded; charset=utf-8';
      return serializeData(data);
    }
    // Return the factory value.
    return transformRequest;
    function serializeData(data) {
      if (typeof data === 'undefined') {
        data = {};
      }
      // If this is not an object, defer to native stringification.
      if (!angular.isObject(data)) {
        return data === null ? '' : data.toString();
      }
      var buffer = [];

      var formalizeData = function(formData, level) {
        var flattened = {};
        for (var key in formData) {
          var val = formData[key];
          var keyToSend = key;

          if (level > 1) {
            keyToSend = '[' + keyToSend + ']';
          }

          if (typeof formData[key] === 'object') {
            var tmpFlattened = formalizeData(formData[key], level + 1);

            for (var tmpKey in tmpFlattened) {
              var tmpVal = tmpFlattened[tmpKey];

              flattened[keyToSend + tmpKey] = tmpVal;
            }
          } else {
            flattened[keyToSend] = val;
          }
        }
        return flattened;
      };

      var flattenedOb = formalizeData(data, 1);

      for (var key in flattenedOb) {
        if (typeof flattenedOb[key] === 'undefined') {
          continue;
        }

        if (typeof flattenedOb[key] === 'boolean') {
          flattenedOb[key] = flattenedOb[key] ? 1 : 0;
        }

        buffer.push(
          encodeURIComponent(key) + '=' + encodeURIComponent(flattenedOb[key])
        );
      }

      // Serialize the buffer and clean it up for transportation.
      var source = buffer.join('&').replace(/%20/g, '+');
      return source;
    }
  }) //Alerts factory.
  //Used for creating/removing alerts for display in a page.
  .factory('alertsFactory', function() {
    var handler = function() {
      this.alerts = [];

      this.getAlerts = function() {
        return this.alerts;
      };

      this.closeAlert = function(index) {
        this.alerts.splice(index, 1);
      };

      this.addAlert = function($type, $message, reset) {
        $message = $message || 'An error occured.';
        if (reset) {
          this.alerts = [];
        }
        this.alerts.push({
          type: $type,
          msg: $message,
        });

        window.scrollTo(0, 0);
      };

      this.resetAlerts = function(last) {
        if (!last) {
          this.alerts = [];
        } else {
          this.alerts.pop();
        }
      };
    };

    return {
      getHandler: function() {
        return new handler();
      },
    };
  })
  .factory('dateFactory', function() {
    var handler = function($scope) {
      this.endDate = new Date();
      this.startDate = new Date(new Date().setMonth(new Date().getMonth() - 1));
      this.opened = {};
      this.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker',
      };
      this.clear = function() {
        $scope.date.endDate = null;
        $scope.date.startDate = null;
      };
      this.open = function($event, key) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.date.opened = {};
        $scope.date.opened[key] = true;
      };
    };
    return {
      getHandler: function($scopeVar) {
        return new handler($scopeVar);
      },
    };
  })
  // Fetches & stores details of currently logged in user
  .factory('admin', [
    '$q',
    '$http',
    '$timeout',
    '$idle',
    function($q, $http, $timeout, $idle) {
      var _identity,
        _authenticated = false;
      return {
        isIdentityResolved: function() {
          return angular.isDefined(_identity);
        },
        isAuthenticated: function() {
          return _authenticated;
        },
        authenticate: function(identity) {
          _identity = identity;
          _authenticated = identity !== null;
        },
        isSuperAdmin: function() {
          var roles = _identity.roles;

          var isPresent = roles.some(function(element) {
            return element.toLowerCase().match('superadmin');
          });

          if (roles && isPresent) {
            return true;
          }
        },
        identity: function(force) {
          var deferred = $q.defer();
          if (force === true) _identity = undefined;
          // check and see if we have retrieved the identity data from the server. if we have, reuse it by immediately resolving
          if (angular.isDefined(_identity)) {
            deferred.resolve(_identity);
            return deferred.promise;
          }
          $http
            .get('/admin/user', { ignoreErrors: true })
            .success(function(data) {
              _identity = data.data;
              _authenticated = data.success === true;
              if (_authenticated) $idle.watch();
              else $idle.unwatch();
              deferred.resolve(_identity);
            })
            .error(function() {
              _identity = null;
              _authenticated = false;
              deferred.resolve(_identity);
            });
          return deferred.promise;
        },
      };
    },
  ])
  //Organization
  .factory('organization', [
    '$q',
    '$http',
    function($q, $http) {
      var _org, _roles;

      return {
        fetchCurrentOrg: function() {
          var deferred = $q.defer();

          if (angular.isDefined(_org)) {
            deferred.resolve(_org);
            return deferred.promise;
          }

          $http.get('/admin/org').success(function(data) {
            if (data.success) {
              _org = data.data;
            }

            deferred.resolve(_org);
          });

          return deferred.promise;
        },
        fetchRoles: function() {
          var deferred = $q.defer();

          if (angular.isDefined(_roles)) {
            deferred.resolve(_roles);
            return deferred.promise;
          }

          $http
            .get('/admin/generic', {
              ignoreErrors: true,
              params: {
                route_name: 'role_get_multiple',
              },
            })
            .success(function(data) {
              if (data.success) {
                _roles = data.data.items;
                deferred.resolve(_roles);
              } else {
                deferred.reject(data.errors);
              }
            })
            .error(function(data) {
              deferred.reject(data.errors);
            });

          return deferred.promise;
        },
        // No caching implemented
        fetchGroups: function() {
          var deferred = $q.defer();

          var groups = [];

          $http
            .get('/admin/generic', {
              params: {
                route_name: 'group_get_multiple',
              },
            })
            .success(function(data) {
              if (data.success === true) {
                angular.forEach(data.data.items, function(group) {
                  var groupObj = {
                    id: group.id,
                    name: group.name,
                    description: group.description,
                  };

                  groups.push(groupObj);
                });

                deferred.resolve(groups);
              } else {
                groups = [];
              }
            })
            .error(function(data) {
              return data.errors;
            });

          return deferred.promise;
        },
        fetchAllowedGroups: function(groupId) {
          var deferred = $q.defer();

          var allowed_groups = [];

          $http
            .get('/admin/generic', {
              params: {
                route_name: 'group_get_allowed_groups',
                url_params: {
                  '{groupId}': groupId, //TODO Add actual ids
                },
              },
            })
            .success(function(data) {
              if (data.success === true) {
                if (data.data) {
                  angular.forEach(data.data, function(group) {
                    var groupObj = {
                      id: group.id,
                      name: group.name,
                      description: group.description,
                    };

                    allowed_groups.push(groupObj);
                  });

                  deferred.resolve(allowed_groups);
                }
              } else {
                allowed_groups = [];
              }
            })
            .error(function() {});

          return deferred.promise;
        },
        fetchPermissions: function() {
          if (this.permissions) {
            return this.permissions;
          }

          var perms = [];
          $http
            .get('/admin/generic', {
              ignoreErrors: true,
              params: {
                route_name: 'permission_get_multiple',
              },
            })
            .success(function(data) {
              if (data.success === true) {
                if (data.data.items.length > 0) {
                  angular.forEach(data.data.items, function(perm) {
                    perms.push(perm);
                  });
                }
              } else {
                perms = {};
              }
            })
            .error(function() {});
          this.permissions = perms;
          return perms;
        },
        fetchUsers: function() {
          if (this.users) {
            return this.users;
          }
          var deferred = $q.defer();
          var users = [];

          $http
            .get('/admin/generic', {
              params: {
                route_name: 'admin_get_multiple',
              },
            })
            .success(function(data) {
              if (data.success === true) {
                if (data.data.items.length > 0) {
                  angular.forEach(data.data.items, function(user) {
                    users.push(user);
                  });
                }

                deferred.resolve(users);
              } else {
                users = [];
              }
            })
            .error(function(data) {
              return data.errors;
            });

          this.users = users;
          return deferred.promise;
        },
      };
    },
  ])
  .factory('theme', [
    function() {
      return {
        apply: function(themeVars) {
          var style = document.createElement('style');
          style.type = 'text/css';

          var rules = themes.theme(themeVars);

          if (style.styleSheet) {
            style.styleSheet.cssText = rules;
          } else {
            style.appendChild(document.createTextNode(rules));
          }
          document.getElementsByTagName('head')[0].appendChild(style);
        },
      };
    },
  ])
  //Authorisation service
  //Checks if the logged in user is allowed to browse to the requested url, redirects him otherwise.
  .factory('adminAuthorization', [
    '$rootScope',
    '$state',
    'admin',
    function($rootScope, $state, admin) {
      return {
        authorize: function() {
          var promise = admin.identity().then(function() {
            // Direct access to /admin (w/o hash) should always trigger auth
            // if the admin is not logged in
            if (!$rootScope.toState) {
              if (admin.isAuthenticated()) {
                $state.go('app.dashboard');
              } else {
                window.location.href = '/admin/auth';
              }

              return;
            }

            // Need auth ?
            if ($rootScope.toState.data.role === 'auth') {
              // If you are not logged in and not on the signin page
              if (admin.isAuthenticated() === false) {
                // Will cause redirect
                window.location.href = '/admin/auth';
              }

              // user is signed in but not authorized for desired state
              if ($rootScope.toState.data.superadmin) {
                admin.identity().then(function(data) {
                  if (data.superadmin != 1) $state.go('app.dashboard');
                });
              }
            } else if ($rootScope.toState.data.role === 'guest') {
              // Don't need auth!
              if (admin.isAuthenticated() === true) {
                $state.go('app.dashboard'); // user is signed in but not authorized for desired state
              }
            }
          });

          return promise;
        },
      };
    },
  ])
  .factory('statusClass', [
    function() {
      return function(status) {
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
          processing: 'bg-info',

          // refund
          partial: 'bg-info', // payment.refund_status

          // invoice
          draft: 'bg-light',
          issued: 'bg-info',
          expired: 'bg-danger',

          // dispute
          open: 'bg-primary',
          under_review: 'bg-warning',
          won: 'bg-success',
          lost: 'bg-danger',
        };

        return mapper[status];
      };
    },
  ])
  // Only returns true if we have a valid status value
  .factory('isStatusKey', [
    function() {
      return function(key, value) {
        if (!value) {
          return false;
        }
        var statusKeys = ['status', 'refund_status'];

        return statusKeys.indexOf(key) > -1;
      };
    },
  ])
  // Force will pick up from the row.entity field
  // rather than the key
  .factory('getState', [
    function() {
      return function(type, force, entityType) {
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
            state =
              'app.entitiesdetail({id:value, mode:mode, type: "pricing"})';
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
              state =
                'app.entitiesdetail({id:value, mode:mode, type: row.entity})';
            }

            if (type.substr(-3) === '_id') {
              var key = type.slice(0, -3);
              state =
                'app.entitiesdetail({id:value, mode:mode, type: "' +
                (entityType || key) +
                '"})';
            }
        }
        return state;
      };
    },
  ])
  .factory('riskMap', [
    function() {
      return {
        1: ['Very Low', 'bg-success'],
        2: ['Low', 'bg-success'],
        3: ['Default', 'bg-info'],
        4: ['High', 'bg-danger'],
        5: ['Very High', 'bg-danger'],
      };
    },
  ])
  .factory('jqTourbusService', function() {
    return {
      start: $.noop,
      next: $.noop,
      prev: $.noop,
      stop: $.noop,
    };
  })
  .factory('getStateMerchant', function() {
    return function(key, value) {
      switch (key) {
        case 'payment_id':
          return 'app.payments.detail({id: value})';
        case 'refund_id':
          return 'app.refunds.detail({id: value})';
        case 'settlement_id':
          return 'app.settlements.detail({id: value})';
        case 'settlement_id':
          return 'app.settlements.detail({id: value})';
        case 'order_id':
          return 'app.orders.detail({id: value})';
        case 'invoice_id':
          return 'app.invoicedetails({id: value})';
        default:
          return '.';
      }
    };
  })
  .factory('permissionsFactory', function() {
    var _permissions = {};
    return {
      getPermissions: function() {
        return _permissions;
      },
      setPermissions: function(permissions) {
        _permissions = permissions;
      },
    };
  })
  .factory('displayClass', [
    'isStatusKey',
    'statusClass',
    function(isStatusKey, statusClass) {
      return function(key, value) {
        if (isStatusKey(key, value)) {
          return 'label ' + statusClass(value);
        }

        if (value === null) {
          return 'label label-warning col-lg-1';
        } else if (value === '') {
          return 'label label-info';
        } else {
          return '';
        }
      };
    },
  ])
  .factory('getEntity', [
    function() {
      return function(key) {
        return key.substr(0, key.length - 3);
      };
    },
  ])
  .factory('getType', [
    'getEntity',
    function(getEntity) {
      return function(key, value) {
        var entity = key.substr(0, key.length - 3);
        var isTimestamp = function(key) {
          return (
            key.substr(-3) === '_at' ||
            key.substr(-3) === '_on' ||
            key === 'next_run'
          );
        };
        // These have their own views
        var specialEntities = ['merchant_id', 'payment_id'];
        var isId = function(key) {
          var validEntities = [
            'adjustment',
            'amex',
            'atom',
            'axis_genius',
            'axis_migs',
            'balance',
            'bank_account',
            'bank_account',
            'batch_fund_transfer',
            'billdesk',
            'card',
            'credits',
            'customer',
            'ebs',
            'file_store',
            'first_data',
            'fund_transfer_attempt',
            'emi_plan',
            'hdfc',
            'iin',
            'invoice',
            'merchant',
            'methods',
            'mobikwik',
            'netbanking',
            'payment',
            'payment_analytics',
            'pricing',
            'refund',
            'settlement',
            'settlement_details',
            'schedule',
            'terminal',
            'token',
            'transaction',
            'wallet',
            'webhook',
          ];
          // It needs to be suffixed with _id
          // and be a valid entity name for this to work
          return key.substr(-3) === '_id' && validEntities.indexOf(entity) > -1;
        };
        // Timestamps could be blank, which is why
        // we consider its value as well
        if (value && isTimestamp(key)) {
          return 'timestamp';
        } else if (key.substr(0, 11) === 'base_amount') {
          // Base Amounts are always in INR
          // includes base_amount and base_amount_refunded
          return 'amount_inr';
        } else if (
          key.substr(-6) === 'amount' ||
          key.substr(0, 7) === 'amount_'
        ) {
          return 'amount';
        } else if (isId(key)) {
          // All other entity links are considered here
          if (specialEntities.indexOf(key) > -1) {
            return getEntity(key);
          } else {
            return 'id';
          }
        } else {
          // Unknown type is entity specific things, like currency
          return 'unknown';
        }
      };
    },
  ])
  .factory('displayValue', [
    'getType',
    '$filter',
    function(getType, $filter) {
      return function(key, value, entity) {
        if (typeof entity === 'undefined') {
          entity = {};
        }
        var type = getType(key, value);
        // Set timezone to IST
        moment().utcOffset(5.5);
        switch (type) {
          case 'timestamp':
            return (
              moment(value * 1000).format('D MMM YYYY h:mm:ss a (ddd) ') + 'IST'
            );
          case 'amount_inr':
            return $filter('rupee')(value / 100);
          case 'amount':
            var currency = 'INR';
            if (entity.hasOwnProperty('currency')) {
              currency = entity.currency;
            }
            return $filter('propercurrency')(value / 100, currency);
          default:
            if (value === null) {
              return 'null';
              // We want to display an empty string prominently
            } else if (value === '') {
              return '"\u2000"';
            } else {
              return value;
            }
        }
      };
    },
  ])
  .factory('utils', [
    '$state',
    function($state) {
      return {
        humanize: function(str) {
          var frags = str.split('_');

          for (var i = 0; i < frags.length; i++) {
            frags[i] = frags[i].charAt(0).toUpperCase() + frags[i].slice(1);
          }

          return frags.join(' ');
        },
        mergeUnique: function(arr, isCaseSensitive) {
          isCaseSensitive = isCaseSensitive || false;
          var auxArr = arr.concat();

          for (var i = 0; i < auxArr.length; i++) {
            for (var j = i + 1; j < auxArr.length; j++) {
              if (
                (isCaseSensitive &&
                  auxArr[i].toLowerCase() === auxArr[j].toLowerCase()) ||
                auxArr[i] === auxArr[j]
              ) {
                auxArr.splice(j--, 1);
              }
            }
          }

          return auxArr;
        },
        isArray: function(val) {
          if (!val) {
            return false;
          }

          return val instanceof Array;
        },

        isIndexedArray: function(val) {
          if (this.isArray(val) === false) {
            return false;
          }

          return ['object', 'undefined'].indexOf(typeof val[0]) === -1;
        },

        // rightmost obj gets preference for same keys
        concatObj: function() {
          var result = {};
          var len = arguments.length;
          for (var i = 0; i < len; i++) {
            for (var p in arguments[i]) {
              if (arguments[i].hasOwnProperty(p)) {
                result[p] = arguments[i][p];
              }
            }
          }

          return result;
        },
        isWorkflow: function(data) {
          if (
            typeof data.id !== 'undefined' &&
            data.id.indexOf('w_action') === 0 &&
            typeof data.workflow_id !== 'undefined'
          ) {
            return true;
          }

          return false;
        },

        resolveEntityLinkAndGo: function(entityId, entityName) {
          var entityMap = {
            merchant: {
              route: 'app.merchants.detail',
              idParam: 'id',
              sign: '',
            },
            credits: {
              route: 'app.merchants.detail',
              idParam: 'id',
              sign: '',
            },
            role: {
              route: 'app.roles.edit',
              idParam: 'id',
              sign: 'role_',
            },
            methods: {
              route: 'app.merchants.detail',
              idParam: 'id',
              sign: '',
            },
            adjustment: {
              route: 'app.merchants.detail',
              idParam: 'id',
              sign: '',
            },
            schedule_task: {
              route: 'app.merchants.detail',
              idParam: 'id',
              sign: '',
            },
            feature: {
              route: 'app.merchants.detail',
              idParam: 'id',
              sign: '',
            },
          };

          if (typeof entityMap[entityName] !== 'undefined') {
            var entityDetails = entityMap[entityName];

            var params = {};
            params[entityDetails.idParam] = entityDetails.sign + entityId;

            $state.go(entityDetails.route, params);
          }
        },
      };
    },
  ])
  .factory('utilMapping', [
    '$state',
    function($state) {
      // mapping used in multiple files
      var map = {
        networkMap: {
          AMEX: 'American Express',
          DICL: 'Diners Club',
          DISC: 'Discover',
          JCB: 'JCB',
          MAES: 'Maestro',
          MC: 'MasterCard',
          RUPAY: 'RuPay',
          VISA: 'Visa',
          UNP: 'Union Pay',
        },
        methodMap: {
          card: 'Card',
          wallet: 'Wallet',
          netbanking: 'Netbanking',
          upi: 'UPI',
          emi: 'EMI',
        },
        gatewayAcquirerMap: {
          axis: 'Axis',
          hdfc: 'HDFC',
          amex: 'Amex',
          icic: 'ICICI',
        },
        gatewayCardMap: {
          first_data: 'First Data',
          hdfc: 'FSS',
          axis_migs: 'Axis Migs',
          cybersource: 'Cybersource',
          amex: 'Amex',
          sharp: 'Sharp',
        },
        gatewayEmiMap: {
          amex: 'Amex',
          hdfc: 'FSS',
          first_data: 'First Data',
          sharp: 'Sharp',
        },
        gatewayNBMap: {
          netbanking_hdfc: 'HDFC Netbanking',
          netbanking_corporation: 'Corporation Netbanking',
          netbanking_kotak: 'Kotak Netbanking',
          netbanking_icici: 'ICICI Netbanking',
          netbanking_axis: 'Axis Netbanking',
          netbanking_federal: 'Federal Netbanking',
          netbanking_airtel: 'Airtel Netbanking',
          netbanking_rbl: 'RBL netbanking',
          netbanking_indusind: 'IndusInd netbanking',
          billdesk: 'Billdesk',
          ebs: 'Ebs',
          sharp: 'Sharp',
        },
        gatewayWalletMap: {
          mobikwik: 'Mobikwik',
          wallet_airtelmoney: 'Airtelmoney',
          wallet_freecharge: 'Freecharge',
          wallet_jiomoney: 'Jiomoney',
          wallet_olamoney: 'Olamoney',
          wallet_payumoney: 'Payumoney',
          wallet_payzapp: 'Payzapp',
          wallet_mpesa: 'Mpesa',
          wallet_sbibuddy: 'SbiBuddy',
          wallet_openwallet: 'Openwallet',
          sharp: 'Sharp',
        },
        gatewayUpiMap: {
          upi_idfc: 'IDFC UPI',
          upi_icici: 'ICICI UPI',
          upi_mindgate: 'Mindgate/HDFC UPI',
          sharp: 'Sharp',
        },
        walletMap: {
          payzapp: 'Payzapp',
          mobikwik: 'Mobikwik',
          payumoney: 'Payumoney',
          olamoney: 'Olamoney',
          airtelmoney: 'Airtelmoney',
          freecharge: 'Freecharge',
          jiomoney: 'Jiomoney',
          openwallet: 'Openwallet',
          mpesa: 'Mpesa',
          paytm: 'Paytm',
        },
      };

      return {
        getMap: function(key) {
          return map[key];
        },
      };
    },
  ]);
