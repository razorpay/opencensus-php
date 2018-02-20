import React, { Component } from 'react';

import Table from 'ui/Table';

import { adminFetch } from 'common/fetch';
import { formatDate } from 'common/util';
import { statusPill } from 'common/data';

export default class ActivationStatusLogs extends Component {
  state = { logs: [] };

  componentWillMount() {
    return adminFetch(
      `live/merchant/activation/${this.props.merchantId}/status_change_log`
    ).then(response => {
      if (response) {
        this.setState({
          logs: response.items,
        });
      }
    });
  }

  fields = () => {
    return [
      ['Created At', item => formatDate(item.created_at)],
      ['Activation Status', item => statusPill(item.name)],
    ];
  };

  render() {
    return (
      <div class="container" style={{ padding: '20px 0' }}>
        {this.state.logs.length ? (
          <Table items={this.state.logs} fields={this.fields()} />
        ) : (
          <div class="spinner center" />
        )}
      </div>
    );
  }
}
