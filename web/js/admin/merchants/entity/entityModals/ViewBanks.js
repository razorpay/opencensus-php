import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { ModalContent } from 'component/Modal';

import { adminFetch } from 'common/fetch';

import Table from 'ui/Table';

@observer
export default class BankDetails extends Component {
  state = {};

  componentWillMount() {
    adminFetch(
      `live_${this.props.merchantId}/merchants/${this.props.merchantId}/banks`
    ).then(response => {
      let data = {};
      for (let key in response) {
        data[key] = Object.keys(response[key]).map(index => [
          index,
          response[key][index],
        ]);
      }
      this.setState(data);
    });
  }

  render() {
    return (
      <ModalContent
        header={`Merchant Banks  `}
        className="merchant-banks-modal"
      >
        <div class="container">
          <div class="heading">
            <strong>Disabled Banks</strong>
          </div>
          {!this.state.disabled ? (
            <div class="spinner center" />
          ) : (
            <Table
              header={false}
              items={this.state.disabled}
              fields={_getBankFields()}
            />
          )}
        </div>

        <div class="separate m-t m-b" />

        <div class="container">
          <div className="heading">
            <strong>Enabled Banks</strong>
          </div>
          {!this.state.enabled ? (
            <div class="spinner center" />
          ) : (
            <Table
              header={false}
              items={this.state.enabled}
              fields={_getBankFields()}
            />
          )}
        </div>
      </ModalContent>
    );
  }
}

/* Resources */

function _getBankFields(title = '') {
  return [[title, item => item[1]]];
}
