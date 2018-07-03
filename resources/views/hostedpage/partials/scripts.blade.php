<script>
    function initAnalytics() {
        analytics.init(['ga', 'hotjar'], window.location.hostname.indexOf('razorpay.com') < 0);
        analytics.track('ga', 'pageview');
    }

    function checkIsDesktop() {
        var maxMobileWidth = {!!utf8_json_encode($max_mobile_width)!!};

        var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
        return width > maxMobileWidth;
    }

    function cleanHTML() {
        // Show content according to width
        if (checkIsDesktop()) {
            document.getElementById('mobile-container').innerHTML = '';
            document.getElementById('desktop-container').style.display = 'block';
        } else {

            document.getElementById('desktop-container').innerHTML = '';
            document.getElementById('mobile-container').style.display = 'block';

            document.body.style.overflow = 'hidden';
        }
    }

    function removeForm() {
        document.getElementById("udf_submit_btn").style.display='none';
        editor.destroy();

        document.getElementsByName('payment-form')[0].style.display = 'none';
        document.getElementsByName('payment-form')[0].innerHTML = '';
    }

    function hasClass(ele,cls) {
        return !!ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
    }

    function addClass(ele,cls) {
        if (!hasClass(ele,cls)) ele.className += " "+cls;
    }

    function removeClass(ele,cls) {
        if (hasClass(ele,cls)) {
            var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
            ele.className=ele.className.replace(reg,' ');
        }
    }

    function scrollToMobileForm() {
        var formEl = document.getElementById('form-section');
        addClass(formEl, 'slideup')
    }

    function toggleTrimDescription(toTrim) {
        var data = window.RZP_DATA.data,
            desc = data.payment_link.description,
            charLimit, pseudoChar, button = '';

        if (checkIsDesktop()) {
            charLimit = 200;
            pseudoChar = 45;
        } else {
            charLimit = 125;
            pseudoChar = 35;
        }

        if (desc && toTrim) {
            var visLength = 0;

            var i = 0;
            for (; i < desc.length ; i++) {
                if (desc[i] === '\n') {
                    visLength += pseudoChar;
                } else {
                    visLength++;
                }

                if (visLength > charLimit) {
                    i = i - 1;
                    break;
                }
            }

            desc= desc.substr(0, i + 1);
            desc =  desc.trim();
            desc += '...';
            button = '<button class="btn-link showmore" onclick="toggleTrimDescription(false)"> Show More </button>';
        }

        var ele = document.getElementById('payment-for');
        ele && (ele.innerHTML = desc + button);
    }
</script>

<script src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="initAnalytics()" async></script>

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