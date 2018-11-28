import { Component } from 'react';
import { observer } from 'mobx-react';

import { adminFetch, adminPost } from 'common/fetch';
import { closeModal, notifySuccess } from 'common/modal';

import EntityRow from 'ui/EntityRow';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';

import Model from '../../model';

import ConfigDetails from './ConfigDetails';
import DataDetails from './DataDetails';

@observer
export default class CreateMerchantReportConfig extends Component {
  state = {
    configOptions: {
      loading: true,
      data: {},
    },
    configComponents: {
      loading: false,
    },
    values: {},
  };
  constructor(props) {
    super();

    this.model = new Model({
      fetchFn: adminFetch,
      merchantId: props.merchantId,
    });
  }

  componentWillMount() {
    fetchConfigOptions().then(({ file: data }) => {
      this.setState({ configOptions: { loading: false, data } });
    });
  }

  handleSubmitClick = () => {
    const data = { ...this.state.values };
    const template = this.configDetails ? this.configDetails.getValue() : {};

    data.template = {
      ...data.template,
      ...template,
    };

    data.emails = data.emails.split(',');

    // hardcoded default values
    data.created_by = this.props.merchantId;
    data.scheduled = '0';

    return adminPost({
      url: `live_${this.props.merchantId}/reporting/configs`,
      data,
    }).then(response => {
      if (response) {
        notifySuccess('Report Config Added Successfully');
        closeModal();
      }
    });
  };

  handleChangeIn = ({ target }) => {
    const { name, type } = target;
    const values = { ...this.state.values };

    let value = type === 'checkbox' ? target.checked : target.value;
    if (name === 'template.file_meta.header') {
      value = !!Number(value);
    }
    dotToString(name, value, values);

    this.setState({ values });
  };

  handleReportTypeChange = event => {
    const value = event.target.value;

    if (!!value) {
      fetchConfigComponents(event.target.value).then(data => {
        this.setState({ configComponents: { loading: false, data } });
      });
    } else {
      this.setState({ loading: false, data: undefined });
    }
  };

  render() {
    const merchantId = this.props.merchantId;
    const { details } = this.model.merchant;

    return (
      <>
        <div class="box">
          <div class="heading">Merchant Id: {merchantId}</div>
          {Object.keys(details).length ? (
            [
              <EntityRow key={0} label="Merchant Name" value={details.name} />,
              <EntityRow
                key={1}
                label="Registered Email"
                value={details.email}
              />,
            ]
          ) : (
            <div class="spinner center" />
          )}
        </div>

        <div class="box ReportConfig">
          <div class="heading">Create New Config Form</div>
          <Form class="full-span full-elements" onChange={this.handleChangeIn}>
            {Object.keys(details).length ? (
              <DataDetails
                partnerType={details.partner_type}
                configOptions={this.state.configOptions}
                reportEmails={details.transaction_report_email}
                onChange={this.handleChangeIn}
                extension={
                  ((this.state.values.template || {}).file_meta || {}).extension
                }
                onReportTypeChange={this.handleReportTypeChange}
              />
            ) : (
              <div class="spinner center" />
            )}
          </Form>

          <ConfigDetails
            {...this.state.configComponents.data}
            ref={ref => (this.configDetails = ref)}
          />
          <AsyncButton
            text="Create"
            class="btn"
            pendingClass="small spinner"
            onClick={this.handleSubmitClick}
          />
        </div>
      </>
    );
  }
}

const selfServeReportBase = 'live/admin-reporting/';

function fetchConfigOptions() {
  return adminFetch(selfServeReportBase + 'config-options').then(response => {
    if (response) {
      return response;
    }
  });
}

function fetchConfigComponents(field) {
  return adminFetch(`${selfServeReportBase}config-components/${field}`).then(
    response => {
      if (response) {
        return response;
      }
    }
  );
}

function dotToString(path, value, obj) {
  const parts = path.split('.');
  const last = parts.pop();
  while ((part = parts.shift())) {
    if (typeof obj[part] !== 'object') obj[part] = {};
    obj = obj[part];
  }
  obj[last] = value;
  var part;
}
