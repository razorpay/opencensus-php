import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { ModalContent } from 'component/Modal';

import fetch, { adminFetch } from 'common/fetch';
import { openModal } from 'common/modal';
import user from 'admin/user';

import Table from 'ui/Table';

import UserResetPassword from './UserResetPassword';

@observer
export default class TeamDetails extends Component {
  state = {};

  componentWillMount() {
    adminFetch(`live_${this.props.merchantId}/invitations`).then(data => {
      this.setState({
        pendingInvites: data,
      });
    });

    return fetch({
      url: `/admin/api/live_${this.props.merchantId}/merchants-users`,
    }).then(data => {
      this.setState({
        users: data,
      });
    });
  }

  openPasswordResetModal = member => {
    openModal(
      <UserResetPassword member={member} merchantId={this.props.merchantId} />
    );
  };

  _getUsersFields = () => {
    let fields = [
      ['Name', item => item.name],
      ['Email', item => item.email],
      ['Role', item => item.role],
    ];

    if (user.permissions.indexOf('user_password_reset') > -1) {
      fields.push([
        '',
        item => (
          <span
            class="link danger"
            onClick={this.openPasswordResetModal.bind(this, item)}
          >
            Reset Password
          </span>
        ),
      ]);
    }

    return fields;
  };

  render() {
    return (
      <ModalContent
        header={`Team Details for merchant ${this.props.merchantId}`}
      >
        <div class="container">
          <div class="heading">Users</div>
          {!this.state.users ? (
            <div class="spinner center" />
          ) : (
            <Table items={this.state.users} fields={this._getUsersFields()} />
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
      </ModalContent>
    );
  }
}

/* Resources */

function _getPendingInvitesFields() {
  return [['Email', item => item.email], ['Role', item => item.role]];
}
