import React, { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import AsyncButton from 'react-async-button';
import Header from 'rzp/ui/Header';
import Time from 'rzp/ui/Time';
import ModalDialog from 'rzp/ui/ModalDialog';
import PasswordForm from 'merchant/components/Profile/PasswordForm';
import MerchantForm from 'merchant/components/Profile/MerchantForm';

import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as ProfileActions from 'merchant/modules/profile';

@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  { ...ProfileActions, ...NotificationActions, ...ModalActions }
)
export default class Profile extends Component {
  componentWillMount() {
    this.props.fetchBankInfoAndInvitations().catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });

    this.refreshUser(); // Analyze user object in props
  }

  refreshUser() {
    const { user } = this.props;
    let hasMerchant = false;

    console.log(this.props);
    console.log(user.user.merchants);
    // Does the user have an associated merchant account
    for (let i in user.user.merchants) {
      var merchant = user.user.merchants[i];
      if (merchant.email.toLowerCase() === user.user.email.toLowerCase()) {
        hasMerchant = true;
      }
    }

    this.setState({
      merchantCount: Object.keys(user.merchants).length,
      loggedInUser: user.user,
      hasMerchant,
    });

    // Show notification if user not assiciated with active merchant account
    if (!user.current) {
      this.props.showNotification({
        type: 'error',
        message: 'Your user account is not associated at present with any active merchant account.',
      });
    }
  }

  // Check how to show error
  getPendingInvitations() {
    this.props.fetchPendingInvitations().catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  }

  updateInvitation(type, invite) {
    let message;

    switch (type) {
      case 'accept':
        message = 'You have accepted the invite.';
        break;
      case 'reject':
        message = 'You have rejected the invite.';
        break;
      default:
        return;
    }

    this.props
      .updateInvitation(type, invite.id)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message,
        });
        location.reload();
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  }

  upgradeAccount = form => {
    const data = {
      business_name: form.business_name,
    };
    return this.props
      .upgradeAccount(data)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Merchant Account Created.',
        });
        // $state.reload();
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  changePassword = values => {
    return this.props
      .updatePassword(values)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Password changed successfully.',
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    const { user, bankAccount, invitations } = this.props;
    let merchantDetails = null;
    let bankAccountDetails = null;
    let loggedInUserDetails = null;
    let invitationList = null;

    if (!user) {
      return <div>Loading..</div>;
    }

    if (user && user.current) {
      let customClass = '';
      merchantDetails = (
        <div className="row wrapper">
          <div className="list-group">
            <a className="list-group-item">
              <span
                className="pull-right"
                style={{ textTransform: 'capitalize' }}
              >
                {user.name}
              </span>
              Merchant Name
            </a>
            <a href="mailto:{{user.email}}" className="list-group-item">
              <span className="pull-right">{user.email}</span>
              Merchant Email
            </a>
            <a className="list-group-item">
              <span className="pull-right">
                {/*tooltip="{{user.activated == 1 ? 'Activated' : 'Not Activated'}}"*/}
                <i
                  className={`fa ${user.activated == 1 ? 'fa-check text-success' : 'fa-times text-danger'}`}
                />
              </span>
              Activation Status
            </a>
            <a className="list-group-item">
              <span className="pull-right">
                <Time
                  value={user.created_at}
                  format="MMM DD YYYY, hh:mm:ss a"
                />
              </span>
              Registration Date
            </a>
            <a className="list-group-item">
              <span className="pull-right">{user.activation_progress}%</span>
              Activation Form Progress
            </a>

          </div>
        </div>
      );
    }

    if (bankAccount) {
      bankAccountDetails = (
        <div className="row wrapper">
          <div className="panel-heading m-t m-b">
            Bank Account
          </div>
          <div className="panel panel-default">
            <div className="list-group">
              <a className="list-group-item">
                <span className="pull-right">{bankAccount.ifsc_code}</span>
                IFSC Code
              </a>
              <a className="list-group-item">
                <span className="pull-right">{bankAccount.account_number}</span>
                Account Number
              </a>
              <a className="list-group-item">
                <span className="pull-right">
                  {bankAccount.beneficiary_name}
                </span>
                Beneficiary
              </a>
            </div>
          </div>
        </div>
      );
    }

    if (
      this.state.merchantCount > 1 ||
      this.state.loggedInUser.email != user.email
    ) {
      loggedInUserDetails = (
        <div className="row wrapper">
          <div className="panel panel-default">
            <div className="list-group">
              <a className="list-group-item">
                <span
                  className="pull-right"
                  style={{ textTransform: 'capitalize' }}
                >
                  {this.state.loggedInUser.name}
                </span>
                User Name
              </a>
              <a
                href={`mailto:${this.state.loggedInUser.email}`}
                className="list-group-item"
              >
                <span className="pull-right">{loggedInUser.email}</span>
                Login Email
              </a>
              <a className="list-group-item">
                <span className="pull-right">{role}</span> {/*roletoname*/}
                Role
              </a>
            </div>
          </div>
        </div>
      );
    }

    if (invitations) {
      let invites = [];

      invitations.forEach((invite, index) => {
        invites.push(
          <a className="list-group-item" key={index}>
            <span className="pull-right">
              <button
                className="btn btn-xs btn-default"
                onClick={() => this.updateInvitation('accept', invite)}
              >
                Accept
              </button>
            </span>
            <span className="pull-right">
              <button
                className="btn btn-xs btn-danger"
                onClick={() => this.updateInvitation('reject', invite)}
              >
                Reject
              </button>
            </span>
            Invitation to join {invite.merchant.name}
          </a>
        );
      });

      invitationList = (
        <div className="row wrapper">
          {invites}
        </div>
      );
    }

    return (
      <div className="react-root">
        <Header title="User Profile" showMode={false} />
        <div className="content-wrapper">
          <div className="row">
            <div className=" col-sm-6 col-sm-offset-3">
              <div className="panel panel-default">
                <div className="panel-heading">
                  Merchant Id: <strong>{1000000000}</strong>
                </div>

                <div className="panel-body">
                  {merchantDetails}
                  {bankAccountDetails}
                  {loggedInUserDetails}
                  {invitations
                    ? <div className="panel-heading m-t m-b">
                        Pending Invitations
                      </div>
                    : null}
                  {invitationList}
                  {!this.state.hasMerchant
                    ? <MerchantForm upgradeAccount={this.upgradeAccount} />
                    : null}

                  <div className="text-center m-b">
                    <button
                      className="btn btn-primary btn-rounded btn-change-pwd"
                      onClick={() => {
                        this.props.openModal({
                          size: 'medium',
                          component: (
                            <PasswordForm
                              changePassword={this.changePassword}
                              closeModal={this.props.closeModal}
                            />
                          ),
                        });
                      }}
                    >
                      Change Password
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
