import React, { Component } from 'react';
import { observer } from 'mobx-react';

import { adminFetch } from 'util/fetch';
import { openModal, confirm } from 'common/modal';

import Table from 'ui/Table';

@observer
export default class MerchantTeamDetails extends Component {
  state = {};

  constructor(props) {
    super();
    this.merchantId = props.match.params.id;
  }

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
      <div class="entity-container box">
        <header class="heading">
          Merchant: {this.merchantId} (Team Details)
        </header>
        <div class="box">
          <div class="heading">Users</div>
          <Table items={this.state.users} fields={_getUsersFields()} />
        </div>

        <div class="box">
          <div class="heading">Pending Invitations</div>
          <Table
            items={this.state.pendingInvites}
            fields={_getPendingInvitesFields()}
          />
        </div>
      </div>
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
