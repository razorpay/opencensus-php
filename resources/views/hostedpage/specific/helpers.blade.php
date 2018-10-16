{{-- Common Helpers --}}
<script>
    'use strict';
    (function (global) {

        function amountValidator(value) {
            if(!value || value < 1) {
                return 'Value must be at least ₹1';
            } else if (value.toString() != Number(value).toString()) {
                return 'Please enter valid amount';
            } else {
                return '';
            }
        }

        function evalAmountValidation(e) {
            var ele = e.target || e;
            var errorMsg = amountValidator(ele.value);

            var errorEle = ele.nextElementSibling;
            if (window.RZP.hasClass(errorEle, 'errormsg')) {
                ele.nextElementSibling.innerHTML = errorMsg;
            }

            if (errorMsg) {
                window.RZP.addClass(ele.parentElement, 'has-error');
            } else {
                window.RZP.removeClass(ele.parentElement, 'has-error');
            }

            return errorMsg;
        }

        function addAmountValidation() {
            var ele = document.querySelector('[data-validate="amount"]');

            ele.addEventListener('blur', function(e) {
                evalAmountValidation(e);
            });

            // Amount prettifier
            document.getElementsByName('amount')[0].addEventListener('input', (function() {
                var prettyVal;

                return function(e) {
                    var value = e.target.value;

                    var parentEle = e.target.parentElement;
                    if (window.RZP.hasClass(parentEle, 'has-error')) {
                        evalAmountValidation(e);
                    }

                    if (!value) {
                        e.target.value = '';
                        prettyVal = '';

                        return;
                    }

                    if(amountValidator(value) && !prettyVal) {
                        prettyVal = '';
                    } else {
                        var newValue = value
                            .split('.')
                            .slice(0, 2)
                            .map(function(v, index) {
                                v = v.replace(/\D/g, '');
                                if (index) {
                                    v = v.slice(0, 2);
                                }
                                return v;
                            })
                            .join('.');

                        if (newValue) {
                            prettyVal = newValue > 5000000000 ? 5000000000 : newValue;
                        }
                    }

                    e.target.value = prettyVal;

                };
            })());
        }

        function addAlphaFieldsValidation(elemPaths) {
            for (var i = 0; i < elemPaths.length; i++) {
                var curEle = document.querySelector('[data-schemapath="'+ elemPaths[i] +'"]');

                // Reset the field if copy pasted the value with non-digit characters
                curEle.getElementsByTagName('input')[0].addEventListener('input', function(e) {
                    var value = e.target.value;

                    var nameReg = /^[A-Za-z ]*$/;
                    if (value && !nameReg.test(value)){
                        e.target.value = '';
                    }
                });

                // Not allowing keydown of non-digit characters
                curEle.getElementsByTagName('input')[0].addEventListener('keydown', function(e) {
                    var value = e.which;

                    // Special keys like delete button, Alt, arrow keys etc must work
                    function _isValueIn(value) {
                        var specialKeys = [16, 18, 8, 46, 37, 38, 39, 40, 9]; // Shift, Alt, Ctrl, Cmd, Delete, Tab, etc.
                        var isIn = false;

                        for (var i = 0; i < specialKeys.length; i++) {
                            if (value == specialKeys[i]) {
                                isIn = true;
                                break;
                            }
                        }

                        return isIn;
                    }

                    // Allow Spaces in names. Cmd/Ctrl must be allowed since it might be used for shortcuts like Ctrl + A or Ctrl + L
                    if (value && (value < 97 || value > 122) && (value < 65 || value > 90) && value != 32 && !_isValueIn(value) && !e.ctrlKey && !e.metaKey) {
                        e.preventDefault();
                    }
                });
            }
        }

        function addIntFieldsValidation(elemPaths) {
            for (var i = 0; i < elemPaths.length; i++) {
                var curEle = document.querySelector('[data-schemapath="'+ elemPaths[i] +'"]');

                // Reset the field if copy pasted the value with non-digit characters
                curEle.getElementsByTagName('input')[0].addEventListener('input', function(e) {
                    var value = e.target.value;

                    if (value && value != Number(value)){
                       e.target.value = '';
                    }
                });

                // Not allowing keydown of non-digit characters
                curEle.getElementsByTagName('input')[0].addEventListener('keydown', function(e) {
                    var value = e.which;

                    // Special keys like delete button, Alt, arrow keys etc must work
                    function _isValueIn(value) {
                        var specialKeys = [16, 18, 8, 46, 37, 38, 39, 40, 9]; // Shift, Alt, Ctrl, Cmd, Delete, Tab, etc.
                        var isIn = false;

                        for (var i = 0; i < specialKeys.length; i++) {
                            if (value == specialKeys[i]) {
                                isIn = true;
                                break;
                            }
                        }

                        return isIn;
                    }
                    // Cmd/Ctrl must be allowed since it might be used for shortcuts like Ctrl + A or Ctrl + L
                    if (value && (value < 48 || value > 57) && !_isValueIn(value) && !e.ctrlKey && !e.metaKey) {
                        e.preventDefault();
                    }
                });
            }
        }

        global.evalAmountValidation = evalAmountValidation;
        global.addAlphaFieldsValidation = addAlphaFieldsValidation;
        global.addIntFieldsValidation = addIntFieldsValidation;
        global.addAmountValidation = addAmountValidation;

    })(window.RZP = window.RZP || {});
</script>


<script>
    'use strict';
    (function (global) {
        function removeForm() {
            window.RZP.getEl("udf_submit_btn").style.display='none';
            window.editor.destroy();

            document.getElementsByName('payment-form')[0].style.display = 'none';
            document.getElementsByName('payment-form')[0].innerHTML = '';

            var testModeEle = window.RZP.getEl('testmode-warning');
            if (testModeEle) {
                testModeEle.style.display = 'none';
            }

            if (window.RZP.checkIsDesktop()) {
                document.body.scrollTop = 0;
            } else {
                window.RZP.getEl('form-section').scrollTop = 0;
            }
        }

        function fullPaid(respPaymentId, amountPaid) {
            if (!respPaymentId) {
                return;
            }

            removeForm();

            window.RZP.getEl('success-section').style.display = 'block';

            window.RZP.getEl('success-msg').innerHTML = 'You\'ve successfully paid ₹' + (amountPaid/100).toFixed(2);
            window.RZP.getEl('payment-id').innerHTML = 'Payment ID: ' + respPaymentId;
        }

        function toggleMobileForm() {
            var formEl = window.RZP.getEl('form-section');

            if (window.RZP.hasClass(formEl, 'slideup')) {
                window.RZP.removeClass(formEl, 'slideup');
            } else {
                window.RZP.addClass(formEl, 'slideup');
            }
        }

        function toggleTrimDescription(toTrim) {
            var data = window.RZP_DATA.data,
                desc = data.payment_link.description,
                charLimit, pseudoChar, button = '';

            if (window.RZP.checkIsDesktop()) {
                charLimit = 200;
                pseudoChar = 45;
            } else {
                charLimit = 125;
                pseudoChar = 35;
            }

            if (desc && toTrim) {
                var visLength = 0;
                desc =  desc.trim();

                var descLength = desc.length;

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

                if (desc.length < descLength) {
                    desc += '...';
                    button = '<button class="btn-link showmore" onclick="window.RZP.toggleTrimDescription(false)"> Show More </button>';
                }
            }

            var ele = window.RZP.getEl('payment-for');
            ele && (ele.innerHTML = desc + button);
        }

        global.fullPaid = fullPaid;
        global.toggleMobileForm = toggleMobileForm;
        global.toggleTrimDescription = toggleTrimDescription;

    })(window.RZP = window.RZP || {});
</script>
