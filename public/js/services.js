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
							if(data.data.steps_finished) {
								_identity.activation_progress = parseInt((data.data.steps_finished.length * 100)/ 6);
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
			.factory('alertsFactory', function() {
				var alerts = [];
				return {
					initialise:  function(){
						alerts = [];
						return {
							getAlerts: function() {
								return alerts;
							},
							closeAlert: function(index) {
								alerts.splice(index, 1);
							}
						}
					},					
					addAlert: function($type, $message) {
						$message = $message || "An error occured.";

						alerts.push({type: $type, msg: $message});
					},
					resetAlerts: function() {
						alerts = [];
					}
				}
			})
;