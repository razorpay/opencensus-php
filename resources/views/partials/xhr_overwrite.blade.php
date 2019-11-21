<script type="text/javascript">
    var origOpen = XMLHttpRequest.prototype.open;
    var origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (a, b, c, d, e) {
        if (b.indexOf('hotjar') >= 0) {
            this.send = function (s) {
                try {
                    var x = JSON.parse(s);

                    // Add MID, email, and contact to the message.
                    if (x['response'] && typeof x['response']['message'] !== 'undefined') {
                        if (x['response']['message'] === null) {
                            x['response']['message'] = '';
                        }

                        if (typeof x['response']['message'] === 'string') {
                            x['response']['message'] += '\n\nMID: ' + rzp_user.id;
                            x['response']['message'] += '\nEmail: ' + ((rzp_user.user && rzp_user.user.email) || rzp_user.email);
                            x['response']['message'] += '\nContact: ' + ((rzp_user.user && rzp_user.user.contact_mobile) || rzp_user.contact_mobile);
                            x['response']['message'] += '\nName: ' + rzp_user.business_name;
                        }
                    }



                    if (x['action'] && (x['action'] === 'create_poll_response' || x['action'] === 'update_poll_response')) {
                        if (x['response_content']) {
                            if (typeof x['response_content'] === 'string') {
                                try {

                                    var rc = JSON.parse(x['response_content']);
                                    if (rc['answers'] && rc['answers'].length) {
                                        // Add mid to poll response
                                        rc['answers'][0]['answer'] += ' MID - ' + window.rzp_user.id;
                                        x['response_content'] = JSON.stringify(rc);

                                        var d = {
                                            mid: window.rzp_user.current,
                                            uid: window.rzp_user.user.id,
                                            feedback: null,
                                            rating: null
                                        };
                                        var a = rc['answers'][0];
                                        if (a['question'] === 'How would you rate the new dashboard home page?') {
                                            d.rating = parseInt(a.answer);
                                        }
                                        if (rc['answers'].length > 1) {
                                            a = rc['answers'][1];
                                            if (a['question'] === 'Please suggest how we can make it better.') {
                                            d.feedback = a.answer;
                                            }
                                        }
                                        var xhr = new XMLHttpRequest();
                                        xhr.open('POST', 'https://hooks.zapier.com/hooks/catch/1088429/zk9ygu/', true);
                                        xhr.send(JSON.stringify(d));
                                    }
                                } catch (e) {}
                            }
                        }
                    }
                    try {
                        s = JSON.stringify(x);
                    } catch (stringifyErr) {}
                } catch (e) {}
                origSend.apply(this, [s]);
            }
        }
        // Pass arguments that were passed originally, because the behaviour of xhr.send() changes when the no. of
        // arguments change.
        origOpen.apply(this, arguments);
    }

</script>
