'use strict';

/* Services */

angular.module('app.services', [])
			// User Service
			// Fetches & stores details of currently logged in user
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
							if(data.data.steps_finished) {
								_identity.activation_progress = parseInt((JSON.parse(data.data.steps_finished).length * 100)/ 6);
							}
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
			//Authorisation service
			//Checks if the logged in user is allowed to browse to the requested url, redirects him otherwise.
			.factory('authorization', ['$rootScope', '$state', 'user', '$location',
			  function($rootScope, $state, user, $location) {
			    return {
			      authorize: function() {
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
			])
			.factory("modeFactory",['$state', '$localStorage', '$rootScope',
			 function($state, $localStorage, $rootScope){
				var modes = {test: "test", live: "live"};	

				var currentMode = "test";


				if(angular.isDefined($localStorage.rzp_mode) ) {
			       currentMode = $localStorage.rzp_mode;
			      } else {
			        $localStorage.rzp_mode = currentMode;
			      }
			    
				$rootScope.$watch(function() {
				  return currentMode;
				}, function watchCallback(newValue, oldValue) {
				  $localStorage.rzp_mode = newValue;
				},
				true);
			    
				return {
					getMode: function(){
						return currentMode;
					},
					selectMode: function(mode){
						currentMode = modes[mode];
						$state.go($state.$current, null, { reload: true });
						return currentMode;
					},
					getModes: function(){
						return modes;
					}
				};
			}])
			//Transforms json array to form post fields, also modifies content type of submission
			.factory("transformRequestAsFormPost",
            function() {
 
                // I prepare the request data for the form post.
                function transformRequest( data, getHeaders ) {
 
                    var headers = getHeaders();
 					
                    headers[ "Content-Type" ] = "application/x-www-form-urlencoded; charset=utf-8";
 
                    return( serializeData( data ) );
 
                }
 
                // Return the factory value.
                return( transformRequest );
 
 
                // ---
                // PRVIATE METHODS.
                // ---
 
                // I serialize the given Object into a key-value pair string. This
                // method expects an object and will default to the toString() method.
                // --
                // NOTE: This is an atered version of the jQuery.param() method which
                // will serialize a data collection for Form posting.
                // --
                // https://github.com/jquery/jquery/blob/master/src/serialize.js#L45
                function serializeData( data ) {
 
                    // If this is not an object, defer to native stringification.
                    if ( ! angular.isObject( data ) ) {
 
                        return( ( data == null ) ? "" : data.toString() );
 
                    }
 
                    var buffer = [];
 
                    // Serialize each key in the object.
                    for ( var name in data ) {
 
                        if ( ! data.hasOwnProperty( name ) ) {
 
                            continue;
 
                        }
 
                        var value = data[ name ];
 
                        buffer.push(
                            encodeURIComponent( name ) +
                            "=" +
                            encodeURIComponent( ( value == null ) ? "" : value )
                        );
 
                    }
 
                    // Serialize the buffer and clean it up for transportation.
                    var source = buffer
                        .join( "&" )
                        .replace( /%20/g, "+" )
                    ;
 
                    return( source );
 
                }
 
            })
			//Alerts factory.
			//Used for creating/removing alerts for display in a page.
			.factory('alertsFactory', function() {
				function handler(){
					this.alerts = [];
					this.getAlerts = function() {
						return this.alerts;
					},
					this.closeAlert = function(index) {
						this.alerts.splice(index, 1);
					},
					this.addAlert = function($type, $message, reset) {
						$message = $message || "An error occured.";
						if(reset) this.alerts = [];
						this.alerts.push({type: $type, msg: $message});
					},
					this.resetAlerts = function() {
						this.alerts = [];
					}
				};

				return {
					getHandler: function(){
						return new handler();
					}
				};
			})
			.factory('dateFactory', function(){
				function handler($scope) {
					this.endDate = new Date();
					this.startDate = new Date(new Date().setMonth(new Date().getMonth()-1)),
					this.opened = {};
					this.dateOptions= {
						formatYear: 'yy',
						startingDay: 1,
						class: 'datepicker'
					};
					this.clear= function () {
						$scope.date.endDate = null;
						$scope.date.startDate = null;
					};
					this.open = function($event, key) {
						$event.preventDefault();
						$event.stopPropagation();
						$scope.date.opened[key] = true;
					};			  
				};

				return {
					getHandler: function($scopeVar){
						return new handler($scopeVar);
					}
				};
			})
;