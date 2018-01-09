import React, { Component } from 'react';

import BaseModal from 'ui/BaseModal';
import Table from 'ui/Table';

import { adminFetch } from 'common/fetch';
import { formatDate } from 'common/util';
import { statusPill } from 'common/data';
import { closeModal } from 'common/modal';

export default class ActivationStatusLogs extends Component {
  state = { logs: null };

  componentWillMount() {
    const data = {
      route_name: 'merchant_activation_status_change_log',
      url_params: {
        id: this.props.merchantId,
      },
    };
    return adminFetch(data).then(response => {
      if (response) {
        this.setState({
          logs: response.items,
        });
      } else {
        closeModal();
      }
    });
  }

  fields = () => {
    return [
      ['Created At', item => formatDate(item.created_at)],
      ['Status', item => statusPill(item.name)],
    ];
  };

  render() {
    return (
      <BaseModal header="Activation Status Logs">
        {this.state.logs ? (
          <Table items={this.state.logs} fields={this.fields()} />
        ) : (
          <div class="spinner center" />
        )}
      </BaseModal>
    );
  }
}
