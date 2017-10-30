import React, { Component } from 'react';
import user, { org } from 'admin/user';
import Duplex from 'ui/Duplex';
import BaseModal from 'ui/BaseModal';
import Field from 'ui/Field';
import Form from 'ui/Form';
import { DataTable } from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';
import { formatDate } from 'util/index';
import { observer } from 'mobx-react';
import { observable, computed } from 'mobx';
import fetch, { adminPost } from 'util/fetch';
import {
  openModal,
  notifyError,
  notifySuccess,
  closeModal,
  confirm,
} from 'common/modal';

class PasswordResetModal extends Component {
  submit(body) {
    return adminPost({
      body,
      route_name: 'admin_change_password',
    })
      .then(response => {
        if (!response) {
          return;
        }

        if (response.success) {
          notifySuccess('Password updated successfully');
          closeModal();
        } else {
          response.errors.map(error => notifyError(error));
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  render() {
    return (
      <Form>
        <Field
          type="password"
          placeholder="Current Password"
          name="old_password"
          label="Current Password"
        />
        <Field
          type="password"
          placeholder="New Password"
          name="password"
          label="New Password"
        />
        <Field
          type="password"
          placeholder="Confirm New Password"
          name="password_confirmation"
          label="Confirm New Password"
        />
        <br />
        <AsyncButton
          class="btn"
          pendingClass="btn spinner"
          onSubmit={this.submit}
          text="Update password"
        />
      </Form>
    );
  }
}

@observer
class ActivityLogModal extends Component {
  @observable log = [];
  @computed
  get str() {
    return this.log.toJSON();
  }

  constructor(props) {
    super();
    this.log.splice(0, this.log.length, ...props.log);
  }

  deleteOtherSessions() {
    return fetch({
      method: 'delete',
      url: '/admin/activity/',
    })
      .then(response => {
        notifySuccess('Sessions deleted successfully');
        closeModal();
        this.log.replace(
          this.log.filter(activity => {
            return activity.current;
          })
        );
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  deleteSession(activity) {
    fetch({
      url: '/admin/activity/' + activity.id,
      method: 'delete',
    })
      .then(response => {
        notifySuccess('Session deleted successfully');
        closeModal();
        this.log.replace(
          this.log.filter(current => {
            return current.id !== activity.id;
          })
        );
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  render() {
    var fields = [
      [
        'Device',
        item =>
          `${item.parsed_user_agent.ua.family}(${item.parsed_user_agent.ua
            .major}.${item.parsed_user_agent.ua.minor}.${item.parsed_user_agent
            .ua.patch}) ${item.parsed_user_agent.os.family}`,
      ],
      ['IP address', item => item.ip_address],
      ['Date/Time', item => item.parsed_last_activity],
      [
        'Action',
        item => {
          return (
            !item.current && (
              <i
                class="i-trash"
                style={{ cursor: 'pointer' }}
                onClick={_ => {
                  confirm(
                    'Are you sure you want to delete this session?',
                    this.deleteSession.bind(this, item),
                    'Ok',
                    'Cancel'
                  );
                }}
              />
            )
          );
        },
      ],
    ];
    return (
      <div>
        <DataTable fields={fields} items={this.log} />
        <button
          onClick={_ => {
            confirm(
              'Are you sure you want to delete all other sessions you are signed in with?"',
              ::this.deleteOtherSessions,
              'Ok',
              'Cancel'
            );
          }}
        >
          Sign out all other sessions
        </button>
      </div>
    );
  }
}

@observer
export default class Profile extends Component {
  showActivityLog() {
    return fetch({
      url: 'admin/activity',
    }).then(log => {
      openModal(
        <BaseModal header="Activity Log">
          <ActivityLogModal log={log} />
        </BaseModal>
      );
    });
  }

  render() {
    var fields = [
      item => item.name && ['Admin Name', item.name],
      item => ['Email Id', item.email],
      item => ['Username', item.username],
      item => item.roles && ['Role', item.roles.join(' ')],
      item => item.created_at && ['Creation Date', formatDate(item.created_at)],
      item => item.employee_code && ['Employee Code', item.employee_code],
      item => item.branch_code && ['Branch Code', item.branch_code],
      item => item.department_code && ['Department Code', item.department_code],
      item => item.location_code && ['Location Code', item.location_code],
      item => item.supervisor_code && ['Supervisor Code', item.supervisor_code],
      item => item.location_code && ['Location Code', item.location_code],
    ];

    return (
      <div class="box">
        <header>Admin Profile</header>
        <Duplex fields={fields} model={user} />
        {org.auth_type === 'password' && (
          <AsyncButton
            class="btn"
            pendingClass="spinner"
            text="Change Password"
            onClick={_ => {
              openModal(
                <BaseModal header="Change Password">
                  <PasswordResetModal />
                </BaseModal>
              );
            }}
          />
        )}
        <AsyncButton
          class="btn"
          pendingClass="spinner"
          text="Show Activity Log"
          onClick={_ => {
            this.showActivityLog();
          }}
        />
      </div>
    );
  }
}
