{{-- Helpers --}}
<script>
    'use strict';

    (function(global){
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

                        return;
                    }

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

                    e.target.value = prettyVal;

                };
            })());
        }
        function addIntFieldsValidation() {
            var elements = ['root.customer_id', 'root.job_number'];

            for (var i = 0; i < elements.length; i++) {
                var curEle = document.querySelector('[data-schemapath="'+ elements[i] +'"]');

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

            var ele = document.querySelector('[data-validate="amount"]');

            ele.addEventListener('blur', function(e) {
                evalAmountValidation(e);
            });

            // Amount prettifier
            document.getElementsByName('amount')[0].addEventListener('input', (function() {
                var prettyVal = '';

                return function(e) {
                    var value = e.target.value;

                    var parentEle = e.target.parentElement;
                    if (window.RZP.hasClass(parentEle, 'has-error')) {
                        evalAmountValidation(e);
                    }

                    if (!value) {
                        e.target.value = '';

                        return;
                    }

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

                    e.target.value = prettyVal;

                };
            })());
        }

        function evalLocation(value) {
            var formEle = document.querySelector('[data-schemapath="root.location"]').getElementsByClassName('form-group')[0];
            var value = formEle.getElementsByTagName('select')[0].value;

            if(!value) {
                window.RZP.addClass(formEle, 'has-error');
            } else {
                window.RZP.removeClass(formEle, 'has-error');
            }
        }

        function addLocationValidation() {
            var p = document.createElement('p');
            p.className = 'help-block errormsg';
            p.innerHTML = 'Please select a location';

            var parentEle = document.querySelector('[data-schemapath="root.location"]').getElementsByClassName('form-group')[0];
            parentEle.append(p);

            editor.watch('root.location',function(e) {
                evalLocation();
            });
        }

        function evalServiceType() {
            var formEle = document.querySelector('[data-schemapath="root.service_type"]').getElementsByClassName('form-group')[0];
            var value = formEle.getElementsByTagName('select')[0].value;

            if(!value) {
                window.RZP.addClass(formEle, 'has-error');
            } else {
                window.RZP.removeClass(formEle, 'has-error');
            }
        }

        function addServiceTypeValidation() {
            var p = document.createElement('p');
            p.className = 'help-block errormsg';
            p.innerHTML = 'Please select type of service';

            var parentEle = document.querySelector('[data-schemapath="root.service_type"]').getElementsByClassName('form-group')[0];
            parentEle.append(p);

            editor.watch('root.service_type', function () {
                evalServiceType();
            });
        }

        global.evalAmountValidation = evalAmountValidation;
        global.addIntFieldsValidation = addIntFieldsValidation;
        global.addAmountValidation = addAmountValidation;
        global.evalLocation = evalLocation;
        global.addLocationValidation = addLocationValidation;
        global.evalServiceType = evalServiceType;
        global.addServiceTypeValidation = addServiceTypeValidation;

    })(window.RZP = window.RZP || {});
</script>

<script>
    (function(global){
        function initCheckout(globalScope, udfData) {
            var data = globalScope.data;

            var paymentPageObj = data.payment_link;
            var merchant = data.merchant;

            // Checkout options
            var options = {
                key: data.key_id,
                payment_link_id: paymentPageObj.id,
                amount: udfData.amount,
                notes: udfData,
                handler: function(response) {
                    var amountPaid = udfData.amount;

                    if (globalScope.hasRedirect()) {

                        return globalScope.redirectToCallback(
                            data.payment_link.callback_url,
                            data.payment_link.callback_method,
                            response
                        );
                    }

                    if (window.ga && window.ga.length) {
                        var sessionTDiff = (new Date()).getTime() - window.t0;
                        var paymentSuccessAction = 'Payment Successful';

                        window.ga('send', 'event', 'Payment Page Hosted', paymentSuccessAction, 'Session Duration(s)' , Math.floor(sessionTDiff/1000), {
                            hitCallback: function() {
                                return window.RZP.fullPaid(response.razorpay_payment_id, amountPaid); // To display the latest payment id
                            }
                        });
                    } else {
                        return window.RZP.fullPaid(response.razorpay_payment_id, amountPaid); // To display the latest payment id
                    }
                },
                theme: {
                },
                modal: {
                    confirm_close: true,
                    escape: false
                }
            };

            options.name = data.merchant.name;
            options.theme.color = merchant.brand_color || '#168AFA';
            options.currency = 'INR';

            options.image = merchant.image;

            var razorpay;
            razorpay = window.razorpay = Razorpay(options);
            razorpay.open();
        };

        function initJSONEditor() {
            if (!JSONEditor) {
                console.log('Network error has occured. Please reload the page to continue.');

                return;
            }

            JSONEditor.defaults.languages.en.error_required = "";

            // Custom validators must return an array of errors or an empty array if valid
            JSONEditor.defaults.custom_validators.push(function(schema, value, path) {
                var errors = [];
                var defaultMsg;
                var errorMsg;

                // Default errors;
                switch(path) {
                    case 'root.customer_name': defaultMsg = 'Please enter customer name'; break;
                    case 'root.invoice_number': defaultMsg = 'Please enter invoice number'; break;
                    case 'root.job_number': defaultMsg = 'Please enter job/quotation number'; break;
                    case 'root.service_type': defaultMsg = 'Please select type of service'; break;
                    case 'root.location': defaultMsg = 'Please select a location'; break;
                }

                if (!value) {
                    errorMsg = defaultMsg;
                } else {
                    if (schema.kind === 'integer') {
                        if (value != parseInt(value)) {
                            errorMsg = 'Please enter valid number';
                        }
                    }
                }

                if(errorMsg) {
                    // Errors must be an object with `path`, `property`, and `message`
                    errors.push({
                        path: path,
                        property: 'format',
                        message: errorMsg
                    });
                }

                return errors;
            });


            var element = window.RZP.getEl('udf_container');
            var editor = new JSONEditor(element, {
                form_name_root: "",
                no_additional_properties: true,
                disable_properties: true,
                disable_edit_json: true,
                disable_collapse: true,
                disable_array_reorder: true,
                disable_array_delete: true,
                disable_array_add: true,
                theme: "bootstrap3",
                schema: {!! $udf_schema !!},
                required_by_default: true
            });

            return editor;
        }

        function submitForm(btn) {
            var errors = editor.validate();
            console.log(errors);
            var hasError;

            window.RZP.evalServiceType(); // Just to show error;
            window.RZP.evalLocation(); // Just to show error;

            if (errors.length) {
                editor.options.show_errors = "always";
                editor.onChange(); // Fire a change event to force revalidation
                hasError = true;
            }

            var amountEl = document.getElementsByName('amount')[0];

            hasError = hasError || !!window.RZP.evalAmountValidation(amountEl) || !!document.getElementsByClassName('has-error').length; // Check existing errors

            var udfData = editor.getValue();
            var amount = amountEl.value;

            // Sanity check
            var schema = {!! $udf_schema !!};
            if (!hasError && schema && schema.required) {
                for (var i = 0; i < schema.required.length; i++) {
                    var val = udfData[i];

                    if (val === '' || val === null) {
                        hasError = true;
                        break;
                    }
                }
            }

            window.setTimeout(function() {
                if (hasError) {
                    var errorEle = document.getElementsByClassName('has-error')[0];

                    if (!errorEle) {
                        errorEle = document.querySelector('[data-schemapath="'+ errors[0].path +'"]');
                    }

                    var parentEle;
                    if (window.RZP.checkIsDesktop()) {
                        parentEle = document.body;
                    } else {
                        parentEle = window.RZP.getEl('form-section');
                    }

                    window.RZP.scrollTo(parentEle, errorEle, 300);
                } else {
                    amount = parseInt(amount * 100);
                    window.RZP.initCheckout(window.RZP_DATA = window.RZP_DATA || {}, Object.assign({}, udfData, {amount: amount}));
                }

            }, 10); // If blur happens directly through click on submit btn, so 'has-error' class won't be put until delayed.
        }

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

        function addListeners_Validators() {
            window.RZP.getEl('udf_submit_btn').addEventListener('click', submitForm);

            window.RZP.addAmountValidation();
            window.RZP.addIntFieldsValidation();
            window.RZP.addServiceTypeValidation();
            window.RZP.addLocationValidation();
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


        global.initCheckout = initCheckout;
        global.initJSONEditor = initJSONEditor;
        global.submitForm = submitForm;
        global.addListeners_Validators = addListeners_Validators;
        global.fullPaid = fullPaid;
        global.toggleMobileForm = toggleMobileForm;
        global.toggleTrimDescription = toggleTrimDescription;

    })(window.RZP = window.RZP || {});
</script>
