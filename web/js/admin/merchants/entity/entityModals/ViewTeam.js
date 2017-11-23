import React, { Component } from 'react';
import { observer } from 'mobx-react';
import BaseModal from 'ui/BaseModal';

import { adminFetch } from 'util/fetch';

import Table from 'ui/Table';

@observer
export default class TeamDetails extends Component {
  state = {};

  componentWillMount() {
    adminFetch({
      route_name: 'invitation_fetch',
      merchant_id: this.props.merchantId,
    }).then(data => {
      this.setState({
        pendingInvites: data,
      });
    });

    adminFetch({
      route_name: 'merchant_fetch_users',
      url_params: {
        id: this.props.merchantId,
      },
    }).then(data => {
      this.setState({
        users: data,
      });
    });
  }

  render() {
    return (
      <BaseModal header={`Team Details for merchant ${this.propsmerchantId}`}>
        <div class="container">
          <div class="heading">Users</div>
          {!this.state.users ? (
            <div class="spinner center" />
          ) : (
            <Table items={this.state.users} fields={_getUsersFields()} />
          )}
        </div>

        <div class="separate m-t m-b" />

        <div class="container">
          <div class="heading">Pending Invites</div>

          {!this.state.pendingInvites ? (
            <div class="spinner center" />
          ) : (
            <Table
              items={this.state.pendingInvites}
              fields={_getPendingInvitesFields()}
            />
          )}
        </div>
      </BaseModal>
    );
  }
}

/* Resources */

function _getUsersFields() {
  return [
    ['Name', item => item.name],
    ['Email', item => item.email],
    ['Role', item => item.role],
  ];
}

function _getPendingInvitesFields() {
  return [['Email', item => item.email], ['Role', item => item.role]];
}
