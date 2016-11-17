'use strict';
/* Filters */
// need load the moment.js to use this filter.
angular.module('app.filters', []).filter('fromNow', function () {
  return function (date) {
    return moment(date).fromNow();
  };
}).filter('titlecase', function () {
  return function (input) {
    if (!input)
      return input;
    var words = input.toString().split(/[\s_]/);
    for (var i = 0; i < words.length; i++) {
      words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
    }
    return words.join(' ');
  };
}).filter('rupee', function() {
  return function (input, symbol) {
    if (!input) {
      input = 0;
    };
    // If passing an alternate symbol like INR suffix it with a space
    if (typeof symbol === 'undefined') {
      // This is Rupee symbol unicode code point
      // https://codepoints.net/U+20B9
      symbol = '\u20b9';
    };

    // Poke @pranav about the regex
    var splits = input
      .toFixed(2)
      .toString()
      .match(/-?([^-]{1,3}(\..+)?$|[^-]{1,2}(?=.([^\.]{2})+(\..+)?$))/g);
    return symbol + splits.join(',');
  }
}).filter('capitalize', function() {
    return function(input) {
      return (!!input) ? input.charAt(0).toUpperCase() + input.substr(1).toLowerCase() : '';
    }
}).filter('roletoname', function() {
    return function(role) {
      var roles = {
        operations: 'Operations',
        support:    'Support',
        finance:    'Finance',
        admin:      'Admin',
        sellerapp:  'Delivery Executive',
        manager:    'Manager',
      }

      return roles[role];
    }
});
