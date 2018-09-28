import React, { Component } from 'react';
import Field from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal, notifyError } from 'common/modal';
import { adminFetch, adminPatch } from 'common/fetch';
import Table from 'ui/Table';
import { SearchableSelectField } from 'ui/Field';

const options = [
  {
    name: '--Select--',
    value: '',
  },
  {
    name: 'Digio',
    value: 'esigner_digio',
  },
  {
    name: 'Legal Desk',
    value: 'esigner_legaldesk',
  },
];

export default class SetEmandateGateway extends Component {
  static title = 'Set Emandate Gateway for a Merchant';

  constructor(props) {
    super(props);
    this.state = {
      merchantGateways: [],
      isLoading: true,
    };
    this.onSubmit = this.onSubmit.bind(this);
    this.fields = [
      [<span>Merchant ID</span>, item => item.merchantId],
      [<span>Gateway</span>, item => item.gateway],
    ];
  }

  componentDidMount() {
    this.fetchGateways();
  }

  fetchGateways() {
    this.setState({
      isLoading: true,
    });

    adminFetch(`live/config/key?key=merchant_enach_configs`).then(response => {
      if (response) {
        const data = [];

        Object.keys(response.auth_gateway).forEach(elem => {
          let gateway = '',
            gatewayKey = response.auth_gateway[elem];

          if (gatewayKey == 'esigner_digio') {
            gateway = 'Digio';
          } else if (gatewayKey == 'esigner_legaldesk') {
            gateway = 'Legal Desk';
          }
          data.push({
            merchantId: elem,
            gateway: gateway,
          });
        });

        this.setState({
          merchantGateways: data,
          isLoading: false,
        });
      } else {
        this.setState({
          isLoading: false,
        });
      }
    });
  }

  onSubmit(data) {
    if (!data.merchantId) {
      notifyError('Merchant ID is mandatory');
      return;
    }
    if (!data.emandateGateway) {
      notifyError('Please select a emandate gateway');
      return;
    }

    let payload = {
      key: 'merchant_enach_configs',
      path: `auth_gateway.${data.merchantId}`,
      value: data.emandateGateway,
    };

    return adminPatch({
      url: `live/config/key`,
      data: payload,
    }).then(response => {
      if (response) {
        this.fetchGateways();
        notifySuccess('Emandate gateway successfully updated for the merchant');
      }
    });
  }

  render() {
    return (
      <div>
        <div>
          <Form class="full-span">
            <Field
              label="Merchant ID"
              placeholder="Enter Merchant ID"
              name="merchantId"
              type="text"
              required
            />
            <SearchableSelectField
              trackBy="value"
              label="Emandate Gateway"
              name="emandateGateway"
              defaultValue=""
              isSearchable={false}
              allowClear={false}
              options={options}
              required
            />
            <br />
            <AsyncButton
              text="Save"
              class="btn"
              pendingClass="small spinner"
              onSubmit={this.onSubmit}
            />
          </Form>
        </div>
        <div class="gateways-container">
          <h2>Emandate Gateways</h2>
          {this.state.isLoading ? (
            <div class="spinner center" />
          ) : (
            <Table items={this.state.merchantGateways} fields={this.fields} />
          )}
        </div>
      </div>
    );
  }
}
