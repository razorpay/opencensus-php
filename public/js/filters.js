'use strict';

/* Filters */
// need load the moment.js to use this filter. 
angular.module('app.filters', [])
	.filter('fromNow', function() {
		return function(date) {
		  return moment(date).fromNow();
		}
	})
	.filter('titlecase', function () {
		return function (input) {
			var words = input.split(' ');
			for (var i = 0; i < words.length; i++) {
			  words[i] = words[i].toLowerCase(); // lowercase everything
			  words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
			}
			return words.join(' ');
		}
	})
  ;