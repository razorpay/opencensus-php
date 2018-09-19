{{-- Utilities --}}
<script>
    'use strict';
    (function (global) {

        function initAnalytics() {
            analytics.init(['ga', 'hotjar'], window.location.hostname.indexOf('razorpay.com') < 0);
            analytics.track('ga', 'pageview');
        }

        function checkIsDesktop() {
            var maxMobileWidth = {!!utf8_json_encode($max_mobile_width)!!};

            var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
            return width > maxMobileWidth;
        }

        function hasClass(ele ,cls) {
            return !!ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
        }

        function addClass(ele, cls) {
            if (!hasClass(ele, cls)) ele.className += " " + cls;
        }

        function removeClass(ele, cls) {
            if (hasClass(ele, cls)) {
                var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
                ele.className=ele.className.replace(reg,' ');
            }
        }

        function removeElemsWithClass(cls) {
            var elems = document.getElementsByClassName(cls);

            for (var i = 0; i < elems.length; i++) {
                elems[i].innerHTML = ''; // To remove common elements to coexist in both views
                elems[i].style.display = 'none'; // To remove common elements to coexist in both views
            }
        }

        function getEl(id) {
            return document.getElementById(id);
        }

        function cleanHTML() {
            // Show content according to width
            if (checkIsDesktop()) {
                getEl('mobile-container').innerHTML = '';
                getEl('desktop-container').style.display = 'block';

                removeElemsWithClass('mobile-el');
            } else {

                getEl('desktop-container').innerHTML = '';
                getEl('mobile-container').style.display = 'block';

                document.body.style.overflow = 'hidden';

                var browserHeight = document.documentElement.clientHeight;

                getEl('mobile-container').style['min-height'] = browserHeight + 'px';
                document.querySelector('#mobile-container .content').style['height'] = browserHeight + 'px';
                document.querySelector('#mobile-container #form-section').style['height'] = browserHeight + 'px';

                removeElemsWithClass('desktop-el');

                /* Handle Android back btn */

                var initialLoad;
                var hash = window.location.hash;

                if (hash) {
                    window.location.hash = ''; // Remove any hash. Page must load with description.
                    initialLoad = true;
                }

                window.onhashchange = function(e) {
                    if (initialLoad) {
                        initialLoad = false;
                    } else {
                        window.RZP.toggleMobileForm();
                    }
                }
            }
        }

        function easeInOutQuad (t, b, c, d) {
            t /= d/2;
            if (t < 1) return c/2*t*t + b;
            t--;
            return -c/2 * (t*(t-2) - 1) + b;
        }

        function scrollTo(element, toEle, duration) {
            if (!element || !toEle) {
                return;
            }

            var viewportOffset = toEle.getBoundingClientRect();
            var to = viewportOffset.top;

            var start = element.scrollTop,
                change = to - start,
                currentTime = 0,
                increment = 20;

            duration = duration || 300;

            var animateScroll = function(){
                currentTime += increment;
                var val = easeInOutQuad(currentTime, start, change, duration);
                element.scrollTop = val;

                if(currentTime < duration) {
                    window.setTimeout(animateScroll, increment);
                }
            };

            animateScroll();
        }


        global.initAnalytics = initAnalytics;
        global.checkIsDesktop = checkIsDesktop;
        global.hasClass = hasClass;
        global.addClass = addClass;
        global.removeClass = removeClass;
        global.cleanHTML = cleanHTML;
        global.easeInOutQuad = easeInOutQuad;
        global.scrollTo = scrollTo;
        global.getEl = getEl;


    })(window.RZP = window.RZP || {});
</script>

<script src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="window.RZP.initAnalytics()" async></script>

<script>
    (function (globalScope) {

        var data = {!!utf8_json_encode($data)!!};

        function forEach (dict, cb) {

            dict = dict || {};

            if (typeof dict !== "object" || typeof cb !== "function") {

                return dict;
            }

            var key, value;

            for (key in dict) {

                if (!dict.hasOwnProperty(key)) {

                    continue;
                }

                value = dict[key];
                cb.apply(value, [value, key, dict]);
            }

            return dict;
        }

        function parseQuery(qstr) {

            var query = {};

            var a = (qstr[0] === '?' ? qstr.substr(1) : qstr).split('&'), i, b;

            for (i = 0; i < a.length; i++) {

                b = a[i].split('=');
                query[decodeURIComponent(b[0])] = decodeURIComponent(b[1] || '');
            }

            return query;
        }

        function createHiddenInput (key, value) {

            var input = document.createElement("input");

            input.type  = "hidden";
            input.name  = key;
            input.value = value;

            return input;
        }

        function hasRedirect () {

            return data.payment_link &&
                data.payment_link.callback_url &&
                data.payment_link.callback_method;
        }

        function redirectToCallback (callbackUrl,
                                     callbackMethod,
                                     requestParams) {

            document.body.className = ([document.body.className,
                "paid",
                "has-redirect"]).join(" ");

            var form   = document.createElement("form"),
                method = callbackMethod.toUpperCase(),
                input, key;

            form.method = method;
            form.action = callbackUrl;

            forEach(requestParams, function (value, key) {

                form.appendChild(createHiddenInput(key, value));
            });

            var urlParamRegex = /^[^#]+\?([^#]+)/,
                matches       = callbackUrl.match(urlParamRegex),
                queryParams;

            if (method === "GET" && matches) {

                queryParams = matches[1];

                if (queryParams.length > 0) {

                    queryParams = parseQuery(queryParams);

                    forEach(queryParams, function (value, key) {

                        form.appendChild(createHiddenInput(key, value));
                    });
                }
            }

            document.body.appendChild(form);

            form.submit();
        }

        globalScope.data               = data;
        globalScope.hasRedirect        = hasRedirect;
        globalScope.redirectToCallback = redirectToCallback;
    }(window.RZP_DATA = window.RZP_DATA || {}));
</script>

{{-- Polyfills --}}
<script>
    (function() {
        // Array.isArray polyfill
        if(!Array.isArray) {
            Array.isArray = function(arg) {
                return Object.prototype.toString.call(arg) === '[object Array]';
            };
        }

        // Object.assign polyfill
        if (typeof Object.assign != 'function') {
            Object.assign = function(target, varArgs) { // .length of function is 2
                'use strict';
                if (target == null) { // TypeError if undefined or null
                    throw new TypeError('Cannot convert undefined or null to object');
                }

                var to = Object(target);

                for (var index = 1; index < arguments.length; index++) {
                    var nextSource = arguments[index];

                    if (nextSource != null) { // Skip over if undefined or null
                        for (var nextKey in nextSource) {
                            // Avoid bugs when hasOwnProperty is shadowed
                            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                                to[nextKey] = nextSource[nextKey];
                            }
                        }
                    }
                }
                return to;
            };
        }
    }());

    // Polyfill for Node.append

    // Source: https://github.com/jserz/js_piece/blob/master/DOM/ParentNode/append()/append().md
    (function (arr) {
      arr.forEach(function (item) {
        if (item.hasOwnProperty('append')) {
          return;
        }
        Object.defineProperty(item, 'append', {
          configurable: true,
          enumerable: true,
          writable: true,
          value: function append() {
            var argArr = Array.prototype.slice.call(arguments),
              docFrag = document.createDocumentFragment();

            argArr.forEach(function (argItem) {
              var isNode = argItem instanceof Node;
              docFrag.appendChild(isNode ? argItem : document.createTextNode(String(argItem)));
            });

            this.appendChild(docFrag);
          }
        });
      });
    })([Element.prototype, Document.prototype, DocumentFragment.prototype]);

</script>
