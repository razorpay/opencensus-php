import Form from 'ui/Form';
import React, { Component } from 'react';
import AsyncButton from 'ui/AsyncButton';
import { ModalContent } from 'component/Modal';
import { adminPost, adminPut } from 'common/fetch';
import Field, { TextAreaField, SelectField } from 'ui/Field';
import { splitAndFilter, snakeToTitleCase } from 'common/util';
import { notifySuccess, notifyError, openModal, closeModal } from 'common/modal';

const options = {
    requests: [
        'assign_schedule',
        'assign_methods',
        'assign_pricing',
        'assign_tag',
    ],

    putRequests: [
        'assign_methods',
    ],

    postRequests: [
        'assign_schedule',
        'assign_pricing',
        'assign_tag',
    ],

    methods: [
        'netbanking',
        'amex',
        'paytm',
        'payzapp',
        'payumoney',
        'airtelmoney',
        'amazonpay',
        'openwallet',
        'olamoney',
        'mobikwik',
        'freecharge',
        'jiomoney',
        'sbibuddy',
        'emi',
        'credit_card',
        'debit_card',
        'upi',
        'aeps',
        'emandate',
        'mpesa',
        'bank_transfer',
        'cardless_emi',
    ]
};

export default class MerchantAccountConfig extends Component {

    static title = 'Edit Merchant Account Settings';

    constructor(props) {
        super(props);

        this.state = {
            selectedRequest: 'assign_schedule',
        };
    }

    handleRequestChange = e => {
        this.setState({
            selectedRequest: e.target.value,
        });

        document.getElementById("full-form").reset();
    };

    getUrlAndFormBodyForRequest = () => {

        let url;
        let formBody;

        switch(this.state.selectedRequest) {
            case 'assign_schedule': {
                url = 'live/merchants/schedules/bulk';

                formBody = (
                    <div>
                        <Field required label="Schedule ID" type="text" name="schedule_id" />
                        <Field
                            required
                            label="Schedule Type"
                            type="text"
                            value="settlement"
                            name="schedule_type"
                            readOnly={true}
                        />
                    </div>
                );

                break;
            }
            case 'assign_methods': {
                url = 'live/methods/bulkupdate';

                formBody = <div>
                    {options.methods.map(method => (
                        <SelectField
                            name={method}
                            label={snakeToTitleCase(method)}
                            defaultValue="none"
                            key={method}
                        >
                            {/* -1 is to remove the method key from payload, 1 to add the method and 0 to remove it */}
                            <option value="-1"></option>
                            <option value="1">Add</option>
                            <option value="0">Remove</option>
                            {/**/}
                        </SelectField>
                    ))}

                </div>;

                break;
            }
            case 'assign_pricing': {
                url = 'live/merchants/pricing/bulk';

                formBody = <div>
                    <Field required label="Pricing ID" type="text" name="pricing_plan_id" />
                </div>;

                break;
            }
            case 'assign_tag': {
                url = 'live/merchants/tags/bulk';

                formBody = <div>
                    <Field required label="Tag Name" type="text" name="name" />
                    <SelectField
                        name="action"
                        label="Action"
                        defaultValue="insert"
                    >
                        <option value="insert">Insert</option>
                        <option value="delete">Delete</option>
                    </SelectField>

                </div>;

                break;
            }
        }

        return {body: formBody, url: url};
    };

    adminPostCall = (url, body) => {
        return adminPost({
            url: url,
            headers: {'Content-Type': 'application/json'},
            data: body,
        }).then(response => {
            if (response) {
                notifySuccess('Request executed successfully');
                openModal(
                    <ModalContent header="Api Response:" noPadding>
                        <div class="code" style={{ width: '650px' }}>
                            {JSON.stringify(response, null, 4)}}
                        </div>
                    </ModalContent>
                );
            }
        });
    };

    adminPutCall = (url, body) => {
        return adminPut({
            url: url,
            headers: {'Content-Type': 'application/json'},
            data: body,
        }).then(response => {
            if (response) {
                notifySuccess('Request executed successfully');
                openModal(
                    <ModalContent header="Api Response:" noPadding>
                        <div class="code" style={{ width: '650px' }}>
                            {JSON.stringify(response, null, 4)}}
                        </div>
                    </ModalContent>
                );
            }
        });
    };

    formatRequestBody = (body) => {
        switch (this.state.selectedRequest) {
            case 'assign_schedule': {
                body.schedule = {
                    type: body.schedule_type,
                    schedule_id: body.schedule_id,
                };
                delete body.schedule_type;
                delete body.schedule_id;
                body.merchant_ids = splitAndFilter(body.merchant_ids, ',');
                break;
            }
            case 'assign_methods': {
                body.methods = {};
                options.methods.map(method => {
                    if (body[method] !== '-1') {
                        body.methods[method] = body[method];
                    }
                    delete body[method];
                });

                body.merchants = splitAndFilter(body.merchant_ids, ',');
                delete body.merchant_ids;
                break;
            }
            case 'assign_pricing':
            case 'assign_tag': {
                body.merchant_ids = splitAndFilter(body.merchant_ids, ',');
                break;
            }
        }
        return body;
    };

    render() {
        let urlAndFormBody = this.getUrlAndFormBodyForRequest();

        return (
        <div>
            <div class="field multi">
                <label>Request</label>
                <select
                    required
                    name="request"
                    value={this.state.selectedRequest}
                    onChange={this.handleRequestChange}
                >
                    {options.requests.map(value => (
                        <option value={value} key={value}>
                            {snakeToTitleCase(value)}
                        </option>
                    ))}
                </select>
            </div>
            <Form class="full-span full-elements" id="full-form">
                {urlAndFormBody.body}

                <TextAreaField
                    required
                    name="merchant_ids"
                    label="Merchant IDs"
                    placeholder="Comma separated merchant ids"
                    type="text"
                />

                <AsyncButton
                    text="OK"
                    class="btn"
                    pendingClass="small spinner"
                    onSubmit={body =>
                    {
                        console.log(body);
                        body = this.formatRequestBody(body);

                        const url = urlAndFormBody.url;

                        if (options.putRequests.indexOf(this.state.selectedRequest) >= 0) {
                            return this.adminPutCall(url, body);
                        } else {
                            return this.adminPostCall(url, body);
                        }
                    }
                    }
                />
            </Form>
        </div>
        );
    }
};
