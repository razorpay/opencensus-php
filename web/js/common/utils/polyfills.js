// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/startsWith
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/endsWith
if (!String.prototype.startsWith) {
  // eslint-disable-next-line func-names, no-extend-native
  String.prototype.startsWith = function (searchString, position) {
    return this.substr(position || 0, searchString.length) === searchString;
  };

  // eslint-disable-next-line func-names, no-extend-native
  String.prototype.endsWith = function (searchStr, Position) {
    if (!(Position < this.length)) Position = this.length;
    // eslint-disable-next-line no-bitwise
    else Position |= 0; // round position
    return this.substr(Position - searchStr.length, searchStr.length) === searchStr;
  };
}

// https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/String/endsWith
if (!String.prototype.endsWith) {
  // eslint-disable-next-line func-names, no-extend-native
  String.prototype.endsWith = function (searchString, position) {
    // eslint-disable-next-line no-var
    var subjectString = this.toString();
    if (
      typeof position !== 'number' ||
      !isFinite(position) ||
      Math.floor(position) !== position ||
      position > subjectString.length
    ) {
      position = subjectString.length;
    }
    position -= searchString.length;
    // eslint-disable-next-line no-var, vars-on-top
    var lastIndex = subjectString.lastIndexOf(searchString, position);
    return lastIndex !== -1 && lastIndex === position;
  };
}

if (!String.prototype.includes) {
  // eslint-disable-next-line func-names, no-extend-native
  String.prototype.includes = function (search, start) {
    if (typeof start !== 'number') {
      start = 0;
    }
    if (start + search.length > this.length) {
      return false;
    } else {
      return this.indexOf(search, start) !== -1;
    }
  };
}

// https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/Array/findIndex

if (!Array.prototype.findIndex) {
  // eslint-disable-next-line no-extend-native
  Object.defineProperty(Array.prototype, 'findIndex', {
    // eslint-disable-next-line func-names
    value: function (predicate) {
      // eslint-disable-next-line strict
      'use strict';
      if (this == null) {
        throw new TypeError('Array.prototype.findIndex called on null or undefined');
      }
      if (typeof predicate !== 'function') {
        throw new TypeError('predicate must be a function');
      }
      // eslint-disable-next-line no-var, vars-on-top
      var list = Object(this);
      // eslint-disable-next-line no-var, vars-on-top, no-bitwise
      var length = list.length >>> 0;
      // eslint-disable-next-line no-var, vars-on-top, prefer-rest-params
      var thisArg = arguments[1];
      // eslint-disable-next-line no-var, vars-on-top
      var value;

      // eslint-disable-next-line no-var, vars-on-top
      for (var i = 0; i < length; i++) {
        value = list[i];
        if (predicate.call(thisArg, value, i, list)) {
          return i;
        }
      }
      return -1;
    },
    enumerable: false,
    configurable: false,
    writable: false,
  });
}

// https://medium.com/@imranshaikh_55168/javascript-array-polyfills-how-to-create-polyfills-in-js-a731072e5090
if (!Array.prototype.some) {
  // eslint-disable-next-line func-names, no-extend-native
  Array.prototype.some = function (callback) {
    for (let i = 0; i < this.length; i++) {
      if (callback(this[i], i, this)) {
        return true;
      }
    }
    return false;
  };
}

// https://developer.mozilla.org/en-US/docs/Web/API/Element/closest#Polyfill
if (window.Element && !Element.prototype.closest) {
  // eslint-disable-next-line func-names, no-extend-native
  Element.prototype.closest = function (s) {
    // eslint-disable-next-line no-var, one-var
    var matches = (this.document || this.ownerDocument).querySelectorAll(s),
      i,
      el = this;
    do {
      i = matches.length;
      // eslint-disable-next-line no-empty
      while (--i >= 0 && matches.item(i) !== el) {}
    } while (i < 0 && (el = el.parentElement));
    return el;
  };
}

if (typeof window.CustomEvent !== 'function') {
  // eslint-disable-next-line no-inner-declarations
  function CustomEvent(event, params) {
    params = params || { bubbles: false, cancelable: false, detail: undefined };
    // eslint-disable-next-line no-var, vars-on-top
    var evt = document.createEvent('CustomEvent');
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  }

  CustomEvent.prototype = window.Event.prototype;

  window.CustomEvent = CustomEvent;
}
