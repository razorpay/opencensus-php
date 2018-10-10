import React, { Component } from 'react';
import Field from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal, notifyError } from 'common/modal';
import { adminFetch, adminPatch, adminDelete } from 'common/fetch';
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
  static title = 'Set eSigner Gateway for a Merchant';

  constructor(props) {
    super(props);
    this.state = {
      merchantGateways: [],
      isLoading: true,
      overrideEnabled: false,
      overrideGateway: '',
    };
    this.onSubmit = this.onSubmit.bind(this);
    this.deleteOverride = this.deleteOverride.bind(this);
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
        let overrideEnabled = false,
          overrideGateway = '';

        Object.keys(response.auth_gateway).forEach(elem => {
          let gateway = '',
            gatewayKey = response.auth_gateway[elem];

          if (gatewayKey == 'esigner_digio') {
            gateway = 'Digio';
          } else if (gatewayKey == 'esigner_legaldesk') {
            gateway = 'Legal Desk';
          }
          if (elem == 'override') {
            overrideEnabled = true;
            overrideGateway = gatewayKey;
          } else {
            data.push({
              merchantId: elem,
              gateway: gateway,
            });
          }
        });

        this.setState({
          merchantGateways: data,
          isLoading: false,
          overrideEnabled,
          overrideGateway,
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
    if (data.merchantId != 'override' && data.merchantId.length != 14) {
      notifyError('Please enter a valid Merchant ID');
      return;
    }
    if (!data.emandateGateway) {
      notifyError('Please select a eSigner gateway');
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
        if (data.merchantId == 'override') {
          notifySuccess(
            'Override for eSigner gateways is successfully enabled'
          );
        } else {
          notifySuccess(
            'eSigner gateway successfully updated for the merchant'
          );
        }
      }
    });
  }

  deleteOverride() {
    let payload = {
      key: 'merchant_enach_configs',
      path: `auth_gateway.override`,
    };
    adminDelete({
      url: `live/config/key`,
      data: payload,
    }).then(response => {
      if (response) {
        this.fetchGateways();
        notifySuccess('Override for eSigner gateways is successfully disabled');
      }
    });
  }

  render() {
    return (
      <div>
        <div>
          <div class="override-cnt m-b">
            {this.state.overrideEnabled ? (
              <div class="disable-override-cnt">
                <div>
                  Override Enabled -{' '}
                  {this.state.overrideGateway == 'esigner_digio'
                    ? 'Digio'
                    : 'Legal Desk'}
                </div>
                <button class="disable-btn btn" onClick={this.deleteOverride}>
                  Disable Override
                </button>
              </div>
            ) : (
              <div>
                <Form class="full-span">
                  <input
                    type="hidden"
                    name="merchantId"
                    value="override"
                    class="hide"
                  />
                  <SearchableSelectField
                    trackBy="value"
                    label="Enable Override"
                    name="emandateGateway"
                    defaultValue=""
                    isSearchable={false}
                    allowClear={false}
                    options={options}
                  />
                  <AsyncButton
                    text="Save"
                    class="btn "
                    pendingClass="small spinner"
                    onSubmit={this.onSubmit}
                  />
                </Form>
              </div>
            )}
          </div>
          <div class="set-emandate-cnt">
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
                label="eSigner Gateway"
                name="emandateGateway"
                defaultValue=""
                isSearchable={false}
                allowClear={false}
                options={options}
                required
              />
              <div class="form-action">
                <AsyncButton
                  text="Save"
                  class="btn"
                  pendingClass="small spinner"
                  onSubmit={this.onSubmit}
                />
              </div>
            </Form>
          </div>
        </div>
        <div class="gateways-container">
          <h2>eSigner Gateways</h2>
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
