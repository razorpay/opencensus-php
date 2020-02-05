{{-- Helpers --}}
<script>
    'use strict';

    (function(global){
        function evalPaymentType() {
            var formEle = document.querySelector('[data-schemapath="root.payment_type"]').getElementsByClassName('form-group')[0];
            var value = formEle.getElementsByTagName('select')[0].value;

            if(!value) {
                window.RZP.addClass(formEle, 'has-error');
            } else {
                window.RZP.removeClass(formEle, 'has-error');
            }
        }

        function addPaymentTypeValidation() {
            var p = document.createElement('p');
            p.className = 'help-block errormsg';
            p.innerHTML = 'Please select the Payment Type';

            var parentEle = document.querySelector('[data-schemapath="root.payment_type"]').getElementsByClassName('form-group')[0];
            parentEle.append(p);

            editor.watch('root.payment_type', function () {
                evalPaymentType();
            });
        }

        global.evalPaymentType = evalPaymentType;
        global.addPaymentTypeValidation = addPaymentTypeValidation;

    })(window.RZP = window.RZP || {});
</script>

<script>
    (function(global){
        function initCheckout(globalScope, udfData, orderId, amountDue) {
            var data = globalScope.data;

            var paymentPageObj = data.payment_link;
            var merchant = data.merchant;

            // Checkout options
            var options = {
                key: data.key_id,
                payment_link_id: paymentPageObj.id,
                order_id: orderId,
                amount: amountDue,
                notes: udfData,
                handler: function(response) {
                    var amountPaid = amountDue;

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
                    case 'root.waybill_number': defaultMsg = 'Please enter the Waybill Number'; break;
                    case 'root.customer_name': defaultMsg = 'Please enter the Customer Name'; break;
                    case 'root.payment_type': defaultMsg = 'Please select type of payment'; break;
                }

                if (!value) {
                    errorMsg = defaultMsg;
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

        function submitForm(e) {
            var errors = editor.validate();
            console.log(errors);
            var hasError;

            window.RZP.evalPaymentType(); // Just to show error;

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

                    var globalScope = window.RZP_DATA || {};
                    var baseUrl = globalScope.data.base_url;
                    var paymentPageObj = globalScope.data.payment_link;

                    var paymentPageId = paymentPageObj.id;

                    var lineItem = paymentPageObj.payment_page_items[0]; // Only 1 line item
                    var lineItems = [{ amount: amount, payment_page_item_id: lineItem.id }];

                    function onOrderCreation(orderId, amountDue) {
                        window.RZP.initCheckout(globalScope, Object.assign({}, udfData), orderId, amountDue);
                    }

                    window.RZP.createOrder(baseUrl, paymentPageId, lineItems, onOrderCreation);
                }

            }, 10); // If blur happens directly through click on submit btn, so 'has-error' class won't be put until delayed.

            e.preventDefault();
        }

        function addListeners_Validators() {
            window.RZP.getEl('udf_submit_btn').addEventListener('click', submitForm);

            window.RZP.addAmountValidation();
            window.RZP.addAlphaFieldsValidation(['root.customer_name']);
            window.RZP.addPaymentTypeValidation();
        }


        global.initCheckout = initCheckout;
        global.initJSONEditor = initJSONEditor;
        global.submitForm = submitForm;
        global.addListeners_Validators = addListeners_Validators;

    })(window.RZP = window.RZP || {});
</script>
