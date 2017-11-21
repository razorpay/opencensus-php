import React, { Component } from 'react';
import { observer } from 'mobx-react';
import BaseModal from 'ui/BaseModal';

import { adminFetch } from 'util/fetch';

import Table from 'ui/Table';

@observer
export default class TeamDetails extends Component {
  state = {};
  merchantId = props.merchant.details.id;

  componentWillMount() {
    adminFetch({
      route_name: 'invitation_fetch',
      merchant_id: this.merchantId,
    }).then(data => {
      this.setState({
        pendingInvites: data,
      });
    });

    adminFetch({
      route_name: 'merchant_fetch_users',
      url_params: {
        id: this.merchantId,
      },
    }).then(data => {
      this.setState({
        users: data,
      });
    });
  }

  render() {
    return (
      <BaseModal header={`Team Details for merchant ${this.merchantId}`}>
        <strong>Users</strong>
        <Table items={this.state.users} fields={_getUsersFields()} />

        <strong>Pending Invites</strong>
        <Table
          items={this.state.pendingInvites}
          fields={_getPendingInvitesFields()}
        />
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
