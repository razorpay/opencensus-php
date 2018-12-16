import Form from 'ui/Form';
import React, { Component } from 'react';
import AsyncButton from 'ui/AsyncButton';
import { ModalContent } from 'component/Modal';
import Field, { SelectField, SelectMode } from 'ui/Field';
import { splitAndFilter, snakeToTitleCase } from 'common/util';
import { adminPost, adminFetch, adminPatch } from 'common/fetch';
import { notifySuccess, notifyError, openModal, closeModal } from 'common/modal';

const options = {
    requests: [
        'create_feature',
        'create_experiment',
        'get_feature',
        'get_experiment',
        'activate_experiment',
        'terminate_experiment',
    ],
    getRequests: [
        'get_feature',
        'get_experiment',
    ],
    patchRequests: [
        'activate_experiment',
        'terminate_experiment'
    ],
    environments: {
        prod: 'Production',
        beta: 'Stage Beta',
        func: 'QA Func',
        perf: 'QA Perf',
        dev: 'Local dev',
        testing: 'Testing'
    },
    segmentTypes: {
        whitelist: 'Whitelist',
        blacklist: 'Blacklist',
        ramp: 'Ramp',
        contextRamp: 'Context Ramp'
    }
};

export default class RazorxActions extends Component {
static permission = 'manage_razorx_operations';
    static title = 'Send Razorx Request';

    constructor(props) {
        super(props);

        this.state = {
            selectedRequest: 'create_experiment',
            totalSegments: 0,
            segments: {},
            selectedEnvironment: 'dev',
        };
    }

    handleRequestChange = e => {
        this.setState({
            selectedRequest: e.target.value,
        });

        document.getElementById("full-form").reset();
    };

    handleEnvironmentChange = e => {
        this.setState({
            selectedEnvironment: e.target.value,
        });
    };

    handleTypeChange = (e, key) => {
        const segments = {...this.state.segments};
        segments[key] = e.target.value;

        this.setState({ segments });
    };

    handleAddSegment = () => {
        let segments = {...this.state.segments};

        const key = Math.random() * Math.random();
        segments[key] = "whitelist";

        this.setState({segments})
    };

    handleRemoveSegment = (e, key) => {
        let segments = {...this.state.segments};

        delete segments[key];

        this.setState({segments});
    };

    getUrlAndFormBodyForRequest = () => {

        let url = undefined;
        let formBody = undefined;

        switch(this.state.selectedRequest) {
            case 'create_feature': {
                url = 'live/service/razorx?service_path=feature_flags';

                formBody = <div>
                    <Field
                        required
                        label="Name"
                        type="text"
                        name="name"
                        placeholder="Feature flag name"
                    />
                    <Field
                        required
                        label="Description"
                        type="text"
                        name="description"
                        placeholder="Description of the feature flag"
                    />
                    <Field
                        required
                        label="Created By"
                        type="text"
                        name="created_by"
                        placeholder="Admin creating the feature flag (You!)"
                    />
                    <Field
                        required
                        label="Variants"
                        type="text"
                        name="variants"
                        placeholder="Comma separated variant strings (possible outcomes) for the feature flag"
                    />
                </div>;

                break;
            }
            case 'create_experiment': {
                url = 'live/service/razorx?service_path=experiments';

                formBody = <div>
                    <Field
                        required
                        label="Description"
                        type="text"
                        name="description"
                        placeholder="Description of the experiment"
                    />
                    <SelectField
                        required
                        name="environment"
                        label="Environment"
                        value={this.state.selectedEnvironment}
                        // defaultValue="dev"
                        onChange={this.handleEnvironmentChange}
                    >
                        {Object.keys(options.environments).map(key => (
                            <option value={key} key={key}>
                                {options.environments[key]}
                            </option>
                        ))}
                    </SelectField>
                    <SelectMode
                        required
                        defaultValue="test"
                    />
                    <Field
                        required
                        label="Feature Flag Id"
                        type="text"
                        name="feature_id"
                        placeholder="Feature flag id on which the experiment is to be created"
                    />
                    <label>Segments</label>
                    {/*rendering segments*/}
                    {Object.keys(this.state.segments).map(key => (
                        <Segment
                            key={key}
                            id={key}
                            selectedType={this.state.segments[key]}
                            onTypeChange={e => this.handleTypeChange(e, key)}
                            onRemove={e => this.handleRemoveSegment(e, key)}
                        />
                    ))}
                    {/**/}
                    <button onClick={this.handleAddSegment}>Add</button>
                    <Field
                        required
                        label="Created By"
                        type="text"
                        name="created_by"
                        placeholder="Admin creating the experiment (You!)"
                    />
                </div>;

                break;
            }
            case 'get_feature': {
                url = 'live/service/razorx?service_path=feature_flags';

                formBody = <div>
                    <Field
                        required
                        label="Feature ID"
                        type="text"
                        name="feature_id"
                        placeholder="Feature Flag ID"
                    />
                </div>;

                break;
            }
            case 'get_experiment': {
                url = 'live/service/razorx?service_path=experiments';

                formBody = <div>
                    <Field
                        required
                        label="Experiment ID"
                        type="text"
                        name="experiment_id"
                        placeholder="Experiment ID"
                    />
                </div>;

                break;
            }
            case 'activate_experiment': {
                url = 'live/service/razorx?service_path=experiments';

                formBody = <div>
                    <Field
                        required
                        label="Experiment ID"
                        type="text"
                        name="experiment_id"
                        placeholder="Experiment ID"
                    />
                    <Field
                        required
                        label="Activated By"
                        type="text"
                        name="activated_by"
                        placeholder="Admin activating the experiment (You!)"
                    />
                </div>;

                break;
            }
            case 'terminate_experiment': {
                url = 'live/service/razorx?service_path=experiments';

                formBody = <div>
                    <Field
                        required
                        label="Experiment ID"
                        type="text"
                        name="experiment_id"
                        placeholder="Experiment ID"
                    />
                    <Field
                        required
                        label="Terminated By"
                        type="text"
                        name="terminated_by"
                        placeholder="Admin terminating the experiment (You!)"
                    />
                </div>;

                break;
            }
        }

        return {body: formBody, url: url};
    };

    formatRequestUrlIfApplicable = (url, body) => {
        switch (this.state.selectedRequest) {
            case 'get_feature': {
                return url + '/' + body.feature_id;
            }
            case 'get_experiment':{
                return url + '/' + body.experiment_id;
            }
            case 'activate_experiment': {
                return url + '/' + body.experiment_id + '/activate';
            }
            case 'terminate_experiment': {
                return url + '/' + body.experiment_id + '/terminate';
            }
            default: {
                return url;
            }
        }
    };

    formatRequestBody = (body) => {
        switch (this.state.selectedRequest) {
            case 'create_feature': {
                body.variants = splitAndFilter(body.variants.trim(), ',');
                break;
            }
            case 'create_experiment': {
                body.feature_id = parseInt(body.feature_id);
                let seg = '';
                body.segments = [];
                Object.keys(this.state.segments).map(id => {
                    const type = 'type.' + id;
                    const ids = 'ids.' + id;
                    const variant = 'variant.' + id;
                    const weight = 'weight.' + id;

                    const idsArray = body[ids] ? splitAndFilter(body[ids].trim(), ',') : undefined;
                    body.segments.push({
                        'ids': idsArray,
                        'variant': body[variant],
                        'type': body[type],
                        'weight': parseInt(body[weight])
                    });
                    delete body[ids];
                    delete body[variant];
                    delete body[type];
                    delete body[weight];
                });
                break;
            }
            case 'get_feature': {
                body.feature_id = parseInt(body.feature_id);
                break;
            }
            case 'get_experiment': {
                body.experiment_id = parseInt(body.experiment_id);
                break;
            }
        }
        return body;
    };

    adminFetchCall = (url) => {
        return adminFetch(url)
            .then(response => {
                if (response) {
                    notifySuccess('Entity fetched successfully.');
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

    adminPostCall = (url, body) => {
        return adminPost({
            url: url,
            headers: {'Content-Type': 'application/json'},
            data: body,
        }).then(response => {
            if (response) {
                notifySuccess('Entity added successfully.');
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

    adminPatchCall = (url, body) => {
        return adminPatch({
            url: url,
            headers: {'Content-Type': 'application/json'},
            data: body,
        }).then(response => {
            if (response) {
                notifySuccess('Entity modified successfully.');
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

                    <AsyncButton
                        text="OK"
                        class="btn"
                        pendingClass="small spinner"
                        onSubmit={body =>
                            {
                                body = this.formatRequestBody(body);
                                const url = this.formatRequestUrlIfApplicable(urlAndFormBody.url, body);

                                if (options.getRequests.indexOf(this.state.selectedRequest) >= 0) {
                                    return this.adminFetchCall(url);

                                } else if (options.patchRequests.indexOf(this.state.selectedRequest) >= 0) {
                                    return this.adminPatchCall(url, body);
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

class Segment extends Component {

    render () {
        let weight = null;
        let merchantIds = null;

        if (this.props.selectedType === 'whitelist'
            || this.props.selectedType === 'blacklist'
            || this.props.selectedType === 'contextRamp') {
            let name = "ids." + this.props.id;
            merchantIds = <Field
                required
                name={name}
                label="IDs"
                placeholder="Comma separated merchant ids"
                type="text"
            />;
        }

        if (this.props.selectedType === 'ramp'
            || this.props.selectedType === 'contextRamp') {
            const name = "weight." + this.props.id;
            weight = <Field
                required
                name={name}
                label="Weight"
                placeholder="Ramp weight"
                type="text"
            />
        }

        let typeName = "type." + this.props.id;
        let variantName = "variant." + this.props.id;

        let body = (<div>
            <SelectField
                required
                name={typeName}
                label="Type"
                value={this.props.selectedType}
                onChange={(e) => this.props.onTypeChange(e, this.props.id)}
            >
                {Object.keys(options.segmentTypes).map(key => (
                    <option value={key} key={key}>
                        {options.segmentTypes[key]}
                    </option>
                ))}
            </SelectField>
            <Field
                required
                label="Variant"
                type="text"
                name={variantName}
                placeholder="Variant"
            />
            {merchantIds}
            {weight}
            <button onClick={e => this.props.onRemove(e, this.props.id)}>Remove</button>
        </div>);

        return body;
    }
};
