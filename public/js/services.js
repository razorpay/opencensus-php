'use strict';

/* Services */


// Demonstrate how to register services
angular.module('app.services', [])
			.factory('user', ['$q', '$http', '$timeout',
			  function($q, $http, $timeout) {
			    var _identity = undefined,
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
			        _authenticated = identity != null;
			      },
			      identity: function(force) {
			        var deferred = $q.defer();

			        if (force === true) _identity = undefined;

			        // check and see if we have retrieved the identity data from the server. if we have, reuse it by immediately resolving
			        if (angular.isDefined(_identity)) {
			          deferred.resolve(_identity);

			          return deferred.promise;
			        }

					$http.get('/user', { ignoreErrors: true })
						.success(function(data) {
							_identity = data.data;
					   		_authenticated = data.success === true;
							deferred.resolve(_identity);
						})
						.error(function () {
						   _identity = null;
						   _authenticated = false;
						   deferred.resolve(_identity);
					});

			        return deferred.promise;
			      }
			    };
			  }
			])
			.factory('authorization', ['$rootScope', '$state', 'user', '$location',
			  function($rootScope, $state, user, $location) {
			    return {
			      authorize: function() {
			      	console.log($rootScope.toState.data.role);
			      	return user.identity()
				          .then(function() {
				          	if($rootScope.toState.data.role === 'auth') {
				            	if (user.isAuthenticated() === false) $state.go('access.signin'); // user is signed in but not authorized for desired state
				        	}
				        	else if($rootScope.toState.data.role === 'guest') {
				        		if (user.isAuthenticated() === true) $state.go('app.dashboard'); // user is signed in but not authorized for desired state
				        	}
				        });
				    }			        
			    };
			  }
			]);