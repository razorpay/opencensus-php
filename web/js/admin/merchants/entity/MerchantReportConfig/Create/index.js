import { Component } from 'react';
import { observer } from 'mobx-react';

import { adminFetch, adminPost } from 'common/fetch';
import { closeModal, notifySuccess, notifyError } from 'common/modal';
import { isBlank } from 'rzp/utils/rzp-utils';

import EntityRow from 'ui/EntityRow';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';

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
    merchantDetails: {
      loading: true,
      data: {},
    },
    values: {},
  };

  componentWillMount() {
    fetchMerchantDetails(this.props.merchantId).then(data => {
      this.setState({
        merchantDetails: { loading: false, data },
        values: {
          ...this.state.values,
          template: {
            formats: {
              date: 'd-m-Y',
            },
            file_meta: {
              extension: 'csv',
              header: true,
            },
          },
        },
      });
    });

    fetchConfigOptions().then(({ file: data }) => {
      this.setState({
        configOptions: { loading: false, data },
      });
    });
  }

  handleSubmitClick = () => {
    const data = { ...this.state.values };
    const template = this.configDetails ? this.configDetails.getValue() : {};

    data.template = {
      ...data.template,
      ...template,
    };

    data.emails = data.emails ? data.emails.split(',') : [];

    // hardcoded default values
    data.created_by = this.props.merchantId;
    data.scheduled = '0';

    // checking for mandatory fields
    if (
      !data.name ||
      !data.description ||
      !data.type ||
      isBlank(data.template.fields_map)
    ) {
      notifyError(
        'Please fill all mandatory fields and select atleast one column to be present in report'
      );
      return;
    }

    return adminPost({
      url: `live_${this.props.merchantId}/reporting/configs`,
      headers: {
        'Content-Type': 'application/json',
      },
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
    if (
      name === 'template.file_meta.header' ||
      name === 'template.attach_to_email'
    ) {
      value = !!Number(value);
    } else if (name === 'template.referred_accounts') {
      value = value === 'all' ? value : undefined;
    }

    dotToString(name, value, values);

    this.setState({ values });
  };

  handleReportTypeChange = event => {
    const value = event.target.value;
    this.setState({ configComponents: { loading: true } });
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
    const { merchantDetails: details, configOptions } = this.state;

    return (
      <>
        <div class="box">
          <div class="heading">Merchant Id: {merchantId}</div>
          {!details.loading && details.data ? (
            <>
              <EntityRow label="Merchant Name" value={details.data.name} />,
              <EntityRow label="Registered Email" value={details.data.email} />
            </>
          ) : (
            <div class="spinner center" />
          )}
        </div>

        <div class="box ReportConfig">
          <div class="heading">Create New Config Form</div>
          {!details.loading && !configOptions.loading ? (
            <>
              <Form
                class="full-span full-elements"
                onChange={this.handleChangeIn}
              >
                <DataDetails
                  partnerType={details.data.partner_type}
                  configOptions={this.state.configOptions}
                  onChange={this.handleChangeIn}
                  extension={
                    ((this.state.values.template || {}).file_meta || {})
                      .extension
                  }
                  onReportTypeChange={this.handleReportTypeChange}
                />
              </Form>

              <ConfigDetails
                {...this.state.configComponents.data}
                loadingConfigComponents={this.state.configComponents.loading}
                ref={ref => (this.configDetails = ref)}
              />
              <AsyncButton
                text="Create"
                class="btn"
                pendingClass="small spinner"
                onClick={this.handleSubmitClick}
              />
            </>
          ) : (
            <div class="spinner center" />
          )}
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

function fetchMerchantDetails(merchantId) {
  return adminFetch({
    url: 'live/merchants/details',
    headers: {
      'X-Razorpay-Account': merchantId,
    },
  }).then(response => {
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
